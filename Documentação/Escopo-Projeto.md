# 4.1 - Definição do Escopo do Projeto

**Projeto:** Fluxo — Sistema Bancário Digital

## 1. Declaração do Problema e Objetivos
O mercado brasileiro de serviços financeiros exige operações ágeis, transparentes e rastreáveis. O sistema financeiro atual ainda sofre com burocracias em aprovações, opacidade em tarifas e ausência de uma experiência unificada que também ofereça trilha de auditoria transparente para o cliente e para a equipe interna.

O **Fluxo** visa construir um sistema bancário digital acessível via web e aplicativo móvel, garantindo a gestão autônoma de contas, movimentações financeiras, controle de limites e emissão de cartões.

## 2. Escopo do Produto (O que está incluído)
O desenvolvimento contempla quatro grandes blocos estruturais que operam de forma integrada, com ênfase na rastreabilidade universal e gestão financeira:

### Módulo de Acesso e Gestão de Conta
*   Cadastro 100% digital com simulação de KYC (Know Your Customer) e aprovação pelo backoffice.
*   Autenticação robusta (e-mail/senha) e Verificação em Dois Fatores (2FA) configurável pelo usuário.
*   Gestão completa de dados cadastrais e encerramento de conta (condicionado a saldo zerado).

### Módulo de Movimentações e Pagamentos
*   Core bancário construído sob o princípio de **partidas dobradas**.
*   Operações de transferência interna e simulação de Pix (chaves CPF, celular, e-mail, aleatória).
*   Pagamento de boletos por código de barras/linha digitável e cobranças via QR Code EMV.
*   Geração de comprovantes detalhados e extratos categorizados.
*   Agendamentos de pagamentos e transferências recorrentes.

### Módulo de Cartões e Assinaturas
*   Emissão de cartões virtuais e físicos, com bloqueio/desbloqueio dinâmico.
*   Ajuste de limites pelo próprio aplicativo, dentro de um teto pré-aprovado.
*   Contratação e gerenciamento de planos/assinaturas com faturamento proporcional.

### Módulo Administrativo e Backoffice
*   Painel web administrativo para gestão de usuários, limites e aprovações manuais.
*   Gestão de chamados de suporte técnico integrados ao perfil do cliente.

### Arquitetura Técnica Adotada
O sistema será desenvolvido em uma arquitetura moderna compreendendo:
*   **Backend:** Laravel, servindo API REST e lógica de negócios.
*   **Frontend Web:** Blade + Tailwind CSS.
*   **Mobile:** Flutter para aplicações multiplataforma.
*   **Banco de Dados:** PostgreSQL 16 (focado em tipagem monetária precisa e constraints ACID).

## 3. Fora de Escopo (O que NÃO será feito)
*   **Operações financeiras reais:** Não haverá integração verdadeira com BACEN/SPI ou adquirentes (Visa/Mastercard); o dinheiro e as liquidações serão simulados.
*   **Módulos de Crédito/Investimento:** Cheque especial, empréstimos, CDBs, e poupança estão excluídos desta versão.
*   **Open Finance e Biometria:** Não haverá integração de compartilhamento de dados interbancários nem documentoscopia facial via IA (a validação será apenas simulada e manual).
*   **Publicação em Lojas Oficiais:** Não haverá build direto nas stores (Google Play/App Store). A distribuição será feita em ambiente de teste ou arquivo direto.
