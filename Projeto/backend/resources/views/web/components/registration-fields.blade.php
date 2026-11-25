@php
    $cliente = !($administrativo ?? false) || old('perfil', 'CLIENTE') === 'CLIENTE';
    $pj = $cliente && old('tipo_cliente', 'PF') === 'PJ';
@endphp
<div class="field" data-register-client @if(!$cliente) hidden @endif>
    <label for="tipo_cliente">Tipo de pessoa</label>
    <select id="tipo_cliente" name="tipo_cliente" data-register-type>
        <option value="PF" @selected(!$pj)>Pessoa física</option>
        <option value="PJ" @selected($pj)>Pessoa jurídica</option>
    </select>
</div>

<div class="field" data-register-name @if($pj) hidden @endif>
    <label for="nome">Nome completo</label>
    <input id="nome" name="nome" value="{{ old('nome') }}" autocomplete="name" maxlength="120" @required(!$pj) @disabled($pj)>
</div>

<div class="form-grid" data-register-pf @if(!$cliente || $pj) hidden @endif>
    <div class="field"><label for="cpf">CPF</label><input id="cpf" name="cpf" value="{{ old('cpf') }}" inputmode="numeric" maxlength="14" placeholder="000.000.000-00" @required($cliente && !$pj) @disabled(!$cliente || $pj)></div>
    <div class="field"><label for="data_nascimento">Data de nascimento</label><input id="data_nascimento" type="date" name="data_nascimento" value="{{ old('data_nascimento') }}" autocomplete="bday" @required($cliente && !$pj) @disabled(!$cliente || $pj)></div>
    <div class="field"><label for="tipo_conta">Tipo de conta</label>
        <select id="tipo_conta" name="tipo_conta" @disabled(!$cliente || $pj)>
            <option value="CORRENTE" @selected(old('tipo_conta') === 'CORRENTE')>Corrente</option>
            <option value="POUPANCA" @selected(old('tipo_conta') === 'POUPANCA')>Poupança</option>
            <option value="SALARIO" @selected(old('tipo_conta') === 'SALARIO')>Salário</option>
        </select>
    </div>
</div>

<div class="form-grid" data-register-pj @if(!$pj) hidden @endif>
    <div class="field"><label for="razao_social">Razão social</label><input id="razao_social" name="razao_social" value="{{ old('razao_social') }}" maxlength="150" @required($pj) @disabled(!$pj)></div>
    <div class="field"><label for="cnpj">CNPJ</label><input id="cnpj" name="cnpj" value="{{ old('cnpj') }}" inputmode="numeric" maxlength="18" placeholder="00.000.000/0000-00" @required($pj) @disabled(!$pj)></div>
    <div class="field"><label for="representante_legal">Representante legal</label><input id="representante_legal" name="representante_legal" value="{{ old('representante_legal') }}" maxlength="150" @required($pj) @disabled(!$pj)></div>
</div>

<div class="field"><label for="email">E-mail</label><input id="email" name="email" type="email" value="{{ old('email') }}" autocomplete="email" maxlength="255" required></div>
<div class="form-grid">
    <div class="field"><label for="password">Senha</label><input id="password" name="password" type="password" autocomplete="new-password" minlength="8" maxlength="72" aria-describedby="password-hint" required><small id="password-hint" class="muted">Mínimo de 8 caracteres.</small></div>
    <div class="field"><label for="password_confirmation">Confirmar senha</label><input id="password_confirmation" name="password_confirmation" type="password" autocomplete="new-password" required></div>
</div>
