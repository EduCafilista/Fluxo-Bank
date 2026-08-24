# 🚀 Fluxo - Banco Digital

O **Fluxo** é um banco digital moderno e intuitivo, projetado para oferecer uma experiência financeira ágil, segura e sem burocracia, contando com suporte tanto para aplicações web (Internet Banking) quanto para dispositivos móveis (Android e iOS).

---

## 🛠️ Stack Tecnológica

O projeto utiliza uma arquitetura em monorepo, separando claramente as responsabilidades entre servidor, interface web e aplicativo móvel:

*   **Backend & Web Frontend:** **Laravel (PHP 8+)**
    *   *Função:* Gerencia a lógica de negócios, rotas, autenticação de usuários, conexão com o banco de dados e a renderização das telas do Internet Banking web.
*   **Web Styling:** **Tailwind CSS**
    *   *Função:* Framework utilitário de CSS para estilização rápida, moderna e responsiva da interface web.
*   **Mobile Frontend:** **Flutter (Dart)**
    *   *Função:* Framework multiplataforma utilizado para criar o aplicativo nativo para Android e iOS a partir de uma única base de código, integrando-se diretamente com as APIs do backend.
*   **Banco de Dados:** **PostgreSQL**
    *   *Função:* Sistema de gerenciamento de banco de dados relacional essencial para garantir a consistência estrita (propriedades ACID) de saldos, extratos e transações financeiras.
*   **Ambiente de Desenvolvimento:** **GitHub Codespaces / Android Studio**
    *   *Função:* O Codespaces é utilizado para o desenvolvimento em nuvem, enquanto o Android Studio serve para compilar, emular e testar o aplicativo mobile.

---

## 📁 Estrutura de Pastas do Repositório

O repositório está organizado nas seguintes pastas principais:

### 🌐 Pasta `backend/` (Laravel-PHP)
Concentra todo o código do servidor, banco de dados e a interface web.

*   `app/`: Núcleo da aplicação (Controllers para regras de negócio e Models para interação com o banco).
*   `bootstrap/`: Arquivos de inicialização e otimização do framework.
*   `config/`: Arquivos de configuração global (banco de dados, serviços, segurança).
*   `database/`: Migrations (criação e alteração de tabelas no PostgreSQL) e Seeders (dados de teste).
*   `public/`: Ponto de entrada público da aplicação web (`index.php` e assets públicos).
*   `resources/`: Views em Blade (templates HTML) e arquivos de estilo com Tailwind CSS.
*   `routes/`: Definição de rotas web (`web.php`) e rotas de API (`api.php`).
*   `storage/`: Logs de erros, arquivos temporários, cache e uploads.
*   `tests/`: Testes automatizados do sistema.

### 📱 Pasta `mobile/` (Flutter-Dart)
Concentra todo o código-fonte voltado para a experiência do usuário nos dispositivos móveis.

*   `lib/`: Código-fonte principal em Dart.
    *   `screens/`: Telas visuais do aplicativo (Login, Dashboard, Extrato, Transferências).
    *   `services/`: Lógica de comunicação HTTP com o backend.
*   `android/`: Arquivos nativos para compilação em Android (compatível com Android Studio).
*   `ios/`: Arquivos de configuração nativos para dispositivos Apple (iOS).
*   `test/`: Testes unitários e de widget para validação do app mobile.