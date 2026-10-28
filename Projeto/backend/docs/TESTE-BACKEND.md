# Como testar o backend do Fluxo (API v1 + PostgreSQL)

Três caminhos, do mais rápido ao mais completo. Todos usam **somente PostgreSQL**.

---

## 1. Docker (nada instalado na máquina)

```bash
cd Projeto/backend
docker compose up --build          # PostgreSQL 16 + API em http://localhost:8000
```

O container roda `migrate`, aplica o `DemoSeeder` (idempotente) e sobe o servidor.
Em outro terminal:

```bash
docker compose exec api php artisan fluxo:verificar      # banco, migrations, triggers, partidas dobradas
docker compose exec api ./scripts/smoke-test.sh          # teste ponta a ponta da API
docker compose exec db psql -U fluxouser -d fluxodb -c '\dt'
```

Parar e apagar dados: `docker compose down -v`.

---

## 2. Local sem Docker

```bash
# dependências (Debian/Ubuntu; no Windows use WSL)
sudo apt install -y php8.4-cli php8.4-pgsql php8.4-mbstring php8.4-xml php8.4-curl \
                    php8.4-zip php8.4-bcmath composer postgresql unzip
sudo pg_ctlcluster 17 main start

# bancos (um de trabalho, um de teste)
sudo -u postgres psql \
  -c "CREATE USER fluxouser WITH PASSWORD 'fluxopass';" \
  -c "CREATE DATABASE fluxodb    OWNER fluxouser;" \
  -c "CREATE DATABASE fluxo_test OWNER fluxouser;"

cd Projeto/backend
cp .env.example .env
php artisan key:generate
php artisan migrate --force
php artisan db:seed --class=DemoSeeder      # dados de demonstração
php artisan serve --host=0.0.0.0 --port=8000
```

---

## 3. Testar a conexão com o banco em 4 níveis

| Nível | Comando | Se falhar |
|---|---|---|
| 1. Driver/credencial | `php artisan db:show` | `DB_HOST/DB_PORT/senha` ou falta `pdo_pgsql` |
| 2. Schema versionado | `php artisan migrate:status` | rode `php artisan migrate` |
| 3. Query + auditoria + integridade | `php artisan fluxo:verificar` | mostra exatamente qual item está errado (com ✗) |
| 4. HTTP de verdade | `./scripts/smoke-test.sh` | login 401 → seeder não rodou; 500 → `storage/logs/laravel.log` |

`php artisan fluxo:verificar` cobre: conexão, driver `pgsql`, versão do PostgreSQL,
migrations pendentes, triggers de auditoria, **teste real de append-only**
(insere e tenta apagar um registro da trilha) e **partidas dobradas** (procura
transações cuja soma de lançamentos ≠ 0).

---

## 4. Smoke test da API (15 verificações)

```bash
php artisan db:seed --class=DemoSeeder      # cria ana/bruno/admin
./scripts/smoke-test.sh                     # BASE_URL=http://127.0.0.1:8000 por padrão
```

O que ele valida:

1. `fluxo:verificar` (banco + triggers + partidas dobradas)
2. `GET /api/v1/health` (consulta o PostgreSQL de verdade)
3. Rota antiga `/api/conta` redireciona para `/api/v1/conta` (RNF30)
4. Rota protegida sem token → 401
5. Login → token
6. Saldo + limites Pix
7. Chaves Pix cadastradas
8. Recebimento Pix (crédito com contra-partida)
9. Cobrança Pix com payload EMV e CRC16 válido
10. Transferência com `Idempotency-Key`, conferindo `partidas_dobradas.fecham = true`
11. Cadastro novo + regra de saldo insuficiente (422 sem gravar nada)
12. Auditoria pelo backoffice + logout + token revogado (401)

---

## 5. Suíte automatizada (83 testes em 13 arquivos)

Roda no PostgreSQL (`fluxo_test`, definido no `phpunit.xml`):

```bash
php artisan test                 # Feature + Unit
php artisan test --filter=AuditoriaTest
composer test                    # limpa config e roda tudo
composer lint                    # Laravel Pint (PSR-12)
```

Cobertura por arquivo:

| Arquivo | Cobre |
|---|---|
| `CadastroTest` | RF01/RF10/RN35: PF, PJ, CPF/CNPJ, KYC (aprovação e recusa de menor) |
| `DoisFatoresTest` | RF02/RN19/RN20: desafio, token temporário, uso único, bloqueio após 5 falhas |
| `ContaTest` | RF03/RF13/RF18/RNF05: saldo, extrato paginado, autobloqueio, encerramento, KYC pendente |
| `TransferenciaTest` | RF05/RNF15/16/17: saldo, partidas dobradas, mesma conta, idempotência, conta bloqueada |
| `PixTest` | RF04/RF14/RF23/RN28-RN30/RN42: chaves, limites, transferência por chave, EMV |
| `PagamentoTest` | RF08/RF15: validação e pagamento de boleto, recusa sem saldo, recebimento Pix com idempotência, comprovante e IDOR |
| `CartaoTest` | RF07/RF12/RF21/RF24/RN38–RN40/RN44: emissão, virtual (unicidade 409), bloqueio, limite, compras on-line, aviso de viagem e assinaturas do cartão |
| `AgendamentoTest` | RF06/RF25/RN45: agendamento, worker, execução única, cancelamento |
| `AssinaturaTest` | RN22: fatura proporcional e retirada lógica de item |
| `AuditoriaTest` | RF-SEG01–04/RF22: middleware por categoria, triggers OLD/NEW/DIFF, append-only, masking |
| `BackofficeTest` | RF09/RN37: RBAC, aprovação de KYC, bloqueio por fraude, chamados |
| `EmvTest` (Unit) | RN36: CRC16 e campos do payload Pix |
| `LancamentoServiceTest` (Unit) | RN02/RN03: recusa lançamento único e desbalanceado |

---

## 6. Usuários de demonstração (`DemoSeeder`)

| E-mail | Senha | Papel | Estado |
|---|---|---|---|
| `ana@fluxo.test` | `senha123` | CLIENTE | 1ª conta, R$ 1.000,00, 2 chaves Pix, cartão débito + virtual + crédito |
| `bruno@fluxo.test` | `senha123` | CLIENTE | 2ª conta (destino das transferências), chave Pix |
| `admin@fluxo.test` | `senha123` | ADMIN | acesso ao `/api/v1/backoffice` |

> `php artisan db:seed` (sem `--class`) aplica **apenas o catálogo** (planos/itens).
> O `DemoSeeder` é só para local/teste — nunca em produção.

---

## 7. Fluxo manual em 6 comandos (curl)

```bash
API=http://127.0.0.1:8000/api/v1

TOKEN=$(curl -s -X POST $API/login -H 'Content-Type: application/json' \
  -d '{"email":"ana@fluxo.test","password":"senha123"}' | php -r 'echo json_decode(stream_get_contents(STDIN))->token;')

curl -s $API/conta -H "Authorization: Bearer $TOKEN"                      # saldo e limites
curl -s $API/conta/extrato -H "Authorization: Bearer $TOKEN"              # extrato
curl -s -X POST $API/transferencias -H "Authorization: Bearer $TOKEN" \
  -H 'Content-Type: application/json' -H "Idempotency-Key: meu-pedido-1" \
  -d '{"chave_pix":"bruno@fluxo.test","valor_centavos":25000}'            # Pix de R$ 250,00
curl -s -X POST $API/pix/cobrancas -H "Authorization: Bearer $TOKEN" \
  -H 'Content-Type: application/json' -d '{"valor_centavos":5000}'         # QR Code EMV
php artisan agendamentos:processar                                        # worker de agendamentos
```
