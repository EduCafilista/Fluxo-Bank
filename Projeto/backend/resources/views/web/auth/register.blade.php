@extends('web.layouts.guest', ['title' => 'Criar conta'])
@section('content')
<h1>Abra sua conta Fluxo</h1>
<p class="lead">Preencha seus dados para solicitar a abertura. Você poderá acompanhar a análise ao entrar.</p>
<form method="post" action="{{ route('web.register.store') }}" class="stack registration-form" data-registration>
    @csrf
    @include('web.components.registration-fields')
    <button class="btn btn-primary btn-full" type="submit">Criar minha conta</button>
</form>
<p class="guest-link-footer">Já possui conta? <a href="{{ route('web.login') }}">Entrar</a></p>
@endsection
