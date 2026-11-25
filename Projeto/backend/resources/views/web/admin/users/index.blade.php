@extends('web.layouts.app', ['title' => 'Usuários'])
@section('content')
<div class="page-heading management-heading">
    <div><h1>Usuários</h1><p class="page-subtitle">Gerencie clientes, atendentes e administradores.</p></div>
    <a class="btn btn-primary" href="{{ route('web.admin.users.create') }}">
        @include('web.components.icon', ['name' => 'user']) Novo usuário
    </a>
</div>

<section class="panel filter-panel" aria-label="Filtros de usuários">
    <form method="get" class="filter-row">
        <div class="field filter-search">
            <label for="busca">Buscar usuário</label>
            <input id="busca" name="busca" value="{{ request('busca') }}" placeholder="Nome, e-mail ou número da conta" maxlength="100" type="search">
        </div>
        <div class="field">
            <label for="perfil">Perfil</label>
            <select id="perfil" name="perfil">
                <option value="">Todos</option>
                <option value="CLIENTE" @selected(request('perfil') === 'CLIENTE')>Cliente</option>
                <option value="ATENDENTE" @selected(request('perfil') === 'ATENDENTE')>Atendente</option>
                <option value="ADMIN" @selected(request('perfil') === 'ADMIN')>Administrador</option>
            </select>
        </div>
        <button class="btn btn-outline" type="submit">Filtrar</button>
        @if(request()->filled('busca') || request()->filled('perfil'))
            <a class="btn btn-muted" href="{{ route('web.admin.users.index') }}">Limpar</a>
        @endif
    </form>
</section>

<section class="panel management-table">
    <div class="panel-header">
        <h2>Lista de usuários</h2>
        <span class="pill blue">{{ $usuarios->total() }} {{ $usuarios->total() === 1 ? 'registro' : 'registros' }}</span>
    </div>
    @if($usuarios->isEmpty())
        @include('web.components.empty', ['icon' => 'users', 'heading' => 'Nenhum usuário encontrado', 'description' => 'Revise os filtros ou cadastre um usuário.'])
    @else
        <div class="table-scroll" role="region" aria-label="Lista de usuários" tabindex="0">
            <table class="data-table responsive-table">
                <thead><tr><th scope="col">Usuário</th><th scope="col">Perfil</th><th scope="col">Conta e KYC</th><th scope="col">Cadastro</th><th scope="col">Ações</th></tr></thead>
                <tbody>
                @foreach($usuarios as $usuario)
                    <tr>
                        <td data-label="Usuário">
                            <div class="table-identity">
                                <span class="avatar" aria-hidden="true">{{ mb_strtoupper(mb_substr($usuario->name, 0, 1)) }}</span>
                                <div><strong>{{ $usuario->name }}</strong><div class="cell-secondary">{{ $usuario->email }}</div></div>
                            </div>
                        </td>
                        <td data-label="Perfil"><span class="pill {{ $usuario->ehBackoffice() ? 'blue' : '' }}">{{ $usuario->perfil === 'ADMIN' ? 'Administrador' : ucfirst(strtolower($usuario->perfil)) }}</span></td>
                        <td data-label="Conta e KYC">
                            @if($usuario->conta)
                                <a class="text-link" href="{{ route('web.admin.accounts.show', $usuario->conta) }}">{{ $usuario->conta->numero }}</a>
                                <div class="cell-secondary">{{ str_replace('_', ' ', ucfirst(strtolower($usuario->conta->status_abertura))) }}</div>
                            @else
                                <span class="muted">Sem conta bancária</span>
                            @endif
                        </td>
                        <td data-label="Cadastro">{{ $usuario->created_at?->format('d/m/Y') ?? '—' }}</td>
                        <td data-label="Ações"><a class="btn btn-outline btn-sm" href="{{ route('web.admin.users.edit', $usuario) }}" aria-label="Editar {{ $usuario->name }}">Editar</a></td>
                    </tr>
                @endforeach
                </tbody>
            </table>
        </div>
        @include('web.components.pagination', ['paginator' => $usuarios])
    @endif
</section>
@endsection
