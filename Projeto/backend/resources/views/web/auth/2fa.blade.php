@extends('web.layouts.guest',['title'=>'Verificar acesso'])
@section('content')
<p class="eyebrow">SEGURANÇA</p><h1>Confirme seu acesso</h1><p class="lead">Informe o código de 6 dígitos enviado pelo canal cadastrado. O código expira rapidamente por segurança.</p>
<form method="post" action="{{ route('web.2fa.verify') }}">@csrf
 <div class="field"><label for="codigo">Código de verificação</label><input id="codigo" name="codigo" type="text" pattern="[0-9]{6}" minlength="6" maxlength="6" inputmode="numeric" autocomplete="one-time-code" placeholder="000000" required autofocus></div>
 <button class="btn btn-primary btn-full" type="submit">Validar código</button>
</form>
<p class="guest-link-footer"><a href="{{ route('web.login') }}">Voltar ao login</a></p>
@endsection
