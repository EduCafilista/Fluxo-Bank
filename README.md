# Fluxo - Banco Digital

O **Fluxo** é um banco digital moderno e intuitivo, projetado para oferecer uma experiência financeira ágil, segura e sem burocracia, contando com suporte tanto para aplicações web (Internet Banking) quanto para dispositivos móveis (Android e iOS).

---

## 🛠️ Stack Tecnológica

O projeto utiliza uma arquitetura em monorepo, separando claramente as responsabilidades entre servidor, interface web e aplicativo móvel:

*   **Backend & Web Frontend:** **Laravel (PHP 8+)**[cite: 3]
    *   *Função:* Gerencia a lógica de negócios, rotas, autenticação de usuários via API/sessão, conexão com o banco de dados e a renderização das telas do Internet Banking web (utilizando Blade e Tailwind CSS)[cite: 3].
*   **Web Styling:** **Tailwind CSS**[cite: 3]
    *   *Função:* Framework utilitário de CSS para estilização rápida, moderna e responsiva da interface web[cite: 3].
*   **Mobile Frontend:** **React Native com Expo**
    *   *Função:* Framework em JavaScript/TypeScript para criação do aplicativo multiplataforma. O Expo gerencia a configuração unificada (`app.json`) e o comando `expo prebuild` gera de forma automatizada as pastas nativas (`android/` e `ios/`) para compilação.
*   **Banco de Dados:** **PostgreSQL**[cite: 3]
    *   *Função:* Sistema de gerenciamento de banco de dados relacional essencial para garantir a consistência estrita (propriedades ACID) de saldos, extratos e transações financeiras[cite: 3].
*   **Ambiente de Desenvolvimento:** **GitHub Codespaces / Android Studio**[cite: 3]
    *   *Função:* O Codespaces é utilizado para o desenvolvimento em nuvem do repositório, enquanto o Android Studio serve para emular e testar o build nativo do aplicativo mobile[cite: 3].

---

## 📁 Estrutura de Pastas do Repositório

O repositório está organizado nas seguintes pastas principais:

### 🌐 Pasta `backend/` (Laravel-PHP)
Concentra todo o código do servidor, banco de dados e a interface web[cite: 3].

*   `app/`: Núcleo da aplicação (Controllers para regras de negócio e Models para interação com o banco)[cite: 3].
*   `bootstrap/`: Arquivos de inicialização e otimização do framework[cite: 3].
*   `config/`: Arquivos de configuração global (banco de dados, serviços, segurança)[cite: 3].
*   `database/`: Migrations (criação e alteração de tabelas no PostgreSQL) e Seeders (dados de teste)[cite: 3].
*   `public/`: Ponto de entrada público da aplicação web (`index.php` e assets públicos)[cite: 3].
*   `resources/`: Views em Blade (templates HTML) e arquivos de estilo com Tailwind CSS[cite: 3].
*   `routes/`: Definição de rotas web (`web.php`) e rotas de API (`api.php`)[cite: 3].
*   `storage/`: Logs de erros, arquivos temporários, cache e uploads[cite: 3].
*   `tests/`: Testes automatizados do sistema[cite: 3].

### 📱 Pasta `mobile/` (React Native / Expo)
Concentra todo o código-fonte voltado para a experiência do usuário nos dispositivos móveis utilizando Expo.

*   `app.json`: Arquivo central de configuração do Expo (metadados, ícones, splash screen e parâmetros de build nativo).
*   `app/` ou `src/`: Código-fonte em JavaScript/TypeScript contendo as telas (Login, Dashboard, Extrato, Transferências) e componentes de interface.
*   `package.json`: Gerenciador de dependências e bibliotecas JavaScript do aplicativo.
*   *Nota sobre pastas nativas:* As pastas `android/` e `ios/` ficam ocultas por padrão e são geradas/atualizadas sob demanda através do comando `npx expo prebuild`.
