# 4.3 — Diagramas de Componentes e de Implantação (UML)

## 1. CMP-01 — Diagrama de Componentes (visão lógica)

![CMP-01 — Diagrama de componentes](diagramas/cmp-01-componentes.svg)

### 1.1 Camadas

| Camada | Componentes | Responsabilidade |
|---|---|---|
| Apresentação | App Mobile (Flutter), Web App (Blade), Portal Admin (Blade) | Interface com o usuário |
| Fronteira | `API REST v1` `«gateway»` | Autenticação Sanctum (token), versionamento, rate limiting, validação |
| Domínio | 10 serviços de negócio, em arquitetura **MVC** *(revisão)* | Regras do banco; cada serviço é substituível |
| Persistência | Eloquent ORM (Models), Migrations/Seeders, PostgreSQL 16 | Mapeamento objeto-relacional e armazenamento |
| Externa | Serviço KYC, SPI/Pix, Registradora, SMTP/Push | Integrações **simuladas** |

**Padrão MVC adotado *(novo)*:** todo módulo do back-end segue **Model** (Eloquent — regra de persistência e especializações, ex.: `ClientePF`/`ClientePJ`, `ContaCorrente`/`ContaPoupanca`, `CartaoFisico`/`CartaoVirtual`), **View** (Blade para a Web; API Resources/JSON para o app) e **Controller** (recebe a requisição, valida e delega ao Service — nunca contém regra de negócio).

### 1.2 Componentes de domínio

| Componente | Interfaces principais | Requisitos |
|---|---|---|
| `AuthService` | `login()`, `emitirToken()` *(revisão)*, `validar2FA()`, `refreshToken()`, `revogarToken()` *(revisão)* | RF02, RF17 |
| `CadastroService` | `cadastrar()` (PF ou PJ) *(revisão)*, `verificarKyc()`, `abrirConta()` | RF01, RF10 |
| `ContaService` | `saldo()`, `extrato()`, `encerrar()`, `bloquear()`/`desbloquear()` *(revisão)* | RF03, RF13 |
| `MotorTransacao` | `transferir()`, `partidasDobradas()`, `estornar()` | RF04, RN02, RN03 |
| `MotorAntifraude` | `analisar()`, `verificarSaldo()`, `verificarLimites()`, `verificarLocalizacao()`, `verificarAvisoViagem()`, `classificar()` *(revisão)* | RF05, RN27–RN32 |
| `CartoesService` | `emitirFisico()`, `gerarVirtual()` *(revisão)*, `bloquear()`, `fatura()` | RF07, RF12, RN33 |
| `AssinaturaService` | `contratar()`, `adicionarItem()`, `removerItem()`, `trocarPlano()`, `gerarCobranca()` | Módulo de Assinaturas (Escopo 4.1), RN22–RN25 |
| `PagamentosService` | `pagarBoleto()`, `cobrancaPix()`, `gerarQrCodePix()` *(revisão)*, `agendar()` | RF08, RF15 |
| `NotificacoesService` | `email()`, `push()`, `fila` | — |
| `AuditoriaService` | `registrar()`, `consultar()`, `relatorio()` | RF09, RF-SEG01–04 |

### 1.3 Interfaces oferecidas e requeridas

| Interface | De | Para |
|---|---|---|
| `IApiV1` (bola) | API REST v1 | App Mobile, Web App, Portal Admin |
| `IKyc` (soquete) | CadastroService | Serviço KYC |
| `INotificacao` (soquete) | NotificacoesService | SMTP / Push |

`CadastroService`, `MotorTransacao`, `PagamentosService`, `NotificacoesService` e `AssinaturaService` (cobrança recorrente) dependem de componentes externos por `«use»` tracejado — trocar o simulador por um serviço real não altera o contrato interno.

### 1.4 Decisões arquiteturais

1. **MVC em todo o back-end** *(revisão)*: Models Eloquent concentram persistência e especialização (Cliente, Conta, Cartão); Controllers apenas validam e delegam; Views/Resources formatam a saída para Web e API.
2. Uma única API `/api/v1` serve app e web, evitando duplicidade de regra de negócio.
3. A regra de negócio vive nos Services, nunca nos Controllers (testabilidade, RNF27).
4. `AuditoriaService` é transversal — todo serviço de domínio depende dele, incluindo os métodos de log expandidos (RF-SEG04).
5. Nenhum serviço escreve SQL direto; migrations versionam o esquema, inclusive as tabelas de especialização (discriminador `tipo`/`modalidade`).
6. Notificações e cobrança de assinatura rodam por fila, preservando o tempo de resposta de escrita.
7. Autenticação via **token Sanctum**, emitido por `AuthService::emitirToken()` e exigido por todo middleware de rota protegida (RF17).

---

## 2. CMP-02 — Diagrama de Implantação

![CMP-02 — Diagrama de implantação](diagramas/cmp-02-implantacao.svg)

### 2.1 Nós

| Nó | Estereótipo | Conteúdo |
|---|---|---|
| Dispositivo do cliente | `«device»` | Navegador e app Android (Flutter 3.x, Android 8.0+) |
| Estação de desenvolvimento | `«device»` | GitHub Codespaces (backend), Android Studio (mobile) |
| GitHub | `«repository»` | Monorepo `backend/` + `mobile/` |
| Render | `«cloud»` | Web Service PHP 8.2/Laravel 11 (MVC), Cron Job, PostgreSQL 16 gerenciado |
| Serviços externos simulados | `«external»` | SPI/Pix, registradora, KYC, SMTP/Push |

### 2.2 Conexões

| De | Para | Protocolo | Observação |
|---|---|---|---|
| Dispositivo do cliente | Web Service | `«HTTPS»` 443 | TLS 1.2+; header `Authorization: Bearer <token>` (Sanctum) |
| Web Service | PostgreSQL | `«TCP»` 5432 | Interno ao datacenter do Render |
| Web Service | Cron Job | `«HTTPS»` | Aciona a expiração de retiradas/cobranças e a geração de faturas de assinatura |
| GitHub | Render | `«webhook»` | Push em `main` dispara build/deploy |

### 2.3 Limitações do plano gratuito do Render e mitigação

| Limitação | Mitigação |
|---|---|
| Hiberna após ~15 min sem tráfego | "Aquecer" a URL antes da apresentação; vídeo de contingência |
| Banco expira em 30 dias | `pg_dump` semanal + migrations/seeders como fonte de verdade |
| Cron roda em container efêmero | Agendador acionado por requisição HTTP externa; sem estado local |
| Sem disco persistente | Anexos gravados no banco (`bytea`) ou storage externo |
| Build com limite de tempo | APK gerado no Android Studio, publicado como release no GitHub |

---

## 3. Organização do repositório (monorepo)

```
Fluxo/
├── backend/                    # Laravel 11 (PHP 8.2+) — arquitetura MVC
│   ├── app/Http/Controllers/   # Auth, Transacao, Retirada, Assinatura, Cartao...
│   ├── app/Models/             # Cliente, ClientePF, ClientePJ, Conta, ContaCorrente,
│   │                           # ContaPoupanca, ContaSalario, ContaPJ, Cartao,
│   │                           # CartaoFisico, CartaoVirtual, LogAuditoria...
│   ├── app/Services/           # MotorTransacao, MotorAntifraude, AssinaturaService,
│   │                           # PagamentosService (QR Code), AuditoriaService...
│   ├── app/Policies/           # RBAC (RF09)
│   ├── database/migrations/
│   ├── resources/views/        # Blade + Tailwind
│   ├── routes/api.php
│   └── tests/
└── mobile/                     # Flutter (Dart)
    ├── lib/screens/            # Login, Dashboard, Extrato, Pix, Retirada, Assinatura, Cartao
    ├── lib/services/
    └── test/
```

---

## 4. Rastreabilidade

| Requisito | Evidência |
|---|---|
| RF16 (MVC) | CMP-01 — decisão arquitetural 1; organização do repositório (Models/Controllers/Views) |
| RF17 (token) | CMP-01 — `AuthService::emitirToken()`; CMP-02 — header `Authorization: Bearer` |
| RF15 (QR Code) | CMP-01 — `PagamentosService::gerarQrCodePix()` |
| RF10–RF12 (especialização) | CMP-01 — Models especializados na organização do repositório |
| Segurança em trânsito | CMP-02 — `«HTTPS»` em todas as conexões externas |
| Deploy automático | CMP-02 — webhook GitHub → Render |
| Auditoria transversal (RF09, RF-SEG04) | CMP-01 — `AuditoriaService` dependido por todo o domínio |
| Módulo de Assinaturas (Escopo 4.1) | CMP-01 — `AssinaturaService` |
| Backup semanal | CMP-02 — mitigação do free tier |

**Registro de alterações**

| Versão | Data | Alteração |
|---|---|---|
| 1.0 | 31/08/2026 | Versão inicial: CMP-01 e CMP-02 |
| 2.0 | 10/09/2026 | Texto condensado; `AssinaturaService` adicionado ao domínio |
| 3.0 | 10/09/2026 | Explicitado o padrão MVC; adicionados `emitirToken()`/`revogarToken()`, `gerarQrCodePix()` e as interfaces expandidas do `MotorAntifraude` |
