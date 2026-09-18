# 4.2 - Levantamento de Requisitos

**Projeto:** Fluxo — Sistema Bancário Digital

<<<<<<< HEAD
=======
> **Foco Estratégico: Segurança e Auditoria Universal**
> Conforme solicitado, a arquitetura do Fluxo incorpora um **motor de logging detalhado**. O objetivo é garantir que *nenhuma* ação ocorra sem rastreamento absoluto no ecossistema (backend Laravel e banco PostgreSQL). Os requisitos de log foram injetados como prioridade **Must**.*.

>>>>>>> 9548f8cc43a3dd1dcd9058bd20fff20ce3b96b2e
## 1. Requisitos Funcionais (RF)

| ID | Descrição do Requisito | Prioridade |
| :--- | :--- | :---: |
| **RF-SEG01** | **Log de Requisições Global (Middleware):** o sistema deve implementar um middleware universal no Laravel que registre 100% das requisições HTTP da API e Web, gravando: IP do cliente, User-Agent, Rota acessada, Payload (ofuscando dados sensíveis como senhas e CVV), Data/Hora exata e ID do usuário autenticado. | `Must` |
| **RF-SEG02** | **Auditoria de Banco de Dados (Triggers):** o banco de dados PostgreSQL deve utilizar triggers em todas as tabelas de domínio para capturar o estado anterior (OLD) e o novo estado (NEW) de qualquer operação de INSERT, UPDATE ou DELETE, armazenando o diff em uma coluna estruturada (JSONB). | `Must` |
| **RF-SEG03** | **Imutabilidade da Trilha:** os registros na trilha de auditoria devem ser do tipo *append-only*. O sistema deve impedir, via restrições e regras no banco de dados, qualquer deleção ou alteração desses registros. | `Must` |
| **RF-SEG04** *(novo)* | **Log para todo tipo de operação do aplicativo:** o motor de logging não se limita a escritas financeiras — deve registrar também entradas no app (login/logout), consultas de leitura (saldo, extrato, fatura, trilha) e movimentações, categorizando cada evento em `ACESSO`, `CONSULTA` ou `MOVIMENTACAO`. A estrutura de log **espelha** o esquema das tabelas de domínio: cada tabela sensível tem sua contraparte de auditoria, em vez de um log genérico único. | `Must` |
| **RF01** | Permitir que um visitante se cadastre informando dados básicos e validando o CPF matematicamente. O cadastro passa por KYC simulado e automatizado para aprovação de conta. | `Must` |
| **RF02** | Autenticação robusta de usuários internos e clientes utilizando e-mail, senha (hash) e suporte a Verificação em Dois Fatores (2FA) por código de 6 dígitos temporário. | `Must` |
| **RF03** | Cálculo dinâmico e preciso do saldo atual da conta derivado exclusivamente da soma do histórico de lançamentos contábeis (partidas dobradas). | `Must` |
| **RF04** | Cadastro de chaves Pix (CPF, e-mail, telefone, aleatória) com validação de duplicidade rigorosa na base. | `Must` |
| **RF05** | Realização de transferências (Pix e internas) com exibição de tela de confirmação prévia dos dados do favorecido e submissão à análise de risco antes da efetivação. | `Must` |
| **RF06** | Agendamento de transferências/pagamentos para datas futuras, gerando comprovantes únicos em PDF e disparando notificações ao usuário no momento da execução. | `Should` |
| **RF07** | Gestão completa do ciclo de vida de cartões (emissão física, geração do cartão virtual a partir do físico, bloqueio/desbloqueio e ajuste de limites baseados em tetos pré-aprovados). | `Must` |
| **RF08** | Pagamento de cobranças via QR Code Pix (payload EMV) e boletos por código de barras/linha digitável. | `Must` |
| **RF09** | Backoffice gerencial contendo gestão de perfis (RBAC), bloqueio de contas suspeitas, atendimento de chamados de suporte técnico e consulta detalhada às trilhas de auditoria geradas. | `Must` |
| **RF10** | **Especialização de clientes:** o sistema deve suportar clientes **Pessoa Física** (CPF, validado matematicamente) e **Pessoa Jurídica** (CNPJ, validado matematicamente, com razão social e representante legal), cada um com regras e dados cadastrais próprios. | `Must` |
| **RF11** | **Especialização de contas:** a conta deve se especializar em **Corrente**, **Poupança**, **Salário** (associáveis a cliente PF) e **PJ** (associável a cliente PJ), cada uma com regras de uso específicas. | `Must` |
| **RF12** | **Especialização de cartões:** o cartão se especializa em **Débito** e **Crédito**; cada um existe nas formas **Física** e **Virtual**. Um cartão virtual só pode ser gerado a partir de um cartão físico ativo — **não existe cartão virtual sem um físico correspondente** —, mas o cartão físico pode existir e ser utilizado sem que nenhum virtual tenha sido gerado. | `Must` |
| **RF13** | **Bloqueio de conta pelo cliente:** o próprio cliente pode ativar um bloqueio preventivo (autobloqueio) na própria conta, controlado por uma flag booleana. Enquanto ativa, nenhuma movimentação de débito é permitida. Distinto do bloqueio por fraude, feito pelo Administrador (RF09). | `Must` |
| **RF14** | **Limites configuráveis do Pix:** o cliente pode configurar, dentro de um teto máximo pré-aprovado pelo banco, o valor máximo por transação Pix e o limite diário (valor e quantidade de transações). | `Must` |
| **RF15** | **Geração de QR Code para recebimento de Pix:** o sistema deve gerar uma imagem de QR Code a partir do payload EMV de uma cobrança Pix, permitindo que qualquer pagador (cliente Fluxo ou externo) a leia e efetive o pagamento. | `Must` |
| **RF16** | **Arquitetura MVC:** o back-end deve seguir o padrão **Model-View-Controller** — Models (Eloquent, regra de persistência), Controllers (validação e orquestração da requisição) e Views/Resources (Blade para a web, API Resources/JSON para a API) — mantendo a lógica de negócio isolada em Services, conforme os componentes de domínio (ver `DiagramaComponentes.md`). | `Must` |
| **RF17** | **Token de autenticação no back-end:** toda sessão autenticada (app ou web) deve receber um token de acesso (Laravel Sanctum), emitido no login e validado em toda requisição subsequente à API, com expiração e revogação (logout). | `Must` |

## 2. Regras de Negócio (RN) — Antifraude e Especialização

| ID | Regra | Prioridade |
| :--- | :--- | :---: |
| **RN27** | **Saldo disponível no Pix:** toda transferência Pix verifica o saldo disponível (saldo contábil menos valores já reservados/pendentes) antes de ser submetida à análise de risco; saldo insuficiente bloqueia a operação imediatamente. | `Must` |
| **RN28** | **Limite de valor por transação:** o cliente define um teto de valor por transação Pix, respeitando o teto máximo aprovado pelo banco para o seu perfil. | `Must` |
| **RN29** | **Limite diário — valor e quantidade:** a soma das transações Pix do dia não pode ultrapassar o limite diário configurado, tampouco a quantidade máxima de transações diárias definida para o cliente. | `Must` |
| **RN30** | **Horário noturno:** entre 20h e 6h, os limites de valor por transação e diário do Pix são reduzidos automaticamente — mesmo padrão já usado na retirada (RN19). | `Must` |
| **RN31** | **Aviso de viagem:** o cliente pode registrar um período e uma ou mais localidades de viagem; transações realizadas dentro desse período/local não elevam o score de risco por localização. | `Must` |
| **RN32** | **Localização divergente:** uma transação Pix realizada em localização diferente do padrão do cliente, sem aviso de viagem ativo, eleva o score de risco do `MotorAntifraude` e pode exigir 2FA adicional ou reter a transação para confirmação manual. | `Must` |
| **RN33** | **Cartão virtual depende do físico:** não é possível gerar um cartão virtual sem um cartão físico ativo correspondente à mesma modalidade (débito ou crédito); o cartão físico existe e opera independentemente da existência de um virtual. | `Must` |
| **RN34** | **Bloqueio de conta pelo cliente:** enquanto a flag `bloqueada` da conta estiver ativa (acionada pelo próprio cliente), nenhuma movimentação de débito — Pix, transferência, pagamento, saque, fatura de cartão — é autorizada; apenas o próprio cliente ou o Administrador podem reverter o bloqueio. | `Must` |
| **RN35** | **Validação matemática de CPF/CNPJ:** tanto o dígito verificador do CPF (cliente PF) quanto o do CNPJ (cliente PJ) são conferidos localmente, antes de qualquer consulta ou gravação. | `Must` |
| **RN36** | **Payload do QR Code Pix:** o QR Code gerado segue o padrão EMV (chave, valor, identificador da cobrança, dados do recebedor) e é validado quanto à integridade antes de ser exibido ou reimpresso. | `Must` |

## 3. Requisitos Não Funcionais (RNF)

| Categoria | Restrição / Métrica de Qualidade |
| :--- | :--- |
| **Desempenho** | A interface e API devem responder operações de leitura em até 2 segundos (percentil 95). As operações de escrita e log devem funcionar de maneira assíncrona (filas) para não penalizar a experiência do usuário. |
| **Segurança** | Comunicação estritamente via HTTPS (TLS 1.2+). Senhas armazenadas com bcrypt (custo >= 12). Proteção nativa contra OWASP Top 10 (CSRF, SQLi via Eloquent). Sessões inativas derrubadas após 15 minutos e rate limiting em APIs expostas. |
| **Confiabilidade** | Movimentações financeiras e logs operacionais executados dentro de Transações ACID rigorosas. Uso do tipo de dado `numeric(18,2)` no PostgreSQL para evitar anomalias de arredondamento financeiro. |
| **LGPD / Privacidade** | Máscara automática (ofuscação) de dados sensíveis na interface (como CPF/CNPJ e número do cartão). O sistema deve fornecer mecanismos de anonimização caso o encerramento da conta seja solicitado, mantendo o balanço contábil íntegro. |
| **Arquitetura de Software** *(novo)* | Back-end estruturado em **MVC** (Models Eloquent, Controllers finos, Views/Resources), com regra de negócio isolada em Services; autenticação via **token** (Laravel Sanctum), emitido no login e exigido em toda rota protegida da API. |
| **Stack Técnica** | Frontend: Blade/Tailwind (Web), Flutter (Mobile). Backend: Laravel (PHP). Banco: PostgreSQL 16. Implantação e versão hospedados em nuvem. |
