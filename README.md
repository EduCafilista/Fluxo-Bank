# Fluxo Bank — Plataforma Bancária Digital

O **Fluxo Bank** é um projeto acadêmico de plataforma bancária digital, com API REST em Laravel, persistência PostgreSQL e implantação de demonstração no Render. O backend contempla cadastro e autenticação, gestão de contas, transferências, funcionalidades Pix, cartões, agendamentos, assinaturas e auditoria.

> **Importante:** operações financeiras simuladas não representam integração bancária real, liquidação no Sistema de Pagamentos Instantâneos (SPI) nem movimentação de dinheiro verdadeiro. A instância do Render descrita aqui é destinada a testes.

## 1. Organização do repositório

```text
Fluxo-Bank/
├── Documentacao/
│   ├── Diagramas/                 
│   ├── CasosDeUso.md
│   ├── Escopo-Projeto.md
│   ├── Levantamento-Requisitos-Final.md
│   ├── PrototipacaoFigma.md
│   └── QuadroDeGestao.md
├── Projeto/
│   ├── backend/
│   │   ├── app/
│   │   │   ├── Http/Controllers/
│   │   │   ├── Http/Middleware/
│   │   │   ├── Models/
│   │   │   └── Services/
│   │   ├── bootstrap/
│   │   ├── config/
│   │   ├── database/migrations/
│   │   ├── docs/
│   │   ├── routes/api.php
│   │   ├── routes/web.php
│   │   ├── tests/
│   │   ├── Dockerfile
│   │   └── docker-compose.yml
│   └── render.yaml
└── README.md
```

A documentação dos diagramas está em [`Documentacao/Diagramas`](Documentacao/Diagramas). Os diagramas de classes incluem os modelos de domínio, as interações do backend e os modelos Eloquent. As atualizações relativas a depósito e Pix simulados são descritas na documentação de diagramas atualizada, quando incorporada à branch.

## 2. Tecnologias

- **Backend:** PHP 8.3+ e Laravel 13.
- **Autenticação:** Laravel Sanctum; autenticação em dois fatores (2FA) em fluxos previstos pela aplicação.
- **Banco de dados:** PostgreSQL, com migrations Laravel e modelos Eloquent.
- **Infraestrutura:** Docker, Docker Compose e Render.
- **Testes:** PHPUnit / testes Laravel nas pastas `tests/Unit` e `tests/Feature`.

O Dockerfile do backend utiliza a imagem **PHP 8.4 CLI**. Não confunda a versão mínima declarada no `composer.json` com a versão efetivamente utilizada pela imagem de implantação.

## 3. Funcionalidades principais

| Módulo | Exemplos de rotas da API |
| --- | --- |
| Saúde | `GET /api/v1/health` |
| Cadastro e acesso | `POST /api/v1/cadastro`, `POST /api/v1/login`, `GET /api/v1/eu` |
| Conta e extrato | `GET /api/v1/conta`, `GET /api/v1/conta/extrato` |
| Transações | `GET /api/v1/transacoes`, `POST /api/v1/transferencias` |
| Pix | Chaves, cobranças, recebimentos simulados e consulta de limites |
| Demais módulos | Cartões, boletos, agendamentos, assinaturas, suporte e backoffice |

### Prefixo `/api/v1`

O prefixo público depende da configuração de rotas em `Projeto/backend/bootstrap/app.php`. Se o arquivo `routes/api.php` for registrado pelo Laravel com o prefixo padrão `api`, as rotas serão expostas em `/api/...`, e **não** em `/api/v1/...`. Para adotar `/api/v1`, configure `apiPrefix: 'api/v1'` em `withRouting(...)` e confirme com:

```bash
php artisan route:list --path=api/v1
```

A documentação e o painel de testes pressupõem o prefixo `/api/v1`. Confira o prefixo efetivo antes de publicar a aplicação.

## 4. Depósitos e Pix simulados

Depósito e recebimento Pix são **operações diferentes** e devem permanecer identificáveis separadamente no histórico financeiro, mesmo quando utilizam serviços contábeis compartilhados.

Na implementação de demonstração preparada para o Render, o endpoint `POST /api/v1/depositos` é destinado a **depósitos simulados**, e `POST /api/v1/pix/recebimentos` a **recebimentos Pix simulados**. Esses endpoints só estarão disponíveis se os respectivos arquivos de controller, middleware e rotas da atualização de simulação tiverem sido incorporados ao backend implantado.

Para habilitar a simulação **apenas em um ambiente de demonstração isolado**, configure no serviço do Render:

```env
FLUXO_SIMULACOES_FINANCEIRAS=true
```

A configuração não substitui a autorização: as operações simuladas devem exigir credenciais administrativas de backoffice. Usuários comuns não devem poder creditar arbitrariamente as próprias contas. Na futura produção real, mantenha a simulação desativada e credite valores somente após confirmação confiável da operação externa.

Se a API retornar `403` com `Simulações financeiras desativadas.`, a rota foi alcançada, mas a configuração de simulação está bloqueando a operação. Se retornar `404`, confirme primeiro se o endpoint foi implantado e se o prefixo da API está correto.

## 5. Executar localmente com Docker (opcional)

A implantação principal de demonstração utiliza o Render, mas o repositório também contém configuração Docker Compose para execução local:

```bash
cd Projeto/backend
docker compose up --build -d
```

Confira as portas e serviços declarados no `docker-compose.yml` da sua versão. Para inspecionar rotas e executar testes dentro do contêiner da API:

```bash
docker compose exec api php artisan route:list
docker compose exec api php artisan test
```

Esses comandos exigem que o serviço Compose se chame `api`, como na configuração analisada. Para testes com PostgreSQL, configure previamente um banco de testes isolado.

## 6. Implantação no Render

O arquivo [`Projeto/render.yaml`](Projeto/render.yaml) define a infraestrutura do projeto. O serviço web utiliza o Dockerfile do backend e uma conexão PostgreSQL configurada por variáveis de ambiente.

Após publicar mudanças no backend, verifique:

1. Se o deploy foi concluído e a aplicação iniciou sem erro.
2. Se `GET /up` responde ao health check da aplicação.
3. Se `GET /api/v1/health` está registrado e acessível no prefixo esperado.
4. Se as migrations foram aplicadas no banco correto.
5. Se login, conta, extrato, transferência e idempotência funcionam com dados fictícios.
6. Se as simulações financeiras estão habilitadas **somente** no ambiente de demonstração e restritas a usuários autorizados.

**Agendamentos:** a existência de tarefas no `routes/console.php` não garante sua execução automática no Render. É necessário configurar um processo ou serviço que execute o scheduler Laravel periodicamente.

## 7. Testes e segurança

```bash
cd Projeto/backend
php artisan route:list
php artisan test
```

Para executar `php artisan test` fora do Docker, instale previamente as dependências Composer e configure o ambiente de testes. A quantidade de testes e assertions pode variar entre versões; consulte o resultado da suíte, em vez de depender de uma contagem fixa no README.

Cuidados importantes:

- Não publique senhas, URLs de banco com credenciais, tokens ou códigos 2FA.
- Use `Idempotency-Key` nas operações que exigem proteção contra repetição e valide concorrência no backend.
- Preserve transações atômicas e lançamentos contábeis consistentes.
- Restrinja funções de backoffice e simulação a usuários autorizados.
- Não confunda uma demonstração funcional com integração bancária real ou conformidade de produção certificada.

## 8. Documentação complementar

- [Diagramas UML e DER](Documentacao/Diagramas)
- [Escopo do projeto](Documentacao/Escopo-Projeto.md)
- [Requisitos](Documentacao/Levantamento-Requisitos-Final.md)
- [Casos de uso](Documentacao/CasosDeUso.md)
- [Documentação técnica do backend](Projeto/backend/docs)
- [Especificação OpenAPI](Projeto/backend/docs/openapi.yaml)

---

**Estado da documentação:** este README foi revisado para refletir a estrutura do repositório analisado e as alterações propostas para a demonstração no Render. As funcionalidades condicionadas à aplicação dos pacotes de correção devem ser confirmadas na branch e no deploy efetivamente utilizados.
