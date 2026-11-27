<?php

namespace App\Services;

use App\Models\Conta;
use Illuminate\Support\Facades\Log;

class NotificacaoAberturaService
{
    public function enviarDecisao(int $contaId, bool $aprovada, ?string $motivo = null): void
    {
        $conta = Conta::with('user')->find($contaId);
        $destinatario = $conta?->user?->email;
        if (! $destinatario) return;
        $assunto = $aprovada ? 'Fluxo Bank — conta aprovada' : 'Fluxo Bank — solicitação recusada';
        $texto = $aprovada
            ? "Sua solicitação de abertura foi aprovada. Agência: {$conta->agencia}; conta: {$conta->numero}."
            : "Sua solicitação foi recusada. Motivo: ".($motivo ?: 'Entre em contato com o suporte.');
        try {
            $email = app(EntregaEmailService::class);
            if (! $email->disponivel()) {
                Log::warning('Notificação KYC não enviada: e-mail indisponível', ['conta_id' => $contaId]);
                return;
            }
            $email->enviar($destinatario, $assunto, $texto);
        } catch (\Throwable $e) {
            Log::error('Falha na notificação de KYC', ['conta_id' => $contaId, 'erro' => $e->getMessage()]);
        }
    }
}
