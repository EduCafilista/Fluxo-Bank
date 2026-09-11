# 4.2 - Levantamento de Requisitos (Inicial)

**Projeto:** Fluxo — Sistema Bancário Digital

> **Foco Estratégico: Segurança e Auditoria Universal**
> Conforme solicitado, a arquitetura do Fluxo incorpora um **motor de logging detalhado**. O objetivo é garantir que *nenhuma* ação ocorra sem rastreamento absoluto no ecossistema (backend Laravel e banco PostgreSQL). Os requisitos de log foram injetados como prioridade **Must**.

## 1. Requisitos Funcionais (RF)

| ID | Descrição do Requisito | Prioridade |
| :--- | :--- | :---: |
| **RF-SEG01** | **Log de Requisições Global (Middleware):** O sistema deve implementar um middleware universal no Laravel que registre 100% das requisições HTTP da API e Web, gravando: IP do cliente, User-Agent, Rota acessada, Payload (ofuscando dados sensíveis como senhas e CVV), Data/Hora exata e ID do usuário autenticado. | `Must` |
| **RF-SEG02** | **Auditoria de Banco de Dados (Triggers):** O banco de dados PostgreSQL deve utilizar triggers em todas as tabelas de domínio para capturar o estado anterior (OLD) e o novo estado (NEW) de qualquer operação de INSERT, UPDATE ou DELETE, armazenando o diff em uma coluna estruturada (JSONB). | `Must` |
| **RF-SEG03** | **Imutabilidade da Trilha:** Os registros na trilha de auditoria devem ser do tipo *append-only*. O sistema deve impedir, via restrições e regras no banco de dados, qualquer deleção ou alteração desses registros. | `Must` |
| **RF01** | Permitir que um visitante se cadastre informando dados básicos e validando o CPF matematicamente. O cadastro passa por KYC simulado e automatizado para aprovação de conta. | `Must` |
| **RF02** | Autenticação robusta de usuários internos e clientes utilizando e-mail, senha (hash) e suporte a Verificação em Dois Fatores (2FA) por código de 6 dígitos temporário. | `Must` |
| **RF03** | Cálculo dinâmico e preciso do saldo atual da conta derivado exclusivamente da soma do histórico de lançamentos contábeis (partidas dobradas). | `Must` |
| **RF04** | Cadastro de chaves Pix (CPF, e-mail, telefone, aleatória) com validação de duplicidade rigorosa na base. | `Must` |
| **RF05** | Realização de transferências (Pix e internas) com exibição de tela de confirmação prévia dos dados do favorecido e submissão à análise de risco antes da efetivação. | `Must` |
| **RF06** | Agendamento de transferências/pagamentos para datas futuras, gerando comprovantes únicos em PDF e disparando notificações ao usuário no momento da execução. | `Should` |
| **RF07** | Gestão completa do ciclo de vida de cartões (emissão imediata virtual, solicitação física, bloqueio/desbloqueio e ajuste de limites baseados em tetos pré-aprovados). | `Must` |
| **RF08** | Pagamento de cobranças via QR Code Pix (payload EMV) e boletos por código de barras/linha digitável. | `Must` |
| **RF09** | Backoffice gerencial contendo gestão de perfis (RBAC), bloqueio de contas suspeitas, atendimento de chamados de suporte técnico e consulta detalhada às trilhas de auditoria geradas. | `Must` |

## 2. Requisitos Não Funcionais (RNF)

| Categoria | Restrição / Métrica de Qualidade |
| :--- | :--- |
| **Desempenho** | A interface e API devem responder operações de leitura em até 2 segundos (percentil 95). As operações de escrita e log devem funcionar de maneira assíncrona (filas) para não penalizar a experiência do usuário. |
| **Segurança** | Comunicação estritamente via HTTPS (TLS 1.2+). Senhas armazenadas com bcrypt (custo >= 12). Proteção nativa contra OWASP Top 10 (CSRF, SQLi via Eloquent). Sessões inativas derrubadas após 15 minutos e rate limiting em APIs expostas. |
| **Confiabilidade** | Movimentações financeiras e logs operacionais executados dentro de Transações ACID rigorosas. Uso do tipo de dado `numeric(18,2)` no PostgreSQL para evitar anomalias de arredondamento financeiro. |
| **LGPD / Privacidade** | Máscara automática (ofuscação) de dados sensíveis na interface (como CPF e número do cartão). O sistema deve fornecer mecanismos de anonimização caso o encerramento da conta seja solicitado, mantendo o balanço contábil íntegro. |
| **Stack Técnica** | Frontend: Blade/Tailwind (Web), Flutter (Mobile). Backend: Laravel (PHP). Banco: PostgreSQL 16. Implantação e versão hospedados em nuvem. |
