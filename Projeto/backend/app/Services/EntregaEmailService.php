<?php

namespace App\Services;

use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Mail;
use RuntimeException;

class EntregaEmailService
{
    public function disponivel(): bool
    {
        if (! filter_var(config('mail.from.address'), FILTER_VALIDATE_EMAIL)) return false;
        if (config('fluxo.email.provedor') === 'resend') return filled(config('services.resend.key'));
        return ! in_array(config('mail.default'), ['log', 'array', 'failover'], true);
    }

    public function enviar(string $destinatario, string $assunto, string $texto): void
    {
        if (! $this->disponivel()) throw new RuntimeException('Serviço de e-mail indisponível.');
        if (config('fluxo.email.provedor') === 'resend') {
            $resposta = Http::withToken((string) config('services.resend.key'))
                ->acceptJson()->timeout(12)->post('https://api.resend.com/emails', [
                    'from' => config('mail.from.name', 'Fluxo Bank').' <'.config('mail.from.address').'>',
                    'to' => [$destinatario], 'subject' => $assunto, 'text' => $texto,
                ]);
            if (! $resposta->successful()) throw new RuntimeException('Provedor de e-mail retornou '.$resposta->status());
            return;
        }
        Mail::raw($texto, fn ($m) => $m->to($destinatario)->subject($assunto));
    }
}
