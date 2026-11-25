@extends('web.layouts.app', ['title' => 'Gerenciar conta'])
@section('content')
@php
    $admin = auth('web')->user()->perfil === 'ADMIN';
    $gerenciavel = !$conta->estaEncerrada() && $conta->tipo_conta !== 'INSTITUCIONAL';
    $kycPendente = in_array($conta->status_abertura, ['PENDENTE', 'EM_ANALISE'], true);
@endphp
<a class="back-link" href="{{ route('web.admin.accounts') }}">← Contas e KYC</a>
@include('web.components.heading', ['heading' => 'Conta '.$conta->numero, 'subtitle' => $conta->cliente?->nomeExibicao() ?? $conta->user?->name ?? 'Titular indisponível'])

<div class="grid grid-main">
    <div class="stack">
        <section class="panel">
            <div class="panel-header"><h2>Dados da conta</h2>
                @if($admin && $conta->user)<a class="text-link" href="{{ route('web.admin.users.edit', $conta->user) }}">Abrir usuário →</a>@endif
            </div>
            <dl class="account-summary">
                <div><dt>Status operacional</dt><dd>{{ ucfirst(strtolower($conta->status)) }}</dd></div>
                <div><dt>Status de KYC</dt><dd>{{ str_replace('_', ' ', ucfirst(strtolower($conta->status_abertura))) }}</dd></div>
                <div><dt>Tipo de conta</dt><dd>{{ $conta->tipo_conta }}</dd></div>
                <div><dt>Agência</dt><dd>{{ $conta->agencia }}</dd></div>
                @if($conta->cliente?->documentoMascarado())
                    <div><dt>{{ $conta->cliente->ehPessoaFisica() ? 'CPF' : 'CNPJ' }}</dt><dd>{{ $conta->cliente->documentoMascarado() }}</dd></div>
                @endif
                <div><dt>Data de cadastro</dt><dd>{{ $conta->created_at?->format('d/m/Y H:i') ?? '—' }}</dd></div>
            </dl>
        </section>

        @if($gerenciavel && $kycPendente)
            <section class="panel">
                <div class="panel-header"><h2>Decisão de KYC</h2></div>
                <form method="post" action="{{ route('web.admin.accounts.approve', $conta) }}" data-confirm="Aprovar a abertura desta conta?">
                    @csrf
                    <button class="btn btn-primary" type="submit">Aprovar abertura</button>
                </form>
                <hr class="panel-divider">
                <form method="post" action="{{ route('web.admin.accounts.reject', $conta) }}" class="stack" data-confirm="Recusar a abertura desta conta?">
                    @csrf
                    <div class="field"><label for="motivo-recusa">Motivo da recusa</label><textarea id="motivo-recusa" name="motivo" maxlength="190" rows="2" required></textarea></div>
                    <div><button class="btn btn-danger" type="submit">Recusar abertura</button></div>
                </form>
            </section>
        @endif

        @if($gerenciavel && $admin)
            <section class="panel">
                <div class="panel-header"><h2>Limites Pix</h2></div>
                <form method="post" action="{{ route('web.admin.accounts.limits', $conta) }}" class="stack">
                    @csrf
                    @method('PATCH')
                    <div class="form-grid">
                        <div class="field"><label for="por_transacao">Por transação (centavos)</label><input id="por_transacao" name="por_transacao" type="number" min="1" value="{{ old('por_transacao', $politica['por_transacao']) }}" required></div>
                        <div class="field"><label for="diario">Diário (centavos)</label><input id="diario" name="diario" type="number" min="1" value="{{ old('diario', $politica['diario']) }}" required></div>
                        <div class="field"><label for="noturno">Por transação à noite (centavos)</label><input id="noturno" name="noturno" type="number" min="1" value="{{ old('noturno', $politica['noturno_por_transacao']) }}" required></div>
                    </div>
                    <div><button class="btn btn-outline" type="submit">Salvar limites</button></div>
                </form>
            </section>
        @endif
    </div>

    @if($gerenciavel)
        <div class="stack">
            <section class="panel">
                <div class="panel-header"><h2>Bloqueio da conta</h2></div>
                @if($conta->bloqueio_backoffice)
                    <p class="muted">A conta está bloqueada por suspeita de fraude.</p>
                @else
                    <form method="post" action="{{ route('web.admin.accounts.block', $conta) }}" class="stack" data-confirm="Bloquear esta conta por suspeita de fraude?">
                        @csrf
                        <div class="field"><label for="motivo-fraude">Motivo do bloqueio por fraude</label><textarea id="motivo-fraude" name="motivo" maxlength="190" rows="2" required></textarea></div>
                        <div><button class="btn btn-outline" type="submit">Bloquear conta</button></div>
                    </form>
                @endif
                @if($admin && $conta->bloqueada && $conta->status_abertura === 'APROVADA')
                    <form class="form-actions" method="post" action="{{ route('web.admin.accounts.unblock', $conta) }}" data-confirm="Desbloquear esta conta?">
                        @csrf
                        <button class="btn btn-primary" type="submit">Desbloquear conta</button>
                    </form>
                @endif
            </section>

            @if($admin && $conta->user)
                <section class="panel">
                    <h2>Recuperação de acesso</h2>
                    <p class="muted sm">Valide a identidade do titular antes de entregar o código temporário.</p>
                    <form method="post" action="{{ route('web.admin.recovery.code') }}" data-confirm="Emitir código de recuperação para esta conta?">
                        @csrf
                        <input type="hidden" name="conta_id" value="{{ $conta->id }}">
                        <button class="btn btn-outline" type="submit">Emitir código</button>
                    </form>
                    @if(session('codigo_recuperacao'))
                        <p class="info-box" role="status">Código temporário <strong>{{ session('codigo_recuperacao') }}</strong></p>
                    @endif
                </section>
                <section class="panel danger-panel">
                    <h2>Encerrar conta</h2>
                    <p class="muted sm">A conta precisa ter saldo zerado e nenhum agendamento pendente.</p>
                    <form method="post" action="{{ route('web.admin.accounts.close', $conta) }}" data-confirm="Encerrar esta conta bancária?">
                        @csrf
                        @method('DELETE')
                        <button type="submit" class="btn btn-danger">Encerrar conta</button>
                    </form>
                </section>
            @endif
        </div>
    @endif
</div>
@endsection
