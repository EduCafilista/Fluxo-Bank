@extends('web.layouts.guest',['title'=>'Entrar'])
@section('content')
<p class="eyebrow">SEJA BEM-VINDO</p><h1>Bem-vindo ao Fluxo</h1><p class="lead">Seu dinheiro, no seu ritmo. Entre na sua conta.</p>
<form method="post" action="{{ route('web.login.store') }}">@csrf
  <div class="field"><label for="email">E-mail</label><input id="email" name="email" type="email" value="{{ old('email') }}" placeholder="seu@email.com" autocomplete="username" required autofocus></div>
  <div class="field"><label for="password">Senha</label><div class="input-wrap"><input id="password" name="password" type="password" placeholder="Digite sua senha" autocomplete="current-password" required><button class="toggle-password" type="button" data-toggle-password="password" aria-label="Mostrar senha">@include('web.components.icon',['name'=>'eye'])</button></div></div>
  <div class="guest-form-extra"><a href="{{ route('web.recovery') }}">Esqueci minha senha</a></div>
  <button class="btn btn-primary btn-full" type="submit">Entrar @include('web.components.icon',['name'=>'arrow-up-right'])</button>
</form>
<p class="guest-link-footer">Ainda não tem uma conta? <a href="{{ route('web.register') }}">Criar minha conta</a></p>
<div class="login-feature-badges">@include('web.components.icon',['name'=>'shield'])<span>Acesso protegido</span><span>•</span><span>Seus dados são tratados com segurança</span></div>
@endsection
