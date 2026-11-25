@extends('web.layouts.app', ['title' => 'Visão geral'])
@section('content')
<div class="page-heading management-heading">
    <div><h1>Painel administrativo</h1><p class="page-subtitle">Acompanhe novos cadastros e as operações do Fluxo Bank.</p></div>
    @if(auth('web')->user()->perfil === 'ADMIN')
        <a class="btn btn-primary" href="{{ route('web.admin.users.create') }}">@include('web.components.icon', ['name' => 'users']) Novo usuário</a>
    @endif
</div>

<div class="grid grid-3 admin-stats">
    @foreach([
        ['Contas cadastradas', $contas, 'wallet', '', route('web.admin.accounts')],
        ['Aguardando análise de KYC', $pendentes, 'clock', 'orange', route('web.admin.accounts', ['status_abertura' => 'EM_ANALISE'])],
        ['Chamados abertos', $chamadosAbertos, 'headphones', 'orange', route('web.admin.tickets', ['status' => 'ABERTO'])],
        ['Clientes cadastrados', $clientes, 'users', '', auth('web')->user()->perfil === 'ADMIN' ? route('web.admin.users.index', ['perfil' => 'CLIENTE']) : route('web.admin.accounts')],
        ['Transações hoje', $transacoesHoje, 'arrow-up-right', 'green', null],
        ['Eventos de auditoria', $registrosAuditoria, 'shield', '', route('web.admin.audit')],
    ] as [$rotulo, $valor, $icone, $cor, $url])
        <div class="stat-card">
            <div>
                <p class="stat-label">{{ $rotulo }}</p>
                <div class="stat-number">{{ number_format($valor, 0, ',', '.') }}</div>
            </div>
            <span class="stat-icon {{ $cor }}">
                @if($url)
                    <a href="{{ $url }}" aria-label="{{ $rotulo }}">@include('web.components.icon', ['name' => $icone])</a>
                @else
                    @include('web.components.icon', ['name' => $icone])
                @endif
            </span>
        </div>
    @endforeach
</div>

<div class="grid grid-main management-section">
    <section class="panel">
        <div class="panel-header"><h2>Contas recentes</h2><a class="text-link" href="{{ route('web.admin.accounts') }}">Ver contas →</a></div>
        @forelse($recentes as $conta)
            <a class="detail-item linked-row" href="{{ route('web.admin.accounts.show', $conta) }}">
                <div>
                    <strong>{{ $conta->cliente?->nomeExibicao() ?? $conta->user?->name ?? 'Titular indisponível' }}</strong>
                    <div class="muted sm">{{ $conta->numero }} · {{ $conta->created_at?->format('d/m/Y') }}</div>
                </div>
                <span class="pill {{ $conta->status_abertura === 'APROVADA' ? 'green' : ($conta->status_abertura === 'RECUSADA' ? 'red' : 'orange') }}">
                    {{ str_replace('_', ' ', ucfirst(strtolower($conta->status_abertura))) }}
                </span>
            </a>
        @empty
            @include('web.components.empty', ['icon' => 'wallet', 'heading' => 'Nenhuma conta cadastrada'])
        @endforelse
    </section>
    <section class="panel">
        <div class="panel-header"><h2>Últimos eventos</h2><a class="text-link" href="{{ route('web.admin.audit') }}">Ver auditoria →</a></div>
        @forelse($ultimosLogs as $log)
            <a class="detail-item linked-row" href="{{ route('web.admin.audit.show', $log) }}">
                <div><strong>{{ $log->operacao }}</strong><div class="muted sm">{{ $log->tabela }} · {{ $log->registrado_em?->format('d/m/Y H:i') }}</div></div>
                @include('web.components.icon', ['name' => 'chevron-right'])
            </a>
        @empty
            @include('web.components.empty', ['icon' => 'shield', 'heading' => 'Nenhum evento ainda'])
        @endforelse
    </section>
</div>
@endsection
