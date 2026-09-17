# 4.1 - Definição do Escopo do Projeto

**Projeto:** Fluxo — Sistema Bancário Digital

> **Revisão desta versão:** incorporados os itens definidos na apresentação — arquitetura **MVC**, emissão de **token de autenticação** no back-end, cadastro de **Pessoa Física e Pessoa Jurídica**, estrutura de contas (**Corrente, Poupança, Salário e PJ**), estrutura de cartões (**Físico/Virtual** × **Débito/Crédito**), **trilha de auditoria** para toda operação, **mecanismos de antifraude** e **geração de QR Code** para recebimento de Pix. Nada do escopo original foi removido — apenas detalhado.

## 1. Declaração do Problema e Objetivos
O mercado brasileiro de serviços financeiros exige operações ágeis, transparentes e rastreáveis. O sistema financeiro atual ainda sofre com burocracias em aprovações, opacidade em tarifas e ausência de uma experiência unificada que também ofereça trilha de auditoria transparente para o cliente e para a equipe interna.

O **Fluxo** visa construir um sistema bancário digital acessível via web e aplicativo móvel, garantindo a gestão autônoma de contas, movimentações financeiras, controle de limites e emissão de cartões.

## 2. Escopo do Produto (O que está incluído)
O desenvolvimento contempla quatro grandes blocos estruturais que operam de forma integrada, com ênfase na rastreabilidade universal e gestão financeira:

### Módulo de Acesso e Gestão de Conta
*   Cadastro 100% digital com simulação de KYC (Know Your Customer) e aprovação pelo backoffice.
*   **Cadastro especializado por tipo de cliente:** Pessoa Física (CPF, validado matematicamente) e Pessoa Jurídica (CNPJ, validado matematicamente, com razão social e representante legal). *(revisão)*
*   Autenticação robusta (e-mail/senha) e Verificação em Dois Fatores (2FA) configurável pelo usuário.
*   Gestão completa de dados cadastrais e encerramento de conta (condicionado a saldo zerado).
*   Bloqueio preventivo da própria conta pelo cliente, além do bloqueio por fraude feito pelo backoffice. *(revisão)*

### Módulo de Movimentações e Pagamentos
*   Core bancário construído sob o princípio de **partidas dobradas**.
*   **Estrutura de contas especializadas:** Conta Corrente, Conta Poupança, Conta Salário (associáveis a clientes Pessoa Física) e Conta PJ (associável a clientes Pessoa Jurídica). *(revisão)*
*   Operações de transferência interna e simulação de Pix (chaves CPF, celular, e-mail, aleatória), com verificação de saldo disponível antes de qualquer transferência. *(revisão)*
*   **Geração de QR Code (payload EMV)** para recebimento de cobranças via Pix. *(revisão)*
*   **Mecanismos de antifraude:** limites de valor por transação e diários (com redução em horário noturno), limite de quantidade de transações por dia, aviso de viagem e verificação de localização divergente. *(revisão)*
*   Pagamento de boletos por código de barras/linha digitável e cobranças via QR Code EMV.
*   Geração de comprovantes detalhados e extratos categorizados.
*   Agendamentos de pagamentos e transferências recorrentes.

### Módulo de Cartões e Assinaturas
*   **Estrutura de cartões especializada:** cartão de **Débito** e cartão de **Crédito**, cada um existindo nas formas **Física** e **Virtual** — o cartão virtual é sempre gerado a partir de um cartão físico ativo; o físico pode existir e ser usado sem que um virtual tenha sido gerado. *(revisão)*
*   Bloqueio/desbloqueio dinâmico dos cartões.
*   Ajuste de limites pelo próprio aplicativo, dentro de um teto pré-aprovado.
*   Contratação e gerenciamento de planos/assinaturas com faturamento proporcional.

### Módulo Administrativo e Backoffice
*   Painel web administrativo para gestão de usuários, limites e aprovações manuais.
*   Gestão de chamados de suporte técnico integrados ao perfil do cliente.
*   **Trilha de auditoria (logs) para todo tipo de operação do aplicativo** — acesso (login/logout), consulta (leitura de saldo/extrato/fatura) e movimentação (Pix, transferências, pagamentos, cartões) — não somente escritas financeiras. *(revisão)*

### Arquitetura Técnica Adotada
O sistema será desenvolvido em uma arquitetura moderna compreendendo:
*   **Padrão arquitetural:** **MVC (Model-View-Controller)** em todo o back-end Laravel — Models (Eloquent), Controllers (validação e orquestração) e Views/Resources (Blade para a web, JSON Resources para a API). *(revisão)*
*   **Backend:** Laravel, servindo API REST e lógica de negócios, com emissão de **token de autenticação** (Laravel Sanctum) para sessão do cliente e dos apps. *(revisão)*
*   **Frontend Web:** Blade + Tailwind CSS.
*   **Mobile:** Flutter para aplicações multiplataforma.
*   **Banco de Dados:** PostgreSQL 16 (focado em tipagem monetária precisa e constraints ACID).

## 3. Fora de Escopo (O que NÃO será feito)
*   **Operações financeiras reais:** Não haverá integração verdadeira com BACEN/SPI ou adquirentes (Visa/Mastercard); o dinheiro e as liquidações serão simulados.
*   **Módulos de Crédito/Investimento:** Cheque especial, empréstimos, CDBs, e poupança (como produto de investimento) estão excluídos desta versão.
*   **Open Finance e Biometria:** Não haverá integração de compartilhamento de dados interbancários nem documentoscopia facial via IA (a validação será apenas simulada e manual).
*   **Publicação em Lojas Oficiais:** Não haverá build direto nas stores (Google Play/App Store). A distribuição será feita em ambiente de teste ou arquivo direto.

**Registro de alterações**

| Versão | Data | Alteração |
|---|---|---|
| 1.0 | 31/08/2026 | Versão inicial |
| 2.0 | 10/09/2026 | Adicionados: arquitetura MVC, token de autenticação, PF/PJ, contas especializadas, cartões especializados (físico/virtual, débito/crédito), QR Code Pix, antifraude detalhado e log de todo tipo de operação |
