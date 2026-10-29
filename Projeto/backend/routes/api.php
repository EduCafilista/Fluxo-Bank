<?php

use App\Http\Controllers\AgendamentoController;
use App\Http\Controllers\AssinaturaCartaoController;
use App\Http\Controllers\AssinaturaController;
use App\Http\Controllers\AuditoriaController;
use App\Http\Controllers\AuthController;
use App\Http\Controllers\AvisoViagemController;
use App\Http\Controllers\BackofficeController;
use App\Http\Controllers\BoletoController;
use App\Http\Controllers\CadastroController;
use App\Http\Controllers\CartaoController;
use App\Http\Controllers\ChamadoSuporteController;
use App\Http\Controllers\ChavePixController;
use App\Http\Controllers\ContaController;
use App\Http\Controllers\HealthController;
use App\Http\Controllers\PixController;
use App\Http\Controllers\RecuperacaoSenhaController;
use App\Http\Controllers\TransacaoController;
use Illuminate\Support\Facades\Route;

Route::get('/health', HealthController::class)->name('health');
Route::get('/openapi.yaml', function () {
    $caminho = base_path('docs/openapi.yaml');

    if (! is_file($caminho) || ! is_readable($caminho)) {
        return response()->json(['erro' => 'Especificação OpenAPI não encontrada.'], 404);
    }

    return response((string) file_get_contents($caminho), 200, [
        'Content-Type' => 'application/yaml; charset=UTF-8',
    ]);
})->name('api.openapi');

Route::post('/cadastro', [CadastroController::class, 'store'])->name('cadastro');
Route::post('/login', [AuthController::class, 'login'])
    ->middleware('throttle:10,1')
    ->name('login');

Route::prefix('senha/recuperacao')->middleware('throttle:10,1')->group(function () {
    Route::post('/solicitar', [RecuperacaoSenhaController::class, 'solicitar'])->name('senha.recuperacao.solicitar');
    Route::post('/validar', [RecuperacaoSenhaController::class, 'validar'])->name('senha.recuperacao.validar');
    Route::post('/redefinir', [RecuperacaoSenhaController::class, 'redefinir'])->name('senha.recuperacao.redefinir');
});

Route::post('/2fa/validar', [AuthController::class, 'validarDoisFatores'])
    ->middleware(['auth:sanctum', 'throttle:10,1'])
    ->name('2fa.validar');

Route::middleware(['auth:sanctum', '2fa.concluido', 'idempotencia'])->group(function () {
    Route::post('/logout', [AuthController::class, 'logout'])->name('logout');
    Route::post('/logout-all', [AuthController::class, 'logoutTodos'])->name('logout.all');
    Route::post('/refresh', [AuthController::class, 'refresh'])->name('refresh');
    Route::get('/eu', [AuthController::class, 'eu'])->name('eu');

    Route::post('/2fa/desafio', [AuthController::class, 'novoDesafio'])->name('2fa.desafio');
    Route::post('/2fa/ativar', [AuthController::class, 'ativarDoisFatores'])->name('2fa.ativar');
    Route::post('/2fa/desativar', [AuthController::class, 'desativarDoisFatores'])->name('2fa.desativar');

    Route::get('/conta', [ContaController::class, 'show'])->name('conta.show');
    Route::get('/conta/extrato', [ContaController::class, 'extrato'])->name('conta.extrato');
    Route::get('/conta/extrato/categorizado', [ContaController::class, 'extratoCategorizado'])->name('conta.extrato.categorizado');
    Route::post('/conta/bloqueio', [ContaController::class, 'bloquear'])->name('conta.bloquear');
    Route::delete('/conta/bloqueio', [ContaController::class, 'desbloquear'])->name('conta.desbloquear');
    Route::delete('/conta', [ContaController::class, 'encerrar'])->name('conta.encerrar');

    Route::get('/transacoes', [TransacaoController::class, 'index'])->name('transacoes.index');
    Route::get('/transacoes/{transacao}', [TransacaoController::class, 'show'])->name('transacoes.show');
    Route::post('/transferencias', [TransacaoController::class, 'store'])->name('transferencias.store');

    Route::get('/chaves-pix', [ChavePixController::class, 'index'])->name('chaves.index');
    Route::post('/chaves-pix', [ChavePixController::class, 'store'])->name('chaves.store');
    Route::delete('/chaves-pix/{chave}', [ChavePixController::class, 'destroy'])->name('chaves.destroy');

    Route::post('/pix/cobrancas', [PixController::class, 'cobranca'])->name('pix.cobrancas');
    Route::post('/pix/recebimentos', [PixController::class, 'receber'])->name('pix.recebimentos');
    Route::get('/pix/limites', [PixController::class, 'limites'])->name('pix.limites');

    Route::post('/boletos/validar', [BoletoController::class, 'validar'])->name('boletos.validar');
    Route::post('/boletos/pagar', [BoletoController::class, 'pagar'])->name('boletos.pagar');

    Route::get('/cartoes', [CartaoController::class, 'index'])->name('cartoes.index');
    Route::post('/cartoes', [CartaoController::class, 'store'])->name('cartoes.store');
    Route::post('/cartoes/{cartao}/virtual', [CartaoController::class, 'gerarVirtual'])->name('cartoes.virtual');
    Route::patch('/cartoes/{cartao}/bloqueio', [CartaoController::class, 'bloqueio'])->name('cartoes.bloqueio');
    Route::patch('/cartoes/{cartao}/limite', [CartaoController::class, 'limite'])->name('cartoes.limite');
    Route::patch('/cartoes/{cartao}/compras-online', [CartaoController::class, 'comprasOnline'])->name('cartoes.online');
    Route::get('/assinaturas-cartao', [AssinaturaCartaoController::class, 'index'])->name('assinaturas-cartao.index');
    Route::delete('/assinaturas-cartao/{assinaturaCartao}', [AssinaturaCartaoController::class, 'cancelar'])->name('assinaturas-cartao.cancelar');

    Route::get('/avisos-viagem', [AvisoViagemController::class, 'index'])->name('avisos.index');
    Route::post('/avisos-viagem', [AvisoViagemController::class, 'store'])->name('avisos.store');
    Route::delete('/avisos-viagem/{aviso}', [AvisoViagemController::class, 'cancelar'])->name('avisos.cancelar');

    Route::get('/agendamentos', [AgendamentoController::class, 'index'])->name('agendamentos.index');
    Route::post('/agendamentos', [AgendamentoController::class, 'store'])->name('agendamentos.store');
    Route::delete('/agendamentos/{agendamento}', [AgendamentoController::class, 'cancelar'])->name('agendamentos.cancelar');

    Route::get('/planos', [AssinaturaController::class, 'planos'])->name('planos.index');
    Route::get('/assinaturas', [AssinaturaController::class, 'index'])->name('assinaturas.index');
    Route::post('/assinaturas', [AssinaturaController::class, 'store'])->name('assinaturas.store');
    Route::post('/assinaturas/{assinatura}/itens', [AssinaturaController::class, 'adicionarItem'])->name('assinaturas.itens.store');
    Route::delete('/assinaturas/{assinatura}/itens/{item}', [AssinaturaController::class, 'removerItem'])->name('assinaturas.itens.destroy');
    Route::delete('/assinaturas/{assinatura}', [AssinaturaController::class, 'cancelar'])->name('assinaturas.cancelar');

    Route::get('/chamados', [ChamadoSuporteController::class, 'index'])->name('chamados.index');
    Route::post('/chamados', [ChamadoSuporteController::class, 'store'])->name('chamados.store');
    Route::get('/chamados/{chamado}', [ChamadoSuporteController::class, 'show'])->name('chamados.show');

    Route::prefix('backoffice')->middleware('backoffice')->group(function () {
        Route::get('/resumo', [BackofficeController::class, 'resumo'])->name('backoffice.resumo');
        Route::get('/contas', [BackofficeController::class, 'contas'])->name('backoffice.contas');
        Route::post('/contas/{conta}/kyc/aprovar', [BackofficeController::class, 'aprovarKyc'])->name('backoffice.kyc.aprovar');
        Route::post('/contas/{conta}/kyc/recusar', [BackofficeController::class, 'recusarKyc'])->name('backoffice.kyc.recusar');
        Route::post('/contas/{conta}/bloquear-fraude', [BackofficeController::class, 'bloquearPorFraude'])->name('backoffice.fraude');
        Route::post('/contas/{conta}/recuperacao-senha', [BackofficeController::class, 'gerarCodigoRecuperacaoSenha'])
            ->middleware('backoffice:ADMIN')
            ->name('backoffice.recuperacao-senha');
        Route::post('/contas/{conta}/codigo-recuperacao', [BackofficeController::class, 'gerarCodigoRecuperacaoSenha'])
            ->middleware('backoffice:ADMIN')
            ->name('backoffice.codigo-recuperacao');
        Route::post('/recuperacao-senha/emitir', [BackofficeController::class, 'gerarCodigoRecuperacaoSenha'])
            ->middleware('backoffice:ADMIN')
            ->name('backoffice.recuperacao-senha.emitir');
        Route::get('/chamados', [BackofficeController::class, 'chamados'])->name('backoffice.chamados');
        Route::patch('/chamados/{chamado}', [BackofficeController::class, 'responderChamado'])->name('backoffice.chamados.responder');
        Route::get('/auditoria', [AuditoriaController::class, 'index'])->name('backoffice.auditoria');
        Route::get('/auditoria/{log}', [AuditoriaController::class, 'show'])->name('backoffice.auditoria.show');
    });
});

Route::any('{caminho?}', fn () => response()->json([
    'erro' => 'Rota não encontrada.',
    'versao' => 'v1',
], 404))->where('caminho', '.*')->name('api.v1.fallback');

