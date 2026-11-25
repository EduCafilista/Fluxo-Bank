@extends('web.layouts.app', ['title' => 'Novo usuário'])
@section('content')
<a class="back-link" href="{{ route('web.admin.users.index') }}">← Usuários</a>
@include('web.components.heading', ['heading' => 'Novo usuário', 'subtitle' => 'Selecione o perfil e preencha os dados de acesso.'])
<section class="panel form-panel">
    <form method="post" action="{{ route('web.admin.users.store') }}" class="stack" data-registration>
        @csrf
        <div class="field">
            <label for="perfil">Perfil de acesso</label>
            <select id="perfil" name="perfil" data-register-role>
                <option value="CLIENTE" @selected(old('perfil') === 'CLIENTE')>Cliente</option>
                <option value="ATENDENTE" @selected(old('perfil') === 'ATENDENTE')>Atendente</option>
                <option value="ADMIN" @selected(old('perfil') === 'ADMIN')>Administrador</option>
            </select>
        </div>
        @include('web.components.registration-fields', ['administrativo' => true])
        <div class="form-actions">
            <button class="btn btn-primary" type="submit">Criar usuário</button>
            <a class="btn btn-muted" href="{{ route('web.admin.users.index') }}">Cancelar</a>
        </div>
    </form>
</section>
@endsection
