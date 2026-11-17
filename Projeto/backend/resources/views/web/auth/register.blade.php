@extends('web.layouts.guest',['title'=>'Criar conta'])
@section('content')
<p class="eyebrow">ABERTURA DE CONTA</p><h1>Seu próximo passo começa aqui.</h1><p class="lead">Preencha seus dados. A aprovação depende das regras de KYC do Fluxo.</p>
<form method="post" action="{{ route('web.register.store') }}">@csrf
 <div class="field"><label for="tipo_cliente">Tipo de cadastro</label><select id="tipo_cliente" name="tipo_cliente" data-pj-toggle><option value="PF" {{ old('tipo_cliente')==='PF'?'selected':'' }}>Pessoa física</option><option value="PJ" {{ old('tipo_cliente')==='PJ'?'selected':'' }}>Pessoa jurídica</option></select></div>
 <div data-only-pf {{ old('tipo_cliente','PF')==='PJ'?'hidden':'' }}>
  <div class="field"><label for="nome">Nome completo</label><input id="nome" name="nome" value="{{ old('nome') }}" autocomplete="name" required></div>
  <div class="field"><label for="cpf">CPF</label><input id="cpf" name="cpf" value="{{ old('cpf') }}" inputmode="numeric" maxlength="14" placeholder="Somente números" required></div>
  <div class="field"><label for="data_nascimento">Data de nascimento</label><input id="data_nascimento" type="date" name="data_nascimento" value="{{ old('data_nascimento') }}" required></div>
  <div class="field"><label for="tipo_conta">Tipo de conta</label><select id="tipo_conta" name="tipo_conta"><option value="CORRENTE">Corrente</option><option value="POUPANCA">Poupança</option><option value="SALARIO">Salário</option></select></div>
 </div>
 <div data-only-pj {{ old('tipo_cliente','PF')==='PJ'?'':'hidden' }}>
  <div class="field"><label for="razao_social">Razão social</label><input id="razao_social" name="razao_social" value="{{ old('razao_social') }}" {{ old('tipo_cliente')==='PJ'?'required':'' }}></div>
  <div class="field"><label for="cnpj">CNPJ</label><input id="cnpj" name="cnpj" value="{{ old('cnpj') }}" inputmode="numeric" placeholder="Somente números" {{ old('tipo_cliente')==='PJ'?'required':'' }}></div>
  <div class="field"><label for="representante_legal">Representante legal</label><input id="representante_legal" name="representante_legal" value="{{ old('representante_legal') }}" {{ old('tipo_cliente')==='PJ'?'required':'' }}></div>
 </div>
 <div class="field"><label for="email">E-mail</label><input id="email" name="email" type="email" value="{{ old('email') }}" autocomplete="email" required></div>
 <div class="field"><label for="password">Senha <span class="optional">(mínimo de 8 caracteres)</span></label><input id="password" name="password" type="password" autocomplete="new-password" minlength="8" required></div>
 <div class="field"><label for="password_confirmation">Confirmar senha</label><input id="password_confirmation" name="password_confirmation" type="password" autocomplete="new-password" minlength="8" required></div>
 <button class="btn btn-primary btn-full" type="submit">Criar minha conta</button>
</form>
<p class="guest-link-footer">Já possui conta? <a href="{{ route('web.login') }}">Entrar</a></p>
@endsection
