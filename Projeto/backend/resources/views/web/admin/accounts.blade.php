@extends('web.layouts.app', ['title' => 'Contas e KYC'])
@section('content')
@include('web.components.heading', ['heading' => 'Contas e KYC', 'subtitle' => 'Analise aberturas e acompanhe a situação das contas.'])
<section class="panel filter-panel" aria-label="Filtros de contas">
    <form method="get" class="filter-row">
        <div class="field filter-search"><label for="busca">Buscar conta</label><input id="busca" name="busca" type="search" maxlength="100" value="{{ request('busca') }}" placeholder="Titular, e-mail ou número da conta"></div>
        <div class="field"><label for="status">Status operacional</label><select id="status" name="status">
            <option value="">Todos</option>
            @foreach(['PENDENTE', 'ATIVA', 'BLOQUEADA', 'ENCERRADA'] as $status)
                <option value="{{ $status }}" @selected(request('status') === $status)>{{ ucfirst(strtolower($status)) }}</option>
            @endforeach
        </select></div>
        <div class="field"><label for="status_abertura">Status de KYC</label><select id="status_abertura" name="status_abertura">
            <option value="">Todos</option>
            @foreach(['PENDENTE', 'EM_ANALISE', 'APROVADA', 'RECUSADA'] as $status)
                <option value="{{ $status }}" @selected(request('status_abertura') === $status)>{{ str_replace('_', ' ', ucfirst(strtolower($status))) }}</option>
            @endforeach
        </select></div>
        <button class="btn btn-outline">Filtrar</button>
        @if(request()->anyFilled(['busca', 'status', 'status_abertura']))
            <a class="btn btn-muted" href="{{ route('web.admin.accounts') }}">Limpar</a>
        @endif
    </form>
</section>

<section class="panel management-table">
    <div class="panel-header"><h2>Lista de contas</h2><span class="pill blue">{{ $contas->total() }} {{ $contas->total() === 1 ? 'conta' : 'contas' }}</span></div>
    @if($contas->isEmpty())
        @include('web.components.empty', ['icon' => 'wallet', 'heading' => 'Nenhuma conta encontrada'])
    @else
        <div class="table-scroll" role="region" aria-label="Lista de contas" tabindex="0">
            <table class="data-table responsive-table">
                <thead><tr><th scope="col">Conta</th><th scope="col">Titular</th><th scope="col">Status</th><th scope="col">KYC</th><th scope="col">Ações</th></tr></thead>
                <tbody>
                @foreach($contas as $conta)
                    <tr>
                        <td data-label="Conta"><strong>{{ $conta->numero }}</strong><div class="cell-secondary">{{ ucfirst(strtolower($conta->tipo_conta)) }} · Ag. {{ $conta->agencia }}</div></td>
                        <td data-label="Titular"><strong>{{ $conta->cliente?->nomeExibicao() ?? $conta->user?->name ?? '—' }}</strong><div class="cell-secondary">{{ $conta->cliente?->documentoMascarado() }}</div></td>
                        <td data-label="Status"><span class="pill {{ $conta->status === 'ATIVA' ? 'green' : ($conta->status === 'BLOQUEADA' ? 'red' : '') }}">{{ ucfirst(strtolower($conta->status)) }}</span></td>
                        <td data-label="KYC"><span class="pill {{ $conta->status_abertura === 'APROVADA' ? 'green' : ($conta->status_abertura === 'RECUSADA' ? 'red' : 'orange') }}">{{ str_replace('_', ' ', ucfirst(strtolower($conta->status_abertura))) }}</span></td>
                        <td data-label="Ações"><a class="btn btn-outline btn-sm" href="{{ route('web.admin.accounts.show', $conta) }}" aria-label="Gerenciar conta {{ $conta->numero }}">Gerenciar</a></td>
                    </tr>
                @endforeach
                </tbody>
            </table>
        </div>
        @include('web.components.pagination', ['paginator' => $contas])
    @endif
</section>
@endsection
