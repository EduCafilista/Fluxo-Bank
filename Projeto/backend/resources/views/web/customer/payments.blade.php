@extends('web.layouts.app', ['title' => 'Pagamentos'])
@section('content')
@include('web.components.heading', ['heading' => 'Pagamentos', 'subtitle' => 'Revise o valor e os dados antes de confirmar.'])
<div class="grid grid-2">
    <section class="panel">
        <div class="panel-header"><h2>Pagar com Pix</h2>@include('web.components.icon', ['name' => 'pix'])</div>
        <form method="post" action="{{ route('web.payments.pix.review') }}" class="stack">
            @csrf
            <div class="field"><label for="payload_emv">Pix Copia e Cola</label><textarea id="payload_emv" name="payload_emv" rows="3" placeholder="Cole o código da cobrança" maxlength="512" required>{{ old('payload_emv') }}</textarea></div>
            <div class="field" data-pix-image-control hidden>
                <label for="pix_image">Ler imagem de um QR Code</label>
                <input type="file" id="pix_image" accept="image/*" data-pix-image aria-describedby="pix-image-status">
                <small id="pix-image-status" role="status"></small>
            </div>
            <div><button class="btn btn-primary" type="submit">Revisar Pix</button></div>
        </form>
    </section>
    <section class="panel">
        <div class="panel-header"><h2>Pagar boleto</h2>@include('web.components.icon', ['name' => 'receipt'])</div>
        <form method="post" action="{{ route('web.payments.review') }}" class="stack">
            @csrf
            <div class="field"><label for="linha_digitavel">Linha digitável ou código de barras</label><input id="linha_digitavel" name="linha_digitavel" inputmode="numeric" value="{{ old('linha_digitavel') }}" placeholder="44, 47 ou 48 dígitos" maxlength="70" required></div>
            <div class="field"><label for="valor">Valor do boleto (R$)</label><input id="valor" name="valor" placeholder="149,90" inputmode="decimal" value="{{ old('valor') }}" required></div>
            <div><button class="btn btn-outline" type="submit">Revisar boleto</button></div>
        </form>
    </section>
</div>
@endsection
