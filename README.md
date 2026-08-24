O Fluxo é um banco digital moderno, projetado para oferecer uma experiência financeira ágil, segura e intuitiva através de uma aplicação web (Internet Banking) e um aplicativo móvel (Android e iOS).

🛠️ Stack Tecnológica
O projeto utiliza uma arquitetura separada em monorepo, combinando ferramentas robustas para o ecossistema web e mobile:

Backend & Web Frontend: Laravel (PHP 8+)

Função: Gerencia a lógica de negócios, rotas, autenticação de usuários, conexão com o banco de dados e a renderização das telas do Internet Banking web.

Web Styling: Tailwind CSS

Função: Framework utilitário de CSS utilizado para estilizar a interface web de forma rápida, moderna e totalmente responsiva.

Mobile Frontend: Flutter (Dart)

Função: Framework multiplataforma utilizado para criar o aplicativo nativo para Android e iOS a partir de uma única base de código, integrando-se diretamente com as APIs do backend.

Banco de Dados: PostgreSQL

Função: Sistema de gerenciamento de banco de dados relacional essencial para garantir a consistência estrita (propriedades ACID) de saldos, extratos e transações financeiras.

Ambiente de Desenvolvimento: GitHub Codespaces / Android Studio

Função: O Codespaces é utilizado para o desenvolvimento em nuvem do backend e estrutura do repositório, enquanto o Android Studio serve para compilar, emular e testar o aplicativo mobile.

📁 Estrutura de Pastas do Repositório
O repositório é dividido em duas frentes principais (backend e mobile), organizadas da seguinte forma:

🌐 Pasta backend/ (Laravel / PHP)
Concentra todo o código do servidor, banco de dados e a interface web.

app/: O núcleo da aplicação. Contém os Controllers (responsáveis por receber as requisições, processar regras e retornar respostas) e os Models (que mapeiam e interagem com as tabelas do banco de dados).

bootstrap/: Contém arquivos essenciais para a inicialização do framework Laravel e otimização do carregamento do sistema.

config/: Arquivos de configuração global da aplicação, como conexões de banco de dados, serviços de e-mail e parâmetros de segurança.

database/: Armazena as migrations (scripts para criar e alterar tabelas no PostgreSQL com segurança) e seeders (para preencher o banco com dados iniciais ou de teste).

public/: O ponto de entrada público da aplicação web. Contém os arquivos acessíveis diretamente pelo navegador (como imagens, scripts compilados e o arquivo principal index.php).

resources/: Contém os arquivos de interface que ainda serão processados, incluindo as views em Blade (o sistema de templates do Laravel) e os arquivos de estilo com Tailwind CSS.

routes/: Define os endereços da aplicação. Separa as rotas que entregam páginas web (web.php) das rotas de API (api.php) que fornecem dados para o aplicativo mobile.

storage/: Utilizado pelo framework para gravação de arquivos temporários, logs de erros do sistema, cache e arquivos enviados pelos usuários.

tests/: Espaço reservado para a criação de testes automatizados, garantindo a estabilidade e a qualidade do código do backend.

📱 Pasta mobile/ (Flutter / Dart)
Concentra todo o código-fonte voltado para a experiência do usuário nos dispositivos móveis.

lib/: A pasta principal onde reside todo o código em linguagem Dart do aplicativo.

screens/: Onde são desenvolvidas as telas visuais do usuário (ex: Tela de Login, Tela de Início/Dashboard, Extrato, Tela de Transferência Pix).

services/: Concentra a lógica de comunicação externa, como as requisições HTTP para consumir as rotas da API desenvolvida no Laravel.

android/: Contém os arquivos nativos necessários para que o Flutter seja compilado e executado em dispositivos Android (totalmente compatível para manipulação via Android Studio).

ios/: Contém os arquivos de configuração nativos e específicos para a compilação do aplicativo em dispositivos da Apple (iOS).

test/: Destinada a testes unitários e de widget para validar o comportamento das telas e funções do aplicativo mobile.
