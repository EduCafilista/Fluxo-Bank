# Fluxo — Backend (versão simples em Laravel)

Este README explica **como organizar o backend do Fluxo da forma mais simples possível**, o que o projeto faz nesta etapa, quais arquivos foram criados ou alterados e o que você precisa fazer no repositório. A parte mobile (Expo) está **fora** desta etapa.

> Base: documentação do projeto (escopo, requisitos, casos de uso, diagramas) e o repositório `EduCafilista/Fluxo-Bank`, branch `main`.

---

## 1. O que é o projeto

O **Fluxo** é um banco digital, acessível pelo navegador e pelo aplicativo. O escopo completo é grande: cadastro com KYC, 2FA, Pix, boletos, cartões, assinaturas, antifraude, backoffice e auditoria.

Esta etapa implementa **o menor corte que funciona de ponta a ponta**:

| Funcionalidade | Nesta etapa |
|---|---|
| Login com token (Laravel Sanctum) | ✅ |
| Consultar saldo e últimos 50 lançamentos | ✅ |
| Transferência entre contas, com verificação de saldo | ✅ |
| Logout (encerra o token) | ✅ |
| Cadastro com KYC, CPF/CNPJ, 2FA, Pix, boletos, cartões, assinaturas, backoffice, auditoria | ❌ próximas etapas |
| Aplicativo mobile | ❌ outra etapa |

Regras de negócio já valendo nesta etapa, vindas da documentação:

- **O saldo nunca é guardado num campo.** Ele é a soma dos lançamentos (RF03).
- **Toda transferência gera dois lançamentos**, um débito na origem e um crédito no destino, dentro de uma transação de banco. Ou gravam os dois, ou nenhum (partidas dobradas).
- **Valores são inteiros em centavos** (`valor_centavos`). Isso evita erro de arredondamento.
- **O controller não tem regra de negócio.** Ele valida a entrada e chama o Service (RF16, MVC).
- **Rotas protegidas exigem token** no header `Authorization: Bearer` (RF17).
- **O extrato mostra no máximo 50 registros** (RNF05).

---

## 2. Estrutura de pastas

O Laravel já traz quase tudo pronto. A regra é: **use o esqueleto padrão e acrescente só o necessário**. Não crie pastas à toa.

```
Projeto/
├── README.md                        ← este arquivo
├── backend/                         ← o Laravel inteiro
│   ├── app/
│   │   ├── Http/Controllers/        ← recebem a requisição e devolvem JSON
│   │   │   ├── AuthController.php       (novo)  login e logout
│   │   │   ├── ContaController.php      (novo)  saldo e extrato
│   │   │   └── TransacaoController.php  (novo)  transferência
│   │   ├── Models/                  ← uma classe por tabela
│   │   │   ├── User.php                 (alterado) usuário com token e conta
│   │   │   ├── Conta.php                (novo)
│   │   │   ├── Transacao.php            (novo)
│   │   │   └── Lancamento.php           (novo)
│   │   └── Services/                ← regras de negócio (pasta criada por você)
│   │       └── TransferenciaService.php (novo)
│   ├── bootstrap/app.php            ← configura o Laravel (alterado: 401 em JSON)
│   ├── config/                      ← configurações (padrão, não mexer agora)
│   ├── database/
│   │   ├── migrations/              ← criação das tabelas, versionada
│   │   │   ├── 0001_..._create_users_table.php         (padrão)
│   │   │   ├── 0001_..._create_cache_table.php         (padrão)
│   │   │   ├── 0001_..._create_jobs_table.php          (padrão)
│   │   │   ├── ..._create_personal_access_tokens_table.php (Sanctum)
│   │   │   ├── 2026_10_21_120000_create_contas_table.php      (novo)
│   │   │   ├── 2026_10_21_120100_create_transacoes_table.php  (novo)
│   │   │   └── 2026_10_21_120200_create_lancamentos_table.php (novo)
│   │   └── seeders/
│   │       └── DatabaseSeeder.php   (alterado: dados de teste)
│   ├── routes/
│   │   ├── api.php                  ← endereços da API /api/... (alterado)
│   │   └── web.php                  ← página inicial "/" (alterado)
│   ├── tests/
│   │   └── Feature/
│   │       └── TransferenciaTest.php    (novo)
│   ├── .dockerignore                (novo)
│   ├── .env.example                 (alterado)
│   ├── Dockerfile                   (alterado)
│   ├── composer.json / composer.lock (padrão + Sanctum)
│   └── phpunit.xml                  (alterado: removido o teste "Unit")
└── (pasta infra/ e mobile/ fora desta etapa)
```

### O que cada pasta faz

| Pasta | Função | Você mexe agora? |
|---|---|---|
| `app/Http/Controllers` | Recebe, valida e responde | Sim (3 arquivos) |
| `app/Models` | Representa uma tabela e seus relacionamentos | Sim (4 arquivos) |
| `app/Services` | Regra de negócio: saldo, transferência | Sim (criar a pasta e 1 arquivo) |
| `database/migrations` | Estrutura do banco, em código | Sim (3 arquivos novos) |
| `database/seeders` | Dados de teste | Sim (opcional) |
| `routes/` | Lista de endereços da API | Sim |
| `tests/Feature` | Testes da API | Sim (1 arquivo) |
| `.env` | Segredos locais. **Nunca vai para o Git** | Criado a partir do `.env.example` |
| `vendor/` | Bibliotecas instaladas pelo composer. **Nunca vai para o Git** | Gerada pelo `composer install` |

### Por que não existem `Requests`, `Resources`, `Policies`, `Jobs`?

A documentação cita essas pastas. Elas não são necessárias agora. Entram quando o projeto pedir:

- `Requests/` quando a validação ficar grande
- `Resources/` quando o JSON ficar complexo
- `Policies/` quando houver perfis do backoffice (RF09)
- `Jobs/` quando houver fila (notificações, agendamentos)

---

## 3. Como uma requisição percorre o sistema

Exemplo: transferência de R$ 250,00.

```
Cliente
  │  POST /api/transferencias   + Authorization: Bearer <token>
  ▼
routes/api.php              → confere o token (auth:sanctum)
  ▼
TransacaoController@store   → valida conta destino e valor
  ▼
TransferenciaService        → confere o saldo, trava a conta, grava 2 lançamentos
  ▼
Models + PostgreSQL         → gravam tudo na mesma transação
  ▼
JSON 201 Created
```

Se o saldo for insuficiente, o Service lança uma exceção. O controller responde **422** e **nada é gravado**.

---

## 4. Arquivos a adicionar e a alterar

### 4.1 O que existe hoje no repositório (`Projeto/backend`)

| Arquivo atual | Problema | Ação |
|---|---|---|
| `Projeto/backend/Dockerfile` | Serve só a pasta `public/` com `php -S`. **Não roda o Laravel.** | **Substituir** pelo da entrega |
| `Projeto/backend/.env.example` | Vazio | **Substituir** pelo da entrega |
| `Projeto/backend/public/index.html` | Página de teste do time. Não é usada pelo Laravel | **Remover** |
| `Projeto/infra/render.yaml` | Vazio. A configuração do Render fica no painel | **Remover** |
| `Projeto/README.md` | Vazio | **Substituir** por este arquivo |
| Laravel (`app/`, `routes/`, `config/`...) | **Não existe** no repositório | **Adicionar** a pasta `backend/` da entrega |

Nada disso é commitado pelo assistente. Você faz o commit quando achar melhor.

### 4.2 Lista completa de arquivos da entrega

**Novos (criados por nós):**

| Arquivo | Para que serve |
|---|---|
| `app/Http/Controllers/AuthController.php` | Login e logout com token |
| `app/Http/Controllers/ContaController.php` | Saldo e extrato |
| `app/Http/Controllers/TransacaoController.php` | Transferência |
| `app/Models/Conta.php` | Tabela `contas` e cálculo do saldo |
| `app/Models/Transacao.php` | Tabela `transacoes` |
| `app/Models/Lancamento.php` | Tabela `lancamentos` |
| `app/Services/TransferenciaService.php` | Regra da transferência |
| `database/migrations/2026_10_21_120000_create_contas_table.php` | Cria `contas` |
| `database/migrations/2026_10_21_120100_create_transacoes_table.php` | Cria `transacoes` |
| `database/migrations/2026_10_21_120200_create_lancamentos_table.php` | Cria `lancamentos` |
| `tests/Feature/TransferenciaTest.php` | Testes da API |
| `.dockerignore` | Impede que `.env` e `vendor/` entrem na imagem |

**Alterados em relação ao Laravel padrão:**

| Arquivo | O que mudou |
|---|---|
| `app/Models/User.php` | Trait do Sanctum (`HasApiTokens`), senha com hash, relação `conta()` |
| `bootstrap/app.php` | Visitante sem token recebe **401 em JSON**, em vez de erro 500 |
| `routes/api.php` | Rotas de login, logout, conta e transferências |
| `routes/web.php` | Página "/" devolve um JSON de status |
| `database/seeders/DatabaseSeeder.php` | Dois clientes de teste (só para testes locais) |
| `phpunit.xml` | Removido o conjunto de testes "Unit", que não usamos |
| `.env.example` | PostgreSQL, sessão em arquivo, nome do app |
| `Dockerfile` | Roda o Laravel, com `composer install`, migrations e servidor |

**Removidos do Laravel padrão** (não são usados): `package.json`, `vite.config.js`, `resources/js`, `resources/css`, `resources/views/welcome.blade.php`, `AGENTS.md`, `CLAUDE.md`, `README.md` do Laravel e os testes de exemplo.

### 4.3 Código dos arquivos

**`routes/api.php`**

```php
<?php

use App\Http\Controllers\AuthController;
use App\Http\Controllers\ContaController;
use App\Http\Controllers\TransacaoController;
use Illuminate\Support\Facades\Route;

Route::post('/login', [AuthController::class, 'login']);

Route::middleware('auth:sanctum')->group(function () {
    Route::post('/logout', [AuthController::class, 'logout']);
    Route::get('/conta', [ContaController::class, 'show']);
    Route::post('/transferencias', [TransacaoController::class, 'store']);
});
```

**`routes/web.php`**

```php
<?php

use Illuminate\Support\Facades\Route;

// Página inicial: só confirma que o servidor está no ar
Route::get('/', fn () => response()->json(['app' => 'Fluxo', 'status' => 'ok']));
```

**`bootstrap/app.php`**

```php
<?php

use Illuminate\Foundation\Application;
use Illuminate\Foundation\Configuration\Exceptions;
use Illuminate\Foundation\Configuration\Middleware;
use Illuminate\Http\Request;

return Application::configure(basePath: dirname(__DIR__))
    ->withRouting(
        web: __DIR__.'/../routes/web.php',
        api: __DIR__.'/../routes/api.php',
        commands: __DIR__.'/../routes/console.php',
        health: '/up',
    )
    ->withMiddleware(function (Middleware $middleware): void {
        // API sem tela de login: visitante sem token recebe 401 em JSON
        $middleware->redirectGuestsTo(fn () => null);
    })
    ->withExceptions(function (Exceptions $exceptions): void {
        $exceptions->shouldRenderJsonWhen(
            fn (Request $request) => $request->is('api/*') || $request->expectsJson(),
        );
    })->create();
```

**`app/Models/User.php`**

```php
<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Relations\HasOne;
use Illuminate\Foundation\Auth\User as Authenticatable;
use Illuminate\Notifications\Notifiable;
use Laravel\Sanctum\HasApiTokens;

class User extends Authenticatable
{
    use HasApiTokens, HasFactory, Notifiable;

    protected $fillable = ['name', 'email', 'password'];

    protected $hidden = ['password', 'remember_token'];

    protected function casts(): array
    {
        return ['password' => 'hashed'];
    }

    // Por simplicidade, cada usuário (cliente) tem uma conta
    public function conta(): HasOne
    {
        return $this->hasOne(Conta::class);
    }
}
```

**`app/Models/Conta.php`**

```php
<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Conta extends Model
{
    protected $fillable = ['user_id', 'numero'];

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    public function lancamentos(): HasMany
    {
        return $this->hasMany(Lancamento::class);
    }

    // Saldo sempre calculado pelos lançamentos (nunca guardado num campo)
    public function saldo(): int
    {
        $creditos = $this->lancamentos()->where('tipo', 'credito')->sum('valor_centavos');
        $debitos = $this->lancamentos()->where('tipo', 'debito')->sum('valor_centavos');

        return (int) ($creditos - $debitos);
    }
}
```

**`app/Models/Transacao.php`**

```php
<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Transacao extends Model
{
    // Obrigatório: sem isso o Laravel procura a tabela "transacaos"
    protected $table = 'transacoes';

    protected $fillable = ['descricao'];

    public function lancamentos(): HasMany
    {
        return $this->hasMany(Lancamento::class);
    }
}
```

> A linha `protected $table = 'transacoes';` é obrigatória. Sem ela, o Laravel procura a tabela `transacaos` (plural errado). Esse erro apareceu durante os testes.

**`app/Models/Lancamento.php`**

```php
<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class Lancamento extends Model
{
    protected $fillable = ['transacao_id', 'conta_id', 'tipo', 'valor_centavos'];

    public function transacao(): BelongsTo
    {
        return $this->belongsTo(Transacao::class);
    }

    public function conta(): BelongsTo
    {
        return $this->belongsTo(Conta::class);
    }
}
```

**`app/Services/TransferenciaService.php`**

```php
<?php

namespace App\Services;

use App\Models\Conta;
use App\Models\Lancamento;
use App\Models\Transacao;
use DomainException;
use Illuminate\Support\Facades\DB;

class TransferenciaService
{
    // Toda regra de transferência fica aqui, não no controller
    public function transferir(Conta $origem, Conta $destino, int $valorCentavos, ?string $descricao = null): Transacao
    {
        if ($valorCentavos <= 0) {
            throw new DomainException('O valor deve ser maior que zero.');
        }

        if ($origem->id === $destino->id) {
            throw new DomainException('Não é possível transferir para a mesma conta.');
        }

        // Tudo ou nada: se algo falhar, nenhum lançamento é gravado
        return DB::transaction(function () use ($origem, $destino, $valorCentavos, $descricao) {
            // Trava a conta de origem para evitar dois saques simultâneos
            $origem = Conta::whereKey($origem->id)->lockForUpdate()->firstOrFail();

            if ($origem->saldo() < $valorCentavos) {
                throw new DomainException('Saldo insuficiente.');
            }

            $transacao = Transacao::create(['descricao' => $descricao]);

            Lancamento::create([
                'transacao_id' => $transacao->id,
                'conta_id' => $origem->id,
                'tipo' => 'debito',
                'valor_centavos' => $valorCentavos,
            ]);

            Lancamento::create([
                'transacao_id' => $transacao->id,
                'conta_id' => $destino->id,
                'tipo' => 'credito',
                'valor_centavos' => $valorCentavos,
            ]);

            return $transacao;
        });
    }
}
```

**`app/Http/Controllers/AuthController.php`**

```php
<?php

namespace App\Http\Controllers;

use App\Models\User;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Hash;

class AuthController extends Controller
{
    public function login(Request $request): JsonResponse
    {
        $dados = $request->validate([
            'email' => 'required|email',
            'password' => 'required|string',
        ]);

        $user = User::where('email', $dados['email'])->first();

        if (! $user || ! Hash::check($dados['password'], $user->password)) {
            return response()->json(['erro' => 'Credenciais inválidas.'], 401);
        }

        return response()->json([
            'token' => $user->createToken('app')->plainTextToken,
        ]);
    }

    public function logout(Request $request): JsonResponse
    {
        $request->user()->currentAccessToken()->delete();

        return response()->json(['mensagem' => 'Sessão encerrada.']);
    }
}
```

**`app/Http/Controllers/ContaController.php`**

```php
<?php

namespace App\Http\Controllers;

use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class ContaController extends Controller
{
    // Saldo e últimos 50 lançamentos (RNF05: paginação de no máximo 50)
    public function show(Request $request): JsonResponse
    {
        $conta = $request->user()->conta;

        return response()->json([
            'numero' => $conta->numero,
            'saldo_centavos' => $conta->saldo(),
            'extrato' => $conta->lancamentos()->latest()->limit(50)->get(),
        ]);
    }
}
```

**`app/Http/Controllers/TransacaoController.php`**

```php
<?php

namespace App\Http\Controllers;

use App\Models\Conta;
use App\Services\TransferenciaService;
use DomainException;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class TransacaoController extends Controller
{
    public function __construct(private TransferenciaService $transferencias)
    {
    }

    public function store(Request $request): JsonResponse
    {
        $dados = $request->validate([
            'conta_destino_id' => 'required|integer|exists:contas,id',
            'valor_centavos' => 'required|integer|min:1',
            'descricao' => 'nullable|string|max:120',
        ]);

        try {
            $transacao = $this->transferencias->transferir(
                $request->user()->conta,
                Conta::findOrFail($dados['conta_destino_id']),
                $dados['valor_centavos'],
                $dados['descricao'] ?? null,
            );
        } catch (DomainException $e) {
            return response()->json(['erro' => $e->getMessage()], 422);
        }

        return response()->json($transacao->load('lancamentos'), 201);
    }
}
```

**`database/migrations/2026_10_21_120000_create_contas_table.php`**

```php
<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('contas', function (Blueprint $table) {
            $table->id();
            $table->foreignId('user_id')->unique()->constrained()->cascadeOnDelete();
            $table->string('numero', 20)->unique();
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('contas');
    }
};
```

**`database/migrations/2026_10_21_120100_create_transacoes_table.php`**

```php
<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('transacoes', function (Blueprint $table) {
            $table->id();
            $table->string('descricao', 120)->nullable();
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('transacoes');
    }
};
```

**`database/migrations/2026_10_21_120200_create_lancamentos_table.php`**

```php
<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('lancamentos', function (Blueprint $table) {
            $table->id();
            $table->foreignId('transacao_id')->constrained('transacoes')->cascadeOnDelete();
            $table->foreignId('conta_id')->constrained('contas')->cascadeOnDelete();
            $table->enum('tipo', ['debito', 'credito']);
            $table->unsignedBigInteger('valor_centavos');
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('lancamentos');
    }
};
```

**`database/seeders/DatabaseSeeder.php`**

```php
<?php

namespace Database\Seeders;

use App\Models\Conta;
use App\Models\Lancamento;
use App\Models\Transacao;
use App\Models\User;
use Illuminate\Database\Seeder;

class DatabaseSeeder extends Seeder
{
    // SOMENTE para testes locais. Não rode em produção.
    // Cria 2 clientes; a Ana começa com R$ 1.000,00.
    public function run(): void
    {
        $ana = User::create(['name' => 'Ana', 'email' => 'ana@fluxo.test', 'password' => 'senha123']);
        $bruno = User::create(['name' => 'Bruno', 'email' => 'bruno@fluxo.test', 'password' => 'senha123']);

        $contaAna = Conta::create(['user_id' => $ana->id, 'numero' => '0001-1']);
        Conta::create(['user_id' => $bruno->id, 'numero' => '0002-2']);

        $deposito = Transacao::create(['descricao' => 'Depósito inicial']);
        Lancamento::create([
            'transacao_id' => $deposito->id,
            'conta_id' => $contaAna->id,
            'tipo' => 'credito',
            'valor_centavos' => 100000,
        ]);
    }
}
```

**`tests/Feature/TransferenciaTest.php`**

```php
<?php

namespace Tests\Feature;

use Database\Seeders\DatabaseSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class TransferenciaTest extends TestCase
{
    use RefreshDatabase;

    private function login(string $email): string
    {
        $resposta = $this->postJson('/api/login', ['email' => $email, 'password' => 'senha123']);
        $resposta->assertOk();

        return $resposta->json('token');
    }

    public function test_transferencia_movimenta_o_saldo(): void
    {
        $this->seed(DatabaseSeeder::class);
        $token = $this->login('ana@fluxo.test');

        $this->withToken($token)->postJson('/api/transferencias', [
            'conta_destino_id' => 2,
            'valor_centavos' => 25000,
            'descricao' => 'Teste',
        ])->assertCreated();

        $this->withToken($token)->getJson('/api/conta')
            ->assertOk()
            ->assertJsonPath('saldo_centavos', 75000);
    }

    public function test_nao_permite_transferir_mais_que_o_saldo(): void
    {
        $this->seed(DatabaseSeeder::class);
        $token = $this->login('ana@fluxo.test');

        $this->withToken($token)->postJson('/api/transferencias', [
            'conta_destino_id' => 2,
            'valor_centavos' => 200000,
        ])->assertStatus(422)->assertJsonPath('erro', 'Saldo insuficiente.');
    }

    public function test_rotas_protegidas_exigem_token(): void
    {
        $this->getJson('/api/conta')->assertUnauthorized();
    }
}
```

**`Dockerfile`**

```dockerfile
# Imagem PHP 8.3 com a extensão do PostgreSQL
FROM php:8.3-cli

RUN apt-get update \
    && apt-get install -y --no-install-recommends libpq-dev unzip git \
    && docker-php-ext-install pdo_pgsql pgsql \
    && rm -rf /var/lib/apt/lists/*

COPY --from=composer:2 /usr/bin/composer /usr/bin/composer

WORKDIR /app

COPY . .

RUN composer install --no-dev --no-interaction --prefer-dist --optimize-autoloader \
    && mkdir -p storage/framework/cache storage/framework/sessions storage/framework/views storage/logs bootstrap/cache \
    && chmod -R 775 storage bootstrap/cache

# O Render define PORT automaticamente. Roda as migrations e sobe o servidor.
CMD ["sh", "-c", "php artisan migrate --force && php artisan serve --host=0.0.0.0 --port=${PORT:-10000}"]
```

**`.dockerignore`**

```
.env
vendor/
node_modules/
storage/logs/*
.git/
.gitignore
```

**`.env.example`** (trecho com as linhas alteradas; o arquivo completo está na entrega)

```dotenv
APP_NAME=Fluxo
APP_ENV=local
APP_DEBUG=true
APP_URL=http://localhost:8000

DB_CONNECTION=pgsql
DB_HOST=127.0.0.1
DB_PORT=5432
DB_DATABASE=fluxo
DB_USERNAME=postgres
DB_PASSWORD=

SESSION_DRIVER=file
CACHE_STORE=database
QUEUE_CONNECTION=database
```

> `APP_KEY` não vai no `.env.example`. Gere-a com `php artisan key:generate` (local) ou copie a saída de `php artisan key:generate --show` (Render).

---

## 5. Como aplicar no repositório (Codespaces)

Os comandos abaixo são para você executar. **Nada é commitado automaticamente.**

### 5.1 Copiar a pasta do backend

Depois de baixar a pasta `Projeto/` da entrega para o seu Codespaces, dentro do repositório:

```bash
cd /workspaces/Fluxo-Bank

# remove a versão antiga do backend (só tinha Dockerfile e index.html)
rm -rf Projeto/backend
rm -rf Projeto/infra

# copie a pasta "backend" da entrega para dentro de Projeto/
# (ajuste o caminho de origem para onde você descompactou a entrega)
cp -r /caminho/da/entrega/Projeto/backend Projeto/backend
cp /caminho/da/entrega/Projeto/README.md Projeto/README.md
```

Se preferir, use o explorador de arquivos do VS Code para arrastar a pasta `backend` para `Projeto/`, substituindo a antiga.

### 5.2 Instalar as dependências e configurar o ambiente

```bash
cd Projeto/backend
composer install
cp .env.example .env
php artisan key:generate
```

### 5.3 Rodar os testes

Os testes usam SQLite em memória. Não precisa de PostgreSQL.

```bash
php artisan test
```

Resultado esperado: **3 testes passando** (transferência movimenta o saldo, bloqueio de saldo insuficiente e rota protegida).

### 5.4 Rodar localmente com PostgreSQL (opcional)

Com um PostgreSQL 16 rodando e o `.env` apontando para ele:

```bash
php artisan migrate:fresh --seed
php artisan serve
```

Em outro terminal:

```bash
# login: devolve o token
curl -s -X POST http://localhost:8000/api/login \
  -H "Content-Type: application/json" \
  -d '{"email":"ana@fluxo.test","password":"senha123"}'

# saldo e extrato (troque SEU_TOKEN)
curl -s http://localhost:8000/api/conta -H "Authorization: Bearer SEU_TOKEN"

# transferência de R$ 250,00 para a conta de id 2
curl -s -X POST http://localhost:8000/api/transferencias \
  -H "Authorization: Bearer SEU_TOKEN" \
  -H "Content-Type: application/json" \
  -d '{"conta_destino_id":2,"valor_centavos":25000,"descricao":"Teste"}'
```

---

## 6. Endpoints

| Método | Rota | Protegida | O que faz |
|---|---|---|---|
| GET | `/` | não | Confirma que o servidor está no ar |
| POST | `/api/login` | não | Recebe `email` e `password`, devolve `token` |
| POST | `/api/logout` | sim | Encerra o token atual |
| GET | `/api/conta` | sim | Número da conta, saldo em centavos e últimos 50 lançamentos |
| POST | `/api/transferencias` | sim | Transfere `valor_centavos` para `conta_destino_id` |

Códigos de resposta:

- **200 / 201**: sucesso
- **401**: credenciais inválidas, ou token ausente nas rotas protegidas
- **422**: saldo insuficiente, valor inválido ou conta destino inexistente

---

## 7. Deploy no Render

1. **Crie o banco:** *New + → PostgreSQL* (plano Free).
2. **Crie o serviço:** *New + → Web Service*, conecte o repositório `Fluxo-Bank`.
   - **Root Directory:** `Projeto/backend`
   - **Runtime:** `Docker`
   - **Instance Type:** `Free`
3. **Variáveis de ambiente** (aba *Environment*):

| Variável | Valor |
|---|---|
| `APP_KEY` | saída de `php artisan key:generate --show` (rodada local) |
| `APP_ENV` | `production` |
| `APP_DEBUG` | `false` |
| `APP_URL` | `https://NOME-DO-SERVICO.onrender.com` |
| `DB_CONNECTION` | `pgsql` |
| `DB_HOST` | host do PostgreSQL (campo *Hostname* do banco) |
| `DB_PORT` | `5432` |
| `DB_DATABASE` | nome do banco (campo *Database*) |
| `DB_USERNAME` | usuário (campo *Username*) |
| `DB_PASSWORD` | senha (campo *Password*) |
| `SESSION_DRIVER` | `file` |
| `CACHE_STORE` | `database` |
| `QUEUE_CONNECTION` | `database` |

4. **Deploy:** o container roda `php artisan migrate --force` e sobe o servidor.
5. **Teste:** abra `https://NOME-DO-SERVICO.onrender.com/` (deve mostrar `{"app":"Fluxo","status":"ok"}`).

> **Não rode o seeder em produção.** Ele cria usuários de teste com senha conhecida. Use `migrate:fresh --seed` só no ambiente local.

> No plano gratuito, o serviço hiberna após cerca de 15 minutos sem acesso, e o banco expira em cerca de 30 dias (conforme a documentação). Faça backup com `pg_dump` se precisar guardar dados.

---

## 8. Verificação feita antes da entrega

Esta entrega foi testada em uma cópia limpa:

- `composer install` do zero, sem o `vendor/` incluído
- `php artisan test`: **3 testes passaram**
- Smoke test com servidor HTTP: `GET /` retornou 200; login retornou token; saldo inicial R$ 1.000,00; transferência retornou 201 e saldo passou a R$ 750,00; sem token retornou 401; saldo insuficiente retornou 422 com mensagem.

**Não testado:** o build da imagem Docker e o deploy no Render, porque não há Docker neste ambiente. Se der erro no build ou no deploy, envie o log.

---

## 9. Próximos passos

1. Cadastro com CPF/CNPJ validados e KYC simulado (RF01, RF10)
2. Verificação em dois fatores (RF02)
3. Chaves Pix e limites (RF04, RF14, RF23)
4. Antifraude (RF05)
5. Cartões físicos e virtuais (RF07, RF12)
6. Trilha de auditoria (RF09, RF-SEG01 a RF-SEG04)
7. Aplicativo mobile (outra etapa)

Cada funcionalidade segue o mesmo caminho: **migration → model → service → controller → rota → teste**.

---

## 10. Glossário

- **Partida dobrada:** toda movimentação tem um débito e um crédito de mesmo valor.
- **Lançamento:** uma linha da partida dobrada (`tipo` = `debito` ou `credito`).
- **Token (Sanctum):** chave enviada no header `Authorization: Bearer` que identifica o usuário.
- **Migration:** arquivo que cria ou altera tabelas, versionado no Git.
- **Seeder:** arquivo que preenche o banco com dados de teste.
- **Service:** classe que concentra a regra de negócio.
