@extends('web.layouts.guest',['title'=>'Recuperar acesso'])
@section('content')
<p class="eyebrow">RECUPERAÇÃO DE ACESSO</p><h1>Vamos recuperar seu acesso.</h1><p class="lead">Por segurança, a recuperação utiliza verificação manual e um código temporário emitido pelo suporte autorizado.</p>
@if(!session()->has('fluxo_recuperacao'))
 <form method="post" action="{{ route('web.recovery.request') }}">@csrf
  <div class="field"><label for="identificador">E-mail, CPF ou telefone</label><input id="identificador" name="identificador" value="{{ old('identificador') }}" required placeholder="Identificador cadastrado"></div>
  <button class="btn btn-primary btn-full">Solicitar recuperação</button>
 </form>
 <div class="divider"></div><h3>Já recebeu o código?</h3>
 <form method="post" action="{{ route('web.recovery.validate') }}">@csrf
  <div class="field"><label for="identificador2">Identificador cadastrado</label><input id="identificador2" name="identificador" value="{{ old('identificador') }}" required></div>
  <div class="field"><label for="codigo">Código temporário de 6 dígitos</label><input id="codigo" name="codigo" pattern="[0-9]{6}" maxlength="6" inputmode="numeric" required></div>
  <button class="btn btn-outline btn-full">Validar código</button>
 </form>
@else
 <form method="post" action="{{ route('web.recovery.reset') }}">@csrf
  <div class="field"><label for="password">Nova senha</label><input id="password" name="password" type="password" minlength="8" autocomplete="new-password" required></div>
  <div class="field"><label for="password_confirmation">Confirme a nova senha</label><input id="password_confirmation" name="password_confirmation" type="password" minlength="8" autocomplete="new-password" required></div>
  <button class="btn btn-primary btn-full">Redefinir senha</button>
 </form>
@endif
<p class="guest-link-footer"><a href="{{ route('web.login') }}">Voltar ao login</a></p>
@endsection
