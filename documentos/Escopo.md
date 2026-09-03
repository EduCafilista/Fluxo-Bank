# 4.1 — Definição do Escopo do Projeto

**Projeto:** Fluxo — Sistema Bancário Digital
**Disciplina:** Projetos de Software II — Prof. Marcus Colantoni (UNIUBE)
**Grupo:** 1 · **Checkpoint 1** — apresentação em 03/09
**Repositório:** https://github.com/AlfredoVentura/Fluxo

---

## 1. Contexto e justificativa

O mercado brasileiro de serviços financeiros passou por uma forte digitalização na última década, impulsionada pelo Pix, pelo Open Finance e pela redução das barreiras de entrada para instituições de pagamento. Nesse cenário, a experiência do usuário deixou de ser um diferencial e passou a ser requisito básico: o cliente espera abrir conta em minutos, transacionar em segundos e acompanhar tudo pelo celular.

O **Fluxo** nasce como um exercício acadêmico de engenharia de software que reproduz, em escala reduzida e em ambiente controlado, o núcleo funcional de um banco digital. O objetivo não é operar dinheiro real, mas **projetar, documentar, implementar e implantar** um sistema completo — com duas interfaces de usuário (web e mobile), backend próprio, banco de dados relacional e hospedagem em nuvem — aplicando o ciclo de vida de desenvolvimento estudado na disciplina.

## 2. Objetivo geral

Desenvolver um sistema bancário digital funcional, acessível remotamente, que permita ao cliente abrir e gerenciar uma conta, movimentar saldo por meio de transferências e pagamentos simulados, operar cartões virtuais e físicos e acompanhar seu histórico financeiro, com uma área administrativa para backoffice, suporte e auditoria.

## 3. Objetivos específicos

1. Levantar e documentar os requisitos funcionais e não funcionais do sistema.
2. Modelar o domínio bancário em UML (casos de uso, DER, diagrama de classes).
3. Implementar o backend em **Laravel (PHP)** expondo API REST e telas web.
4. Construir a interface web em **Blade + Tailwind CSS**.
5. Construir o aplicativo mobile em **Flutter**, consumindo a mesma API.
6. Persistir os dados em **PostgreSQL**, com no mínimo 8 entidades no modelo.
7. Implantar o sistema no **Render**, com deploy contínuo a partir do GitHub.
8. Manter commits semanais no GitHub e o cronograma atualizado no LibreProject.
9. Entregar o manual do usuário ao final do semestre.

## 4. Descrição do produto

O Fluxo é composto por quatro grandes blocos:

| Bloco | Descrição |
|---|---|
| **App Mobile (Flutter)** | Aplicativo do cliente final para Android e iOS: abertura de conta, saldo, extrato, Pix, cartões, pagamentos e notificações. |
| **Web App (Blade + Tailwind)** | Portal do cliente com as mesmas operações essenciais em tela ampla, e **portal administrativo** para backoffice, suporte e auditoria. |
| **Backend (Laravel/PHP)** | Autenticação, regras de negócio, motor transacional, API REST versionada, trilha de auditoria e integrações simuladas. |
| **Banco de Dados (PostgreSQL)** | Modelo relacional normalizado com o razão contábil (lançamentos de débito e crédito) como núcleo. |

### 4.1 Princípio contábil adotado

Toda movimentação financeira no Fluxo é registrada por **partidas dobradas**: uma transação gera no mínimo dois lançamentos (`débito` e `crédito`) cuja soma é sempre zero. O saldo de uma conta nunca é um campo editável — é o resultado da soma dos seus lançamentos. Essa decisão garante rastreabilidade, permite reprocessamento e evita a classe de bugs mais comum em sistemas financeiros acadêmicos (saldo dessincronizado do extrato).

## 5. Escopo do produto — o que **está** incluído

### 5.1 Módulo de Acesso e Conta
- Cadastro do cliente com validação de CPF, e-mail e telefone.
- Verificação de identidade (KYC) **simulada**, com aprovação manual pelo backoffice.
- Login com e-mail/senha, autenticação em dois fatores (2FA) por código de e-mail.
- Recuperação de senha por token com expiração.
- Manutenção de dados cadastrais e encerramento de conta.

### 5.2 Módulo de Movimentações
- Consulta de saldo e extrato com filtros por período, tipo e valor.
- Transferência Pix **simulada** (chave aleatória, CPF, e-mail e telefone).
- Registro e gerenciamento de chaves Pix do cliente.
- Transferência interna entre contas Fluxo (liquidação imediata).
- Depósito simulado via Pix ou boleto.
- Agendamento de transferências com execução por job agendado.
- Emissão de comprovante em PDF.
- Análise de risco por regras (limite por operação, limite diário, horário noturno, favorecido novo).

### 5.3 Módulo de Cartões
- Emissão de cartão virtual (número gerado e mascarado).
- Solicitação de cartão físico com acompanhamento de status.
- Bloqueio e desbloqueio temporário ou definitivo.
- Ajuste de limite dentro do teto aprovado.
- Fatura com lançamentos, fechamento e vencimento.
- Contestação de lançamento e tratamento pelo backoffice.

### 5.4 Módulo de Pagamentos
- Pagamento de boleto por código de barras/linha digitável (validação de dígito verificador; liquidação simulada).
- Emissão e pagamento de cobrança Pix via QR Code (payload EMV).
- Agendamento de pagamentos recorrentes.
- Notificações de eventos financeiros ao cliente.

### 5.5 Módulo Administrativo
- Gestão de usuários, perfis e permissões (RBAC).
- Bloqueio de contas suspeitas.
- Trilha de auditoria imutável de operações sensíveis.
- Relatórios gerenciais (volume transacionado, contas ativas, chamados).
- Parametrização de tarifas e limites operacionais.
- Central de chamados de suporte.

## 6. Fora de escopo — o que **não** será feito

Delimitar o que não será construído é tão importante quanto listar o que será. Não fazem parte deste projeto:

- **Operação financeira real.** Nenhum dinheiro real transita pelo sistema; não há integração com o SPI/Banco Central, com arranjos de pagamento ou com instituições financeiras reais.
- **Autorização regulatória.** O Fluxo não é e não pretende ser uma instituição de pagamento autorizada pelo BACEN.
- **Emissão real de cartões.** Não há vínculo com bandeiras (Visa/Mastercard) nem processadora; a autorização de compra é simulada.
- **Crédito e investimentos.** Empréstimos, financiamentos, cheque especial, CDB, poupança remunerada e câmbio ficam fora desta versão.
- **Open Finance / compartilhamento de dados** entre instituições.
- **Biometria facial ou documentoscopia automatizada** no KYC (a validação é simulada e aprovada manualmente).
- **Aplicativo desktop nativo** e versões para smartwatch.
- **Publicação nas lojas oficiais** (Google Play / App Store); o APK será disponibilizado para download direto.
- **Build e demonstração em iOS.** O Flutter garante a compatibilidade do código e a pasta `ios/` é mantida, mas compilar para iPhone exige macOS e conta paga de desenvolvedor Apple — a demonstração será feita em Android.

## 7. Atores do sistema

| Ator | Tipo | Descrição |
|---|---|---|
| Visitante | Humano | Pessoa não autenticada que se cadastra ou recupera acesso. |
| Cliente | Humano | Correntista titular de uma conta Fluxo. |
| Favorecido | Humano | Destinatário de uma transferência (pode ser cliente Fluxo). |
| Pagador externo | Humano | Terceiro que liquida uma cobrança emitida por um cliente. |
| Analista de Suporte | Humano | Atende chamados abertos pelos clientes. |
| Analista de Backoffice | Humano | Aprova aberturas de conta e trata contestações. |
| Administrador | Humano | Gerencia usuários, perfis, tarifas e limites. |
| Auditor | Humano | Consulta trilha de auditoria e relatórios (acesso somente leitura). |
| Serviço KYC | Sistema | Serviço simulado de validação de identidade. |
| SPI / Pix | Sistema | Arranjo de pagamentos instantâneos (simulado). |
| Registradora de boletos | Sistema | Serviço simulado de consulta e liquidação de boletos. |
| Motor Antifraude | Sistema | Componente interno de análise de risco por regras. |
| Motor de Notificações | Sistema | Componente interno de envio de e-mail e push. |

## 8. Arquitetura e stack tecnológica

```
┌──────────────────┐        ┌──────────────────┐
│  App Mobile      │        │  Web (Blade +    │
│  Flutter         │        │  Tailwind CSS)   │
└────────┬─────────┘        └────────┬─────────┘
         │  HTTPS / JSON              │  HTTPS
         │  (API REST v1)             │  (sessão)
         └────────────┬───────────────┘
                      ▼
         ┌────────────────────────────┐
         │   Backend — Laravel (PHP)  │
         │  Controllers · Services ·  │
         │  Policies · Jobs · Events  │
         └────────────┬───────────────┘
                      ▼
         ┌────────────────────────────┐
         │   PostgreSQL (Render)      │
         └────────────────────────────┘
```

| Camada | Tecnologia | Justificativa |
|---|---|---|
| Frontend Web | Blade + Tailwind CSS | Integração nativa com Laravel, sem build complexo; Tailwind acelera a prototipação das telas. |
| Frontend Mobile | Flutter (Dart) | Base de código única para Android/iOS; consome a API REST do Laravel. |
| Backend | Laravel 11 (PHP 8.2+) | Eloquent ORM, migrations, Sanctum para autenticação por token, filas e agendador prontos. |
| Banco de dados | PostgreSQL 16 | Transações ACID, tipo `numeric` exato para valores monetários, constraints robustas. |
| Hospedagem | Render | Plano gratuito, deploy automático a partir do GitHub, PostgreSQL gerenciado. |
| Controle de versão | Git + GitHub | Commits semanais, branches por feature, acesso ao professor. |
| Ambiente de desenvolvimento | GitHub Codespaces + Android Studio | Codespaces padroniza o ambiente do backend na nuvem; Android Studio compila, emula e testa o app. |
| Gestão | LibreProject | Cronograma, WBS e Gantt atualizados a cada checkpoint. |

### 8.1 Organização do código — monorepo

O repositório adota **monorepo**, com backend e mobile no mesmo versionamento:

```
Fluxo/
├── backend/     # Laravel (PHP) — API REST, regras de negócio e web em Blade
│   ├── app/            Controllers e Models
│   ├── database/       Migrations e Seeders
│   ├── resources/      Views Blade + Tailwind
│   ├── routes/         web.php e api.php
│   └── tests/          Testes automatizados
└── mobile/      # Flutter (Dart) — app Android e iOS
    ├── lib/screens/    Telas (Login, Dashboard, Extrato, Transferências)
    ├── lib/services/   Comunicação HTTP com a API
    └── test/           Testes unitários e de widget
```

**Vantagem:** uma alteração de contrato de API e o ajuste correspondente no app entram no mesmo Pull Request, mantendo backend e mobile sempre sincronizados.

> **Atenção ao plano gratuito do Render:** o serviço web hiberna após ~15 min sem tráfego (primeira requisição demora ~50 s) e a instância gratuita de PostgreSQL **expira após 30 dias**. Providência do grupo: agendar dump semanal do banco (`pg_dump`) versionado fora do repositório e recriar a instância quando expirar, mantendo as migrations e seeders como fonte de verdade.

## 9. Modelo de dados — entidades previstas

O requisito da disciplina é de no mínimo 8 entidades. O Fluxo prevê 16:

| # | Entidade | Papel |
|---|---|---|
| 1 | `usuarios` | Credenciais, perfil de acesso e 2FA. |
| 2 | `clientes` | Dados pessoais do titular (CPF, nascimento, renda). |
| 3 | `enderecos` | Endereço vinculado ao cliente. |
| 4 | `contas` | Agência, número, tipo e status da conta. |
| 5 | `lancamentos` | Razão contábil: débitos e créditos (núcleo do saldo). |
| 6 | `transacoes` | Agrupa lançamentos de uma mesma operação. |
| 7 | `chaves_pix` | Chaves Pix registradas pelo cliente. |
| 8 | `favorecidos` | Contatos salvos para transferência. |
| 9 | `cartoes` | Cartões virtuais e físicos. |
| 10 | `faturas` | Ciclos de fatura do cartão. |
| 11 | `boletos` | Boletos pagos ou emitidos. |
| 12 | `cobrancas` | Cobranças Pix (QR Code) emitidas. |
| 13 | `agendamentos` | Transferências e pagamentos futuros. |
| 14 | `notificacoes` | Mensagens enviadas ao cliente. |
| 15 | `chamados` | Tickets de suporte. |
| 16 | `auditoria` | Trilha imutável de operações sensíveis. |

## 10. Premissas

- A equipe dispõe de aproximadamente 10 h semanais somadas para o projeto.
- Todas as integrações externas serão simuladas por *mocks* internos.
- O ambiente de produção será o plano gratuito do Render, com as limitações descritas.
- Os valores monetários serão armazenados em `numeric(18,2)` e manipulados em centavos na aplicação.
- O idioma do sistema e da documentação é o português do Brasil.

## 11. Restrições

- **Prazo:** o semestre letivo, com marcos fixos nos checkpoints 1 a 4.
- **Custo:** orçamento zero — apenas serviços com plano gratuito.
- **Equipe:** máximo de 5 alunos, sem dedicação exclusiva.
- **Tecnologia:** stack definida pelo grupo (Laravel, Flutter, PostgreSQL, Render).
- **Código legado:** proibido reaproveitar código pronto; todo o desenvolvimento ocorre no semestre.

## 12. Riscos identificados

| ID | Risco | Prob. | Impacto | Mitigação |
|---|---|---|---|---|
| R1 | Expiração do PostgreSQL gratuito no Render (30 dias) | Alta | Alto | Dump semanal + migrations/seeders versionados; recriação documentada. |
| R2 | Curva de aprendizado do Flutter | Média | Alto | Começar o app no CP2 com telas simples; priorizar login, saldo e extrato. |
| R3 | Hibernação do serviço gratuito na apresentação | Alta | Médio | "Aquecer" a aplicação 5 min antes; ter vídeo/backup local. |
| R4 | Erros de arredondamento em valores monetários | Média | Alto | `numeric(18,2)` no banco, inteiros em centavos na aplicação, testes unitários. |
| R5 | Commits concentrados no fim da semana | Alta | Médio | Definir "dia de commit" fixo do grupo e revisão por PR. |
| R6 | Desbalanceamento de carga entre integrantes | Média | Médio | Quadro Kanban com responsável por tarefa e revisão semanal. |
| R7 | Escopo crescer além do prazo | Média | Alto | Escopo congelado após o CP2; novas ideias vão para o backlog "pós-entrega". |

## 13. Critérios de aceitação do produto

O Fluxo será considerado entregue quando:

1. Um visitante conseguir se cadastrar, ter a conta aprovada e efetuar login com 2FA.
2. Um cliente conseguir depositar, transferir por Pix e pagar um boleto simulado, com saldo e extrato refletindo corretamente as operações.
3. O cliente conseguir emitir um cartão virtual, ver a fatura e contestar um lançamento.
4. O administrador conseguir bloquear uma conta e o auditor visualizar o registro na trilha de auditoria.
5. As mesmas operações essenciais estiverem disponíveis nas duas interfaces (web e mobile).
6. O sistema estiver publicamente acessível na nuvem.
7. O modelo de dados contiver no mínimo 8 entidades implementadas.
8. O manual do usuário estiver entregue.

## 14. Marcos do projeto

| Marco | Data | Entrega |
|---|---|---|
| Checkpoint 1 | 03/09 | Escopo, requisitos iniciais, diagramas de casos de uso, quadro de gestão, LibreProject e GitHub ativos. |
| Checkpoint 2 | 17/09 | Requisitos finais, protótipo Figma, documentos de casos de uso, DER e diagrama de classes. |
| Checkpoint 3 | 22/10 | Backend REST com TDD, implantado na nuvem, diagramas de interação. |
| Checkpoint 4 | 03/12 | Sistema web e mobile completos na nuvem, manual do usuário, entregas finais. |
