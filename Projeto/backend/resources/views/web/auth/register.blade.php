@extends('web.layouts.guest', ['title' => 'Criar conta'])
@section('content')
<h1>Abra sua conta Fluxo</h1>
<p class="lead">Preencha seus dados para solicitar a abertura. Você poderá acompanhar a análise ao entrar.</p>
<form method="post" action="{{ route('web.register.store') }}" class="stack registration-form" data-registration>
    @csrf
    @include('web.components.registration-fields')
    <p class="hint">Este cadastro abre uma solicitação de conta para análise. Você será informado sobre a decisão por e-mail, quando o serviço de envio estiver habilitado.</p>
    <button class="btn btn-primary btn-full" type="submit">Enviar solicitação</button>
</form>
<p class="guest-link-footer">Já possui conta? <a href="{{ route('web.login') }}">Entrar</a></p>
@endsection
