<?php

use App\Http\Controllers\HealthController;
use App\Http\Controllers\Web\AdminUsuarioController;
use App\Http\Controllers\Web\AdminWebController;
use App\Http\Controllers\Web\AuthWebController;
use App\Http\Controllers\Web\BankActionController;
use App\Http\Controllers\Web\BankWebController;
use App\Http\Middleware\ContextoAuditoria;
use App\Http\Middleware\WebAdmin;
use App\Http\Middleware\WebAuthenticated;
use App\Http\Middleware\WebBackoffice;
use App\Http\Middleware\WebCliente;
use Illuminate\Cookie\Middleware\AddQueuedCookiesToResponse;
use Illuminate\Cookie\Middleware\EncryptCookies;
use Illuminate\Foundation\Http\Middleware\PreventRequestForgery;
use Illuminate\Foundation\Http\Middleware\ValidateCsrfToken;
use Illuminate\Http\Request;
use Illuminate\Session\Middleware\StartSession;
use Illuminate\Support\Facades\Route;
use Illuminate\View\Middleware\ShareErrorsFromSession;

$semSessao = [EncryptCookies::class, AddQueuedCookiesToResponse::class, StartSession::class,
    ShareErrorsFromSession::class, PreventRequestForgery::class, ValidateCsrfToken::class];

Route::get('/docs/openapi.yaml', function () {
    $caminho = base_path('docs/openapi.yaml');
    abort_unless(is_file($caminho), 404);

    return response(file_get_contents($caminho), 200, ['Content-Type' => 'application/yaml; charset=UTF-8']);
})->withoutMiddleware($semSessao)->name('openapi');

Route::get('/', fn (Request $request) => $request->expectsJson()
    ? app(HealthController::class)()
    : redirect()->route('web.login'))
    ->name('web.root');

Route::prefix('acesso')->name('web.')->middleware('guest:web')->group(function () {
    Route::get('/entrar', [AuthWebController::class, 'loginPage'])->name('login');
    Route::post('/entrar', [AuthWebController::class, 'login'])->middleware('throttle:10,1')->name('login.store');
    Route::get('/verificar', [AuthWebController::class, 'twoFactorPage'])->name('2fa');
    Route::post('/verificar', [AuthWebController::class, 'twoFactorVerify'])->middleware('throttle:10,1')->name('2fa.verify');
    Route::get('/cadastro', [AuthWebController::class, 'registrationPage'])->name('register');
    Route::post('/cadastro', [AuthWebController::class, 'register'])->middleware('throttle:10,1')->name('register.store');
    Route::get('/recuperar', [AuthWebController::class, 'recoveryPage'])->name('recovery');
    Route::post('/recuperar/solicitar', [AuthWebController::class, 'recoveryRequest'])->middleware('throttle:10,1')->name('recovery.request');
    Route::post('/recuperar/validar', [AuthWebController::class, 'recoveryValidate'])->middleware('throttle:10,1')->name('recovery.validate');
    Route::post('/recuperar/redefinir', [AuthWebController::class, 'recoveryReset'])->middleware('throttle:10,1')->name('recovery.reset');
});

Route::middleware([WebAuthenticated::class, ContextoAuditoria::class, 'throttle:api'])->name('web.')->group(function () {
    Route::get('/inicio', function () {
        return auth('web')->user()->ehBackoffice()
            ? redirect()->route('web.admin.dashboard')
            : redirect()->route('web.dashboard');
    })->name('home');
    Route::post('/sair', [AuthWebController::class, 'logout'])->name('logout');

    Route::middleware([WebCliente::class])->group(function () {
        Route::get('/dashboard', [BankWebController::class, 'dashboard'])->name('dashboard');
        Route::get('/extrato', [BankWebController::class, 'extrato'])->name('extrato');
        Route::get('/pix', [BankWebController::class, 'pix'])->name('pix');
        Route::get('/pix/limites', [BankWebController::class, 'limitesPix'])->name('pix.limits');
        Route::post('/pix/limites', [BankActionController::class, 'ajustarLimitesPix'])->name('pix.limits.save');
        Route::get('/pix/transferir', [BankWebController::class, 'transferencia'])->name('transferencia');
        Route::post('/pix/revisar', [BankActionController::class, 'revisarTransferencia'])->name('transfer.review');
        Route::get('/pix/confirmar', [BankWebController::class, 'confirmarTransferencia'])->name('transfer.confirm');
        Route::post('/pix/confirmar', [BankActionController::class, 'confirmarTransferencia'])->name('transfer.execute');
        Route::get('/pix/receber', [BankWebController::class, 'receberPix'])->name('pix.receive');
        Route::post('/pix/receber', [BankActionController::class, 'gerarCobrancaPix'])->name('pix.charge');
        Route::delete('/pix/cobrancas/{cobranca}', [BankActionController::class, 'cancelarCobrancaPix'])->name('pix.charge.cancel');
        Route::get('/pix/chaves', [BankWebController::class, 'chaves'])->name('pix.keys');
        Route::post('/pix/chaves', [BankActionController::class, 'criarChave'])->name('pix.keys.store');
        Route::delete('/pix/chaves/{chave}', [BankActionController::class, 'excluirChave'])->name('pix.keys.delete');
        Route::get('/comprovantes/{transacao}/pdf', [BankWebController::class, 'baixarComprovante'])->name('receipt.pdf');
        Route::get('/comprovantes/{transacao}', [BankWebController::class, 'comprovante'])->name('receipt');
        Route::get('/cartoes', [BankWebController::class, 'cartoes'])->name('cards');
        Route::post('/cartoes', [BankActionController::class, 'emitirCartao'])->name('cards.store');
        Route::post('/cartoes/{cartao}/virtual', [BankActionController::class, 'gerarVirtual'])->name('cards.virtual');
        Route::patch('/cartoes/{cartao}/bloqueio', [BankActionController::class, 'bloquearCartao'])->name('cards.block');
        Route::patch('/cartoes/{cartao}/online', [BankActionController::class, 'comprasOnline'])->name('cards.online');
        Route::patch('/cartoes/{cartao}/limite', [BankActionController::class, 'limiteCartao'])->name('cards.limit');
        Route::post('/cartoes/simulacoes', [BankActionController::class, 'simularCompra'])->name('cards.simulation');
        Route::post('/cartoes/{cartao}/compra', [BankActionController::class, 'compraDebito'])->name('cards.purchase');
        Route::get('/agendamentos', [BankWebController::class, 'agendamentos'])->name('schedules');
        Route::post('/agendamentos/boleto', [BankActionController::class, 'agendarBoleto'])->name('schedules.boleto');
        Route::post('/agendamentos', [BankActionController::class, 'agendar'])->name('schedules.store');
        Route::delete('/agendamentos/{agendamento}', [BankActionController::class, 'cancelarAgendamento'])->name('schedules.delete');
        Route::get('/pagamentos', [BankWebController::class, 'pagamentos'])->name('payments');
        Route::post('/pagamentos/pix/revisar', [BankActionController::class, 'revisarPagamentoPix'])->name('payments.pix.review');
        Route::get('/pagamentos/pix/confirmar', [BankWebController::class, 'confirmarPagamentoPix'])->name('payments.pix.confirm');
        Route::post('/pagamentos/pix/confirmar', [BankActionController::class, 'pagarPix'])->name('payments.pix.pay');
        Route::post('/pagamentos/revisar', [BankActionController::class, 'revisarBoleto'])->name('payments.review');
        Route::get('/pagamentos/confirmar', [BankWebController::class, 'confirmarBoleto'])->name('payments.confirm');
        Route::post('/pagamentos/confirmar', [BankActionController::class, 'pagarBoleto'])->name('payments.pay');
        Route::get('/assinaturas', [BankWebController::class, 'assinaturas'])->name('subscriptions');
        Route::post('/assinaturas', [BankActionController::class, 'contratarPlano'])->name('subscriptions.store');
        Route::delete('/assinaturas/{assinatura}', [BankActionController::class, 'cancelarPlano'])->name('subscriptions.delete');
        Route::post('/assinaturas/{assinatura}/itens', [BankActionController::class, 'adicionarItem'])->name('subscriptions.items.store');
        Route::delete('/assinaturas/{assinatura}/itens/{item}', [BankActionController::class, 'removerItem'])->name('subscriptions.items.delete');
        Route::delete('/recorrencias/{recorrencia}', [BankActionController::class, 'cancelarRecorrencia'])->name('recurring.delete');
        Route::get('/viagens', [BankWebController::class, 'viagens'])->name('travel');
        Route::post('/viagens', [BankActionController::class, 'criarAviso'])->name('travel.store');
        Route::delete('/viagens/{aviso}', [BankActionController::class, 'cancelarAviso'])->name('travel.delete');
        Route::get('/suporte', [BankWebController::class, 'suporte'])->name('support');
        Route::post('/suporte', [BankActionController::class, 'criarChamado'])->name('support.store');
        Route::get('/perfil', [BankWebController::class, 'perfil'])->name('profile');
        Route::patch('/perfil', [BankActionController::class, 'atualizarCadastro'])->name('profile.update');
        Route::post('/perfil/bloquear', [BankActionController::class, 'bloquearConta'])->name('profile.block');
        Route::delete('/perfil/bloquear', [BankActionController::class, 'desbloquearConta'])->name('profile.unblock');
        Route::delete('/perfil/encerrar', [BankActionController::class, 'encerrarConta'])->name('profile.close');
        Route::post('/perfil/2fa', [BankActionController::class, 'configurarDoisFatores'])->name('profile.2fa');
    });

    Route::prefix('administracao')->name('admin.')->middleware(WebBackoffice::class)->group(function () {
        Route::get('/', [AdminWebController::class, 'dashboard'])->name('dashboard');
        Route::get('/contas', [AdminWebController::class, 'contas'])->name('accounts');
        Route::get('/contas/{conta}', [AdminWebController::class, 'conta'])->name('accounts.show');
        Route::post('/contas/{conta}/aprovar', [AdminWebController::class, 'aprovar'])->name('accounts.approve');
        Route::post('/contas/{conta}/recusar', [AdminWebController::class, 'recusar'])->name('accounts.reject');
        Route::post('/contas/{conta}/bloquear', [AdminWebController::class, 'bloquear'])->name('accounts.block');
        Route::get('/chamados', [AdminWebController::class, 'chamados'])->name('tickets');
        Route::patch('/chamados/{chamado}', [AdminWebController::class, 'responder'])->name('tickets.reply');
        Route::get('/auditoria', [AdminWebController::class, 'auditoria'])->name('audit');
        Route::middleware(WebAdmin::class)->group(function () {
            Route::post('/contas/{conta}/desbloquear', [AdminWebController::class, 'desbloquear'])->name('accounts.unblock');
            Route::patch('/contas/{conta}/limites', [AdminWebController::class, 'ajustarLimites'])->name('accounts.limits');
            Route::delete('/contas/{conta}', [AdminWebController::class, 'encerrar'])->name('accounts.close');
            Route::get('/perfis', fn () => redirect()->route('web.admin.users.index', request()->query()))->name('roles');
            Route::patch('/perfis/{user}', [AdminUsuarioController::class, 'updateRole'])->name('roles.update');
            Route::get('/usuarios', [AdminUsuarioController::class, 'index'])->name('users.index');
            Route::get('/usuarios/novo', [AdminUsuarioController::class, 'create'])->name('users.create');
            Route::post('/usuarios', [AdminUsuarioController::class, 'store'])->name('users.store');
            Route::get('/usuarios/{user}/editar', [AdminUsuarioController::class, 'edit'])->name('users.edit');
            Route::patch('/usuarios/{user}', [AdminUsuarioController::class, 'update'])->name('users.update');
            Route::delete('/usuarios/{user}', [AdminUsuarioController::class, 'destroy'])->name('users.destroy');
            Route::post('/recuperacao/codigo', [AdminWebController::class, 'codigoRecuperacao'])->name('recovery.code');
        });
        Route::get('/auditoria/{log}', [AdminWebController::class, 'detalheLog'])->name('audit.show');
    });
});

// Compatibilidade com clientes da API que utilizavam o prefixo sem versão.
Route::any('/api/{caminho}', function (Request $request, string $caminho) {
    $destino = url('/api/v1/'.$caminho);
    if ($query = $request->getQueryString()) {
        $destino .= '?'.$query;
    }

    return response()->json(['versao' => 'v1'], 308, ['Location' => $destino]);
})->where('caminho', '(?!v1(?:/|$)).+')->withoutMiddleware($semSessao);
