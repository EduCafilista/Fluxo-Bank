# Fluxo Bank

Backend Laravel com API REST, PostgreSQL 16 e interface web em Blade e Tailwind CSS. O projeto executa as operações bancárias em um ambiente acadêmico de simulação.

## Executar com Docker

Na pasta `backend`:

```sh
docker compose up --build -d
docker compose exec api php artisan fluxo:verificar
```

Abra [http://localhost:8000](http://localhost:8000). O Compose instala as dependências, compila os ativos, executa as migrations, carrega o catálogo e os dados de demonstração, e inicia o agendador.

| Perfil de demonstração | E-mail | Senha |
| --- | --- | --- |
| Administrador | `admin@fluxo.test` | `senha123` |
| Cliente | `ana@fluxo.test` | `senha123` |
| Cliente | `bruno@fluxo.test` | `senha123` |

O administrador encontra **Usuários** e **Contas e KYC** no menu. O cadastro público em `/acesso/cadastro` cria o cliente e a conta no mesmo fluxo usado na administração. Com `FLUXO_KYC_AUTOMATICO=false`, a conta fica em análise e pode ser aprovada no backoffice.

## Executar sem Docker

Use PHP 8.4 com PDO PostgreSQL, DOM e mbstring, Composer 2, Node.js 22 e PostgreSQL 16. Crie o banco e configure a conexão no `.env`.

```sh
cp .env.example .env
composer install
npm ci
npm run build
php artisan key:generate
php artisan migrate
php artisan db:seed
php artisan serve
```

Execute `php artisan schedule:work` em outro terminal para processar os agendamentos. Em servidor com cron, use `php artisan schedule:run` a cada minuto. Use `php artisan db:seed --class=DemoSeeder` em uma instalação de demonstração com KYC automático. Preserve a `APP_KEY` ao atualizar uma instalação já existente. Para 2FA por e-mail, configure o transporte SMTP e `FLUXO_2FA_EMAIL_ENTREGA=true`.

## Administração e API

- **ADMIN** cria, edita, remove e altera perfis. ADMIN também pode desbloquear contas, ajustar limites Pix e encerrar contas com saldo zero e sem agendamentos pendentes. Um administrador pode editar seus dados, com o próprio perfil protegido.
- **ATENDENTE** acompanha contas, decide o KYC, registra bloqueios por fraude, responde chamados e consulta a auditoria.
- **CLIENTE** controla a própria conta. Em Pagamentos pode ler uma imagem com QR Pix ou colar o código de uma cobrança Fluxo, revisar o favorecido e autorizar o pagamento.
- A exclusão do usuário revoga o acesso, anonimiza o cadastro, descarta as chaves Pix do titular e preserva o histórico contábil. O encerramento de uma conta bancária preserva o cadastro de acesso.
- A especificação HTTP está em [docs/openapi.yaml](docs/openapi.yaml) e é servida em `/api/v1/openapi.yaml`. A emissão dos tokens de acesso e renovação ocorre no login da API após a conclusão do 2FA, quando habilitado.

## Verificação e recuperação

Configure um banco de testes separado conforme `phpunit.xml` e execute `php artisan test`. A [matriz de requisitos](docs/REVISAO-REQUISITOS.md) registra o que foi implementado, as condições de execução e os pontos que exigem validação externa.

Para backup periódico do PostgreSQL, configure a execução semanal no ambiente que hospeda o banco e proteja o acesso ao arquivo. A restauração deve ser feita primeiro em banco separado.

```sh
docker compose exec -T db pg_dump -U fluxouser -Fc fluxodb > fluxo-backup.dump
docker compose exec -T db createdb -U fluxouser fluxo_restore
docker compose exec -T db pg_restore -U fluxouser --no-owner -d fluxo_restore < fluxo-backup.dump
docker compose exec -T db psql -U fluxouser -d fluxo_restore -c 'SELECT count(*) FROM contas'
```
