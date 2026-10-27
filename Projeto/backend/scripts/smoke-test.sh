#!/usr/bin/env bash
# ---------------------------------------------------------------------------
# Fluxo — teste de fumaça ponta a ponta (API v1 + PostgreSQL)
#
# Uso, a partir de Projeto/backend:
#   php artisan serve --host=0.0.0.0 --port=8000        # outro terminal
#   php artisan db:seed --class=DemoSeeder              # cria os usuários de teste
#   ./scripts/smoke-test.sh
#
# Variáveis: BASE_URL, EMAIL, SENHA, ADMIN_EMAIL, ADMIN_SENHA, VALOR
# Requisitos: curl e php (php só para ler o JSON).
# ---------------------------------------------------------------------------
set -uo pipefail

BASE_URL="${BASE_URL:-http://127.0.0.1:8000}"
EMAIL="${EMAIL:-ana@fluxo.test}"
SENHA="${SENHA:-senha123}"
ADMIN_EMAIL="${ADMIN_EMAIL:-admin@fluxo.test}"
VALOR="${VALOR:-2500}"
API="$BASE_URL/api/v1"

ok=0; falhas=0
passo() { printf '\n\033[1;34m== %s\033[0m\n' "$1"; }
ok_case() { printf '  \033[1;32mOK\033[0m   %s\n' "$1"; ok=$((ok+1)); }
fail_case() { printf '  \033[1;31mFALHOU\033[0m %s\n     %s\n' "$1" "${2:-}"; falhas=$((falhas+1)); }

request() { # METODO URL [TOKEN] [JSON] -> "STATUS\nCORPO"
    local metodo="$1" url="$2" token="${3:-}" corpo="${4:-}"
    local args=(-s -X "$metodo" "$url" -H 'Accept: application/json' -w $'\n%{http_code}')
    [[ -n "$token" ]] && args+=(-H "Authorization: Bearer $token")
    [[ -n "$corpo" ]] && args+=(-H 'Content-Type: application/json' -d "$corpo")
    curl "${args[@]}"
}
status_de() { tail -n1 <<<"$1"; }
corpo_de() { sed '$d' <<<"$1"; }
campo() { corpo_de "$1" | php -r '$d=json_decode(stream_get_contents(STDIN),true);foreach(explode(".",$argv[1]) as $p){if(!is_array($d)||!array_key_exists($p,$d)){exit(1);} $d=$d[$p];}echo is_scalar($d)?$d:json_encode($d);' "$2" 2>/dev/null; }

echo "Fluxo — smoke test em $BASE_URL"

# 1) banco e schema (comando próprio da aplicação)
passo "1/12 Conexão com o PostgreSQL e schema"
if php artisan fluxo:verificar >/tmp/fluxo-verificar.log 2>&1; then
    ok_case "fluxo:verificar: conexão, migrations, triggers e partidas dobradas OK"
else
    fail_case "fluxo:verificar falhou (veja /tmp/fluxo-verificar.log)" "$(tail -3 /tmp/fluxo-verificar.log)"
fi

# 2) health check HTTP
passo "2/12 Health check da API"
r=$(request GET "$API/health")
if [[ "$(status_de "$r")" == "200" ]]; then
    ok_case "GET /api/v1/health → $(campo "$r" banco.database) ($(campo "$r" banco.versao | cut -c1-30))"
else
    fail_case "health não respondeu 200" "$(corpo_de "$r")"
fi

# 3) rota não versionada aponta para v1
passo "3/12 Compatibilidade de versão (RNF30)"
r=$(request GET "$BASE_URL/api/conta")
if [[ "$(status_de "$r")" == "308" ]]; then
    ok_case "GET /api/conta redireciona para /api/v1/conta"
else
    fail_case "esperava 308" "$(status_de "$r")"
fi

# 4) autorização
passo "4/12 Rota protegida sem token"
r=$(request GET "$API/conta")
[[ "$(status_de "$r")" == "401" ]] && ok_case "401 sem token" || fail_case "esperava 401" "$(status_de "$r")"

# 5) login (SELECT no PostgreSQL)
passo "5/12 Login"
r=$(request POST "$API/login" "" "{\"email\":\"$EMAIL\",\"password\":\"$SENHA\"}")
TOKEN=$(campo "$r" token)
if [[ -n "${TOKEN:-}" ]]; then ok_case "login de $EMAIL gerou token"; else fail_case "login falhou" "$(corpo_de "$r")"; exit 1; fi

# 6) saldo
passo "6/12 Saldo e limites"
r=$(request GET "$API/conta" "$TOKEN")
saldo_antes=$(campo "$r" saldo_centavos)
[[ -n "${saldo_antes:-}" ]] && ok_case "saldo = $saldo_antes centavos | limite disponível = $(campo "$r" limites_pix.disponivel_centavos)" \
    || fail_case "GET /api/v1/conta falhou" "$(corpo_de "$r")"

# 7) chaves Pix
passo "7/12 Chaves Pix"
r=$(request GET "$API/chaves-pix" "$TOKEN")
ok_case "chaves cadastradas: $(corpo_de "$r" | php -r '$d=json_decode(stream_get_contents(STDIN),true);echo count($d["chaves"]??[]);')"

# 8) recebimento Pix (entrada com partida dobrada)
passo "8/12 Recebimento Pix (crédito + contra-partida)"
r=$(request POST "$API/pix/recebimentos" "$TOKEN" "{\"valor_centavos\":$VALOR,\"descricao\":\"smoke\"}")
[[ "$(status_de "$r")" == "201" ]] && ok_case "recebido; saldo agora $(campo "$r" saldo_centavos)" \
    || fail_case "recebimento falhou" "$(corpo_de "$r")"

# 9) cobrança Pix / EMV
passo "9/12 Cobrança Pix com payload EMV"
r=$(request POST "$API/pix/cobrancas" "$TOKEN" '{"valor_centavos":1000}')
if [[ "$(campo "$r" valido)" == "1" || "$(campo "$r" valido)" == "true" ]]; then
    ok_case "payload EMV gerado com CRC16 válido"
else
    fail_case "payload EMV inválido" "$(corpo_de "$r")"
fi

# 10) transferência interna com Idempotency-Key
passo "10/12 Transferência interna (2 lançamentos, idempotente)"
DESTINO=$(corpo_de "$(request GET "$BASE_URL/api/v1/eu" "$TOKEN")" | php -r '$d=json_decode(stream_get_contents(STDIN),true);echo $d["contas"][0]["numero"];')
r=$(curl -s -X POST "$API/transferencias" -H "Authorization: Bearer $TOKEN" -H 'Content-Type: application/json' \
      -H 'Accept: application/json' -H "Idempotency-Key: smoke-$$" -w $'\n%{http_code}' \
      -d "{\"chave_pix\":\"bruno@fluxo.test\",\"valor_centavos\":$VALOR,\"descricao\":\"smoke\"}")
if [[ "$(status_de "$r")" == "201" && "$(campo "$r" partidas_dobradas.fecham)" == "1" ]]; then
    ok_case "transferência gravada (id $(campo "$r" id)) com partidas fechando em zero"
elif [[ "$(status_de "$r")" == "422" ]]; then
    fail_case "transferência recusada" "$(corpo_de "$r")"
else
    fail_case "transferência retornou $(status_de "$r")" "$(corpo_de "$r")"
fi

# 11) cadastro + regra de negócio (conta nova, saldo zero)
passo "11/12 Cadastro novo e valor acima do saldo"
CPF_NOVO=$(php -r '$b=(string)random_int(100000000,999999999);$d=[];for($p=9;$p<11;$p++){$s=0;for($i=0;$i<$p;$i++){$s+=(int)$b[$i]*(($p+1)-$i);}$d[]=((10*$s)%11)%10;$b.=$d[count($d)-1];}echo substr($b,0,9).implode("",$d);')
EMAIL_NOVO="smoke$(date +%s)@fluxo.test"
r=$(request POST "$API/cadastro" "" "{\"nome\":\"Cliente Smoke\",\"cpf\":\"$CPF_NOVO\",\"data_nascimento\":\"1995-04-02\",\"email\":\"$EMAIL_NOVO\",\"password\":\"senha12345\"}")
if [[ "$(status_de "$r")" == "201" ]]; then
    ok_case "cadastro aprovado no KYC simulado (conta $(campo "$r" conta))"
else
    fail_case "cadastro falhou" "$(corpo_de "$r")"
fi

r=$(request POST "$API/login" "" "{\"email\":\"$EMAIL_NOVO\",\"password\":\"senha12345\"}")
TOKEN_NOVO=$(campo "$r" token)
r=$(request POST "$API/transferencias" "$TOKEN_NOVO" "{\"chave_pix\":\"bruno@fluxo.test\",\"valor_centavos\":10000}")
if [[ "$(status_de "$r")" == "422" && "$(campo "$r" erro)" == "Saldo insuficiente." ]]; then
    ok_case "conta sem saldo recusada: \"Saldo insuficiente.\" (nada gravado)"
else
    fail_case "esperava 422 Saldo insuficiente." "$(status_de "$r") $(corpo_de "$r")"
fi

# 12) auditoria + logout
passo "12/12 Auditoria e logout"
r=$(request POST "$API/login" "" "{\"email\":\"$ADMIN_EMAIL\",\"password\":\"$SENHA\"}")
ADMIN_TOKEN=$(campo "$r" token)
r=$(request GET "$API/backoffice/auditoria" "$ADMIN_TOKEN")
if [[ "$(status_de "$r")" == "200" ]]; then
    ok_case "trilha de auditoria acessível ao backoffice ($(campo "$r" total) registros)"
else
    fail_case "auditoria falhou (rode o DemoSeeder para ter o admin)" "$(status_de "$r") $(corpo_de "$r")"
fi
r=$(request POST "$API/logout" "$TOKEN")
[[ "$(status_de "$r")" == "200" ]] && ok_case "logout 200" || fail_case "logout falhou" "$(corpo_de "$r")"
r=$(request GET "$API/conta" "$TOKEN")
[[ "$(status_de "$r")" == "401" ]] && ok_case "token revogado → 401" || fail_case "token continuou válido" "$(status_de "$r")"

printf '\n\033[1mResultado: %d OK, %d falha(s)\033[0m\n' "$ok" "$falhas"
[[ "$falhas" -eq 0 ]] || exit 1
