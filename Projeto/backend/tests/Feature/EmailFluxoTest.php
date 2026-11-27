<?php

namespace Tests\Feature;

use App\Models\Conta;
use App\Services\ContaService;
use App\Services\EntregaEmailService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Http;
use Tests\TestCase;

class EmailFluxoTest extends TestCase
{
    use RefreshDatabase;

    public function test_envia_email_kyc_apos_aprovacao(): void
    {
        config()->set('fluxo.email.provedor', 'resend');
        config()->set('services.resend.key', 'chave-teste');
        config()->set('mail.from.address', 'contato@example.com');
        config()->set('mail.from.name', 'Fluxo Bank');
        Http::fake(['api.resend.com/*' => Http::response(['id' => 'email-teste'], 200)]);

        $usuario = $this->usuarioComConta();
        $usuario->conta->update([
            'status' => Conta::STATUS_PENDENTE,
            'status_abertura' => Conta::ABERTURA_EM_ANALISE,
        ]);
        app(ContaService::class)->aprovarAbertura($usuario->conta->refresh());

        Http::assertSent(fn ($req) => str_contains($req->url(), 'api.resend.com/emails')
            && str_contains((string) $req['subject'], 'aprovada'));
    }

    public function test_servico_de_email_exige_provedor_real(): void
    {
        config()->set('fluxo.email.provedor', 'laravel');
        config()->set('mail.default', 'log');
        $this->assertFalse(app(EntregaEmailService::class)->disponivel());
    }
}
