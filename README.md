# Fluxo Bank — Plataforma Bancária Digital

Repositório oficial do projeto **Fluxo Bank**, uma plataforma bancária digital com arquitetura em camadas, contabilidade por partidas dobradas, antifraude síncrono, autenticação em dois fatores (2FA), controle de acesso baseado em papéis (RBAC) e trilha de auditoria imutável (*append-only*) no PostgreSQL 16.

---

## 1. Estrutura do Repositório

```text
Fluxo-Bank/
├── Documentacao/                    # Artefatos acadêmicos (CP1, CP2 e CP3)
│   ├── Diagramas/                   # Casos de Uso, Sequência, Atividades, Classes (CLS-01 a CLS-07) e DER
│   ├── libreproject/                # Cronograma ProjectLibre (.xml e .csv)
│   ├── Escopo.md                    # Escopo do sistema e módulo de assinaturas
│   ├── Requisitos.md                # Requisitos Funcionais (RF), Não Funcionais (RNF) e Regras de Negócio (RN)
│   ├── CasosDeUso.md                # Especificação detalhada dos casos de uso
│   ├── Prototipacao.md              # Guia de estilo e protótipos Figma (Mobile e Web Backoffice)
│   └── QuadroDeGestao.md            # Quadro Kanban de acompanhamento dos Checkpoints
├── Projeto/
│   ├── backend/                     # API RESTful em Laravel 13 (PHP 8.3/8.4 + Sanctum + PostgreSQL 16)
│   │   ├── app/
│   │   │   ├── Console/Commands/    # ProcessarAgendamentos, VerificarIntegridade e GerarCodigoRecuperacaoSenha (senha:gerar-codigo)
│   │   │   ├── Exceptions/          # Exceções de domínio financeiro e HTTP (409, 422, 423)
│   │   │   ├── Http/
│   │   │   │   ├── Controllers/     # 17 Controllers REST (/api/v1/*)
│   │   │   │   ├── Middleware/      # AuditoriaRequisicao, ApenasDoisFatoresConcluido, ApenasBackoffice, Idempotencia
│   │   │   │   └── Resources/       # Serialização JSON com mascaramento LGPD (ContaResource, LancamentoResource, TransacaoResource)
│   │   │   ├── Models/              # 22 Models Eloquent (User, Cliente, Conta, Transacao, Lancamento, etc.)
│   │   │   ├── Rules/               # Validação matemática de dígitos verificadores (Cpf, Cnpj)
│   │   │   ├── Services/            # 17 Services de domínio (partidas dobradas, Pix, antifraude, 2FA, recuperação de senha, etc.)
│   │   │   └── Support/             # ContaInterna, gerador EMV/BR Code (CRC16-CCITT) e Sanitizador LGPD
│   │   ├── database/
│   │   │   ├── migrations/          # 20 migrations (incluindo pgcrypto, funções PL/pgSQL, rules e triggers append-only)
│   │   │   └── seeders/             # DatabaseSeeder e DemoSeeder
│   │   ├── docs/                    # Guias técnicos de arquitetura, testes e operação do backend
│   │   ├── routes/                  # api.php, api_v1.php e web.php
│   │   ├── tests/                   # 96 testes automatizados (Unit e Feature) em 14 arquivos
│   │   ├── Dockerfile               # Imagem PHP 8.3-cli para execução e deploy no Render
│   │   └── docker-compose.yml       # Orquestração local (api + PostgreSQL 16)
│   └── render.yaml                  # Blueprint de infraestrutura como código para nuvem (Render)
└── RELATORIO_CHECKPOINT_3.md        # Relatório completo de auditoria e conformidade do Checkpoint 3
```

---

## 2. Tecnologias Utilizadas (Backend — Checkpoint 3)

- **Linguagem:** PHP 8.3 / 8.4 (`declare(strict_types=1)`)
- **Framework:** Laravel 13 (`laravel/framework: ^13.17`)
- **Autenticação e Segurança:** Laravel Sanctum (`^4.0`), bcrypt (cost 12), 2FA via código de 6 dígitos com hash (`Servico2FA`), RBAC (`CLIENTE`, `ATENDENTE`, `ADMIN`) e bloqueio temporário por tentativas (`HTTP 423`).
- **Banco de Dados:** PostgreSQL 16 (`postgres:16-alpine`) com extensão `pgcrypto`, funções PL/pgSQL (`fn_auditoria`, `fn_jsonb_diff`, `fn_auditoria_sanitizar`), `RULE`s *append-only* e triggers em 20 tabelas de domínio (`logs_auditoria`).
- **Testes Automatizados:** PHPUnit 12 (`phpunit/phpunit: ^12.5`) — **96 testes distribuídos em 14 arquivos (390 assertions)** (`tests/Unit` e `tests/Feature`), executados sobre PostgreSQL (`fluxo_test`).
- **Infraestrutura e Deploy:** Docker (`Dockerfile` + `docker-compose.yml`) e Render Cloud Blueprint (`Projeto/render.yaml`).

---

## 3. Principais Módulos da API REST (`/api/v1`)

| Módulo | Endpoints Principais (`routes/api_v1.php`) | Regras de Negócio Atendidas |
|---|---|---|
| **Saúde / Status** | `GET /`, `GET /api/v1/health` | Verificação de conectividade real com PostgreSQL (`200` / `503`) |
| **Cadastro & KYC** | `POST /api/v1/cadastro` (`tipo_cliente: PF \| PJ`) | RF01, RF10, RF11, RN01, RN05, RN35 (validação matemática de CPF/CNPJ) |
| **Autenticação, 2FA & Recuperação de Senha** | `POST /api/v1/login`, `/2fa/validar`, `/2fa/desafio`, `/2fa/ativar`, `/2fa/desativar`, `/senha/recuperacao/solicitar`, `/senha/recuperacao/validar`, `/senha/recuperacao/redefinir`, `/refresh`, `/logout`, `/logout-all`, `GET /eu` | RF02, RF17, RN16, RN18–RN20, recuperação manual de senha em 2 etapas com código CSPRNG de 6 dígitos vinculado à conta |
| **Contas & Extratos** | `GET /api/v1/conta`, `/conta/extrato`, `/conta/extrato/categorizado`, `POST /conta/bloqueio`, `DELETE /conta/bloqueio`, `DELETE /conta` | RF03, RF04, RF13, RF18, RN02, RN06, RN34, RNF05 |
| **Transferências & Pix** | `GET /api/v1/transacoes`, `GET /transacoes/{id}`, `POST /api/v1/transferencias`, `GET/POST/DELETE /api/v1/chaves-pix`, `POST /api/v1/pix/cobrancas`, `/pix/recebimentos`, `GET /pix/limites` | RF04, RF05, RF14, RF23, RN02–RN04, RN27–RN30, RN36, RN42 |
| **Boletos** | `POST /api/v1/boletos/validar`, `POST /api/v1/boletos/pagar` | RF08, RF15, RN02, RN03 |
| **Cartões & Viagem** | `GET/POST /api/v1/cartoes`, `POST /cartoes/{id}/virtual`, `PATCH /bloqueio`, `/limite`, `/compras-online`, `GET/POST/DELETE /api/v1/avisos-viagem`, `GET/DELETE /api/v1/assinaturas-cartao` | RF07, RF12, RF19–RF21, RF24, RN33, RN38–RN40, RN44 |
| **Agendamentos** | `GET/POST /api/v1/agendamentos`, `DELETE /api/v1/agendamentos/{id}` | RF06, RF25, RN45 |
| **Assinaturas** | `GET /api/v1/planos`, `GET/POST /api/v1/assinaturas`, `POST/DELETE /assinaturas/{id}/itens`, `DELETE /assinaturas/{id}` | Escopo 4.1, RN21–RN25 (exclusão lógica com motivo em `itens_assinatura`) |
| **Suporte & Backoffice** | `GET/POST /api/v1/chamados`, `GET /api/v1/backoffice/resumo`, `/contas`, `POST /contas/{id}/kyc/aprovar`, `/kyc/recusar`, `/bloquear-fraude`, `GET/PATCH /backoffice/chamados`, `GET /backoffice/auditoria` | RF09, RF22, RN37, RF-SEG01–04 |

---

## 4. Como Executar o Projeto

### 4.1 Via Docker Compose (Recomendado)

```bash
cd Projeto/backend
docker compose up --build -d
docker compose exec api php artisan fluxo:verificar
```

A API estará disponível em `http://localhost:8000`.

### 4.2 Executar os Testes Automatizados (96 testes)

```bash
cd Projeto/backend
docker compose exec db createdb -U fluxouser fluxo_test
docker compose exec api php artisan test
```

Para mais detalhes sobre a arquitetura interna, credenciais de demonstração e exemplos de requisições `curl`, consulte:
- [`Projeto/backend/README.md`](Projeto/backend/README.md)
- [`Projeto/backend/docs/BACKEND-COMPLETO.md`](Projeto/backend/docs/BACKEND-COMPLETO.md)
- [`Projeto/backend/docs/TESTE-BACKEND.md`](Projeto/backend/docs/TESTE-BACKEND.md)
- [`RELATORIO_CHECKPOINT_3.md`](RELATORIO_CHECKPOINT_3.md)
