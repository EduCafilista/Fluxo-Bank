@extends('web.layouts.app', ['title' => 'Solicitações de abertura'])
@section('content')
@include('web.components.heading', ['heading' => 'Solicitações de abertura', 'subtitle' => 'Contas aguardando análise do backoffice.'])
<section class="panel">
    <div class="panel-header"><h2>Pendentes</h2><span class="pill orange">{{ $solicitacoes->total() }}</span></div>
    @if($solicitacoes->isEmpty())
        @include('web.components.empty', ['heading' => 'Nenhuma solicitação pendente'])
    @else
        <div class="table-scroll"><table class="data-table responsive-table">
        <thead><tr><th>Data</th><th>Solicitante</th><th>Conta</th><th>Status</th><th>Ação</th></tr></thead>
        <tbody>@foreach($solicitacoes as $conta)
        <tr>
            <td data-label="Data">{{ $conta->created_at?->format('d/m/Y H:i') }}</td>
            <td data-label="Solicitante"><strong>{{ $conta->cliente?->nomeExibicao() ?? $conta->user?->name }}</strong><div class="cell-secondary">{{ $conta->user?->email }}</div></td>
            <td data-label="Conta">{{ $conta->numero }}</td>
            <td data-label="Status">{{ $conta->status_abertura }}</td>
            <td data-label="Ação"><a href="{{ route('web.admin.accounts.show', $conta) }}" class="btn btn-outline btn-sm">Analisar</a></td>
        </tr>@endforeach</tbody></table></div>
        @include('web.components.pagination', ['paginator'=>$solicitacoes])
    @endif
</section>
@endsection
