<?php

namespace Tests\Feature;

use Tests\TestCase;

/** Smoke tests das rotas públicas e das barreiras de acesso web. */
class FrontendWebAccessTest extends TestCase
{
    public function test_visitante_visualiza_login(): void
    {
        $this->get('/acesso/entrar')->assertOk()->assertSee('Entrar', false);
    }

    public function test_visitante_visualiza_cadastro(): void
    {
        $this->get('/acesso/cadastro')->assertOk()->assertSee('Pessoa jurídica', false);
    }

    public function test_visitante_nao_acessa_dashboard_cliente(): void
    {
        $this->get('/dashboard')->assertRedirect(route('web.login'));
    }

    public function test_visitante_nao_acessa_backoffice(): void
    {
        $this->get('/administracao')->assertRedirect(route('web.login'));
    }

    public function test_visita_raiz_redireciona_para_login(): void
    {
        $this->get('/')->assertRedirect(route('web.login'));
    }
}
