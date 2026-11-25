@extends('web.layouts.app', ['title' => 'Confirmar Pix'])
@section('content')
<a class="back-link" href="{{ route('web.payments') }}">← Pagamentos</a>
@include('web.components.heading', ['heading' => 'Confirmar pagamento Pix', 'subtitle' => 'Confira o favorecido e o valor.'])
<section class="panel form-panel">
    <dl class="account-summary">
        <div><dt>Favorecido</dt><dd>{{ $pagamento['favorecido'] }}</dd></div>
        <div><dt>Conta</dt><dd>{{ $pagamento['conta_numero'] }}</dd></div>
        <div><dt>Valor</dt><dd>R$ {{ number_format($pagamento['valor_centavos'] / 100, 2, ',', '.') }}</dd></div>
        <div><dt>Cobrança</dt><dd>{{ $pagamento['txid'] }}</dd></div>
    </dl>
    <form method="post" action="{{ route('web.payments.pix.pay') }}" class="form-actions">
        @csrf
        <button class="btn btn-primary" type="submit">Confirmar pagamento</button>
        <a href="{{ route('web.payments') }}" class="btn btn-muted">Cancelar</a>
    </form>
</section>
@endsection
