@extends('web.layouts.app', ['title' => 'Editar usuário'])
@section('content')
@php
    $cliente = $usuario->cliente;
    $pj = $cliente?->tipo_cliente === 'PJ';
    $proprio = $usuario->id === auth('web')->id();
    $institucional = $usuario->contas->contains('tipo_conta', 'INSTITUCIONAL');
@endphp

<a class="back-link" href="{{ route('web.admin.users.index') }}">← Usuários</a>
@include('web.components.heading', ['heading' => $usuario->name, 'subtitle' => $usuario->email])
<div class="grid grid-main">
    <section class="panel">
        <div class="panel-header"><h2>Dados cadastrais</h2></div>
        @if($institucional)
            <p class="muted">Cadastro vinculado à tesouraria do Fluxo.</p>
        @else
            <form method="post" action="{{ route('web.admin.users.update', $usuario) }}" class="stack">
                @csrf
                @method('PATCH')
                <div class="form-grid">
                    @if($pj)
                        <div class="field"><label for="razao_social">Razão social</label><input id="razao_social" name="razao_social" value="{{ old('razao_social', $cliente->razao_social ?? $cliente->nome) }}" required></div>
                        <div class="field"><label for="representante_legal">Representante legal</label><input id="representante_legal" name="representante_legal" value="{{ old('representante_legal', $cliente->representante_legal) }}" required></div>
                    @else
                        <div class="field"><label for="name">Nome completo</label><input id="name" name="name" value="{{ old('name', $usuario->name) }}" required></div>
                        @if($cliente)
                            <div class="field"><label for="data_nascimento">Data de nascimento</label><input id="data_nascimento" type="date" name="data_nascimento" value="{{ old('data_nascimento', $cliente->data_nascimento?->format('Y-m-d')) }}" required></div>
                        @endif
                    @endif
                    <div class="field"><label for="email">E-mail de acesso</label><input id="email" name="email" type="email" value="{{ old('email', $usuario->email) }}" required></div>
                    <div class="field"><label for="perfil">Perfil</label>
                        <select id="perfil" name="perfil" @disabled($proprio)>
                            @if($cliente)
                                <option value="CLIENTE" @selected(old('perfil', $usuario->perfil) === 'CLIENTE')>Cliente</option>
                            @endif
                            <option value="ATENDENTE" @selected(old('perfil', $usuario->perfil) === 'ATENDENTE')>Atendente</option>
                            <option value="ADMIN" @selected(old('perfil', $usuario->perfil) === 'ADMIN')>Administrador</option>
                        </select>
                    </div>
                    <div class="field"><label for="password">Nova senha <span class="optional">(opcional)</span></label><input id="password" name="password" type="password" minlength="8" maxlength="72" autocomplete="new-password"></div>
                    <div class="field"><label for="password_confirmation">Confirmar nova senha</label><input id="password_confirmation" name="password_confirmation" type="password" autocomplete="new-password"></div>
                </div>
                <p class="muted sm">Alterar o e-mail, a senha ou o perfil encerra as sessões ativas desse usuário.</p>
                <div><button class="btn btn-primary" type="submit">Salvar alterações</button></div>
            </form>
        @endif
    </section>
    <div class="stack">
        <section class="panel">
            <div class="panel-header"><h2>Contas vinculadas</h2></div>
            @if($cliente?->documentoMascarado())<p class="muted">{{ $cliente->tipo_cliente }} · {{ $cliente->documentoMascarado() }}</p>@endif
            @forelse($usuario->contas as $conta)
                <a class="detail-item" href="{{ route('web.admin.accounts.show', $conta) }}"><strong>{{ $conta->numero }}</strong><span class="pill">{{ $conta->status }}</span></a>
            @empty
                <p class="muted">Sem conta bancária vinculada.</p>
            @endforelse
        </section>
        @if(!$proprio && !$institucional)
            <section class="panel danger-panel">
                <h2>Excluir usuário</h2>
                <p class="muted">O acesso será encerrado e os dados cadastrais serão anonimizados. Contas vinculadas precisam ter saldo zerado e nenhum agendamento pendente.</p>
                <form method="post" action="{{ route('web.admin.users.destroy', $usuario) }}" data-confirm="Confirma a exclusão de {{ $usuario->name }}?">
                    @csrf
                    @method('DELETE')
                    <label class="check-label"><input type="checkbox" name="confirmacao" value="1" required> Confirmo a exclusão deste usuário.</label>
                    <div class="form-actions"><button class="btn btn-danger" type="submit">Excluir usuário</button></div>
                </form>
            </section>
        @endif
    </div>
</div>
@endsection
