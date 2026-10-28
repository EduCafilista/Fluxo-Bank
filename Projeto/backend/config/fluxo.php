<?php

// Configurações de negócio do Fluxo (valores em CENTAVOS, conforme RNF18).
// Tudo pode ser sobrescrito por variável de ambiente no .env / no Render.
return [

    // ---------------------------------------------------------------------
    // Limites Pix (RN27-RN32, RN42). Usados quando a conta não tem política
    // própria na tabela limites_pix.
    // ---------------------------------------------------------------------
    'limites_pix' => [
        'por_transacao' => (int) env('FLUXO_LIMITE_TRANSACAO', 500000),          // R$ 5.000,00
        'diario' => (int) env('FLUXO_LIMITE_DIARIO', 1000000),                   // R$ 10.000,00
        'quantidade_diaria' => (int) env('FLUXO_QTD_DIARIA', 20),
        'noturno_por_transacao' => (int) env('FLUXO_LIMITE_NOTURNO', 100000),    // R$ 1.000,00
        'noturno_diario' => (int) env('FLUXO_LIMITE_NOTURNO_DIARIO', 200000),    // R$ 2.000,00
        'quantidade_noturna' => (int) env('FLUXO_QTD_NOTURNA', 5),
        'sem_chave_por_transacao' => (int) env('FLUXO_LIMITE_SEM_CHAVE', 50000), // R$ 500,00
        'sem_chave_diario' => (int) env('FLUXO_LIMITE_SEM_CHAVE_DIARIO', 100000), // R$ 1.000,00
        'hora_inicio_noturno' => env('FLUXO_INICIO_NOTURNO', '20:00'),
        'hora_fim_noturno' => env('FLUXO_FIM_NOTURNO', '06:00'),
    ],

    // ---------------------------------------------------------------------
    // Verificação em dois fatores (RF02, RN19/RN20)
    // ---------------------------------------------------------------------
    'dois_fatores' => [
        'ativo_por_padrao' => (bool) env('FLUXO_2FA_PADRAO', false),
        'validade_minutos' => (int) env('FLUXO_2FA_VALIDADE', 3),
        'max_tentativas' => (int) env('FLUXO_2FA_TENTATIVAS', 3),
        'canal' => env('FLUXO_2FA_CANAL', 'EMAIL'),
    ],

    // Bloqueio temporário após falhas de login (RN19: 5 falhas / 15 min)
    'login' => [
        'max_tentativas' => (int) env('FLUXO_LOGIN_TENTATIVAS', 5),
        'bloqueio_minutos' => (int) env('FLUXO_LOGIN_BLOQUEIO', 15),
    ],

    // Recuperação manual de senha em duas etapas (código de 6 dígitos vinculado à conta)
    'recuperacao_senha' => [
        'validade_minutos' => (int) env('FLUXO_RECUPERACAO_VALIDADE', 10),
        'max_tentativas' => (int) env('FLUXO_RECUPERACAO_TENTATIVAS', 5),
        'validade_token_minutos' => (int) env('FLUXO_RECUPERACAO_TOKEN_VALIDADE', 10),
    ],

    // ---------------------------------------------------------------------
    // KYC simulado (RF01). Com true, o cadastro é aprovado automaticamente
    // quando o CPF/Cnpj é válido e o cliente tem 18+; com false, a conta fica
    // PENDENTE e depende do backoffice (RF09).
    // ---------------------------------------------------------------------
    'kyc' => [
        'automatico' => (bool) env('FLUXO_KYC_AUTOMATICO', true),
        'idade_minima' => (int) env('FLUXO_KYC_IDADE_MINIMA', 18),
    ],

    // ---------------------------------------------------------------------
    // Pix / QR Code (RF15, RN36)
    // ---------------------------------------------------------------------
    'pix' => [
        'nome_recebedor' => env('FLUXO_PIX_NOME', 'FLUXO BANCO DIGITAL'),
        'cidade_recebedor' => env('FLUXO_PIX_CIDADE', 'SAO PAULO'),
    ],

    // ---------------------------------------------------------------------
    // Cartões (RF07, RF12, RN38-RN40)
    // ---------------------------------------------------------------------
    'cartoes' => [
        'limite_aprovado_padrao' => (int) env('FLUXO_CARTAO_LIMITE', 200000),   // R$ 2.000,00
        'validade_anos' => (int) env('FLUXO_CARTAO_VALIDADE', 5),
        'dias_virtual' => (int) env('FLUXO_CARTAO_VIRTUAL_DIAS', 30),
    ],

    // Conta interna da instituição (contrapartida dos lançamentos de depósito,
    // boleto e QR Code). Garante a partida dobrada (RN02/RN03).
    'conta_interna' => [
        'email' => env('FLUXO_TESOURARIA_EMAIL', 'tesouraria@fluxo.internal'),
        'numero' => env('FLUXO_TESOURARIA_CONTA', '0000-000001'),
    ],

    // Autoria/documentação
    'api' => [
        'versao' => 'v1',
        'openapi' => base_path('docs/openapi.yaml'),
    ],
];
