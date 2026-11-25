@extends('web.layouts.app', ['title' => 'Viagens'])

@section('content')
@include('web.components.heading', ['heading' => 'Avisos de viagem', 'subtitle' => 'Informe em quais localidades você vai usar seu cartão.'])

<div class="grid grid-main">
    <section class="panel">
        <div class="panel-header"><h2>Seus avisos</h2></div>
        @forelse($avisos as $aviso)
            <div class="detail-item billing-row">
                <div>
                    <strong>{{ implode(', ', $aviso->localidades ?? []) }}</strong>
                    <div class="muted sm">{{ $aviso->data_inicio?->format('d/m/Y') }} a {{ $aviso->data_fim?->format('d/m/Y') }}</div>
                    @if($aviso->cartao)
                        <div class="muted sm">Cartão final {{ substr($aviso->cartao->numero_mascarado, -4) }}</div>
                    @endif
                </div>
                <div class="compact-actions">
                    <span class="pill {{ $aviso->status === 'ATIVO' ? 'green' : 'orange' }}">{{ ucfirst(mb_strtolower($aviso->status)) }}</span>
                    @if($aviso->status === 'ATIVO')
                        <form method="post" action="{{ route('web.travel.delete', $aviso) }}" data-confirm="Cancelar este aviso de viagem?">
                            @csrf
                            @method('DELETE')
                            <button class="btn btn-danger btn-sm" type="submit">Cancelar aviso</button>
                        </form>
                    @endif
                </div>
            </div>
        @empty
            @include('web.components.empty', ['icon' => 'plane', 'heading' => 'Nenhuma viagem informada'])
        @endforelse
    </section>

    <section class="panel">
        <div class="panel-header">
            <div>
                <h2>Novo aviso</h2>
                <p class="panel-subtitle">O aviso vale para o cartão e as datas selecionados.</p>
            </div>
        </div>
        @if($cartoes->isEmpty())
            <p class="muted">Emita um cartão para registrar o aviso.</p>
            <a class="btn btn-outline" href="{{ route('web.cards') }}">Ver cartões</a>
        @else
            <form class="stack" method="post" action="{{ route('web.travel.store') }}">
                @csrf
                <div class="field">
                    <label for="cartao-viagem">Cartão</label>
                    <select id="cartao-viagem" name="cartao_id" required>
                        @foreach($cartoes as $cartao)
                            <option value="{{ $cartao->id }}" @selected((int) old('cartao_id') === $cartao->id)>
                                {{ $cartao->modalidade === 'DEBITO' ? 'Débito' : 'Crédito' }} · final {{ substr($cartao->numero_mascarado, -4) }}
                            </option>
                        @endforeach
                    </select>
                </div>
                <div class="field">
                    <label for="localidades">Localidades</label>
                    <input id="localidades" name="localidades" value="{{ old('localidades') }}" placeholder="Ex.: Belo Horizonte, São Paulo" required maxlength="240">
                </div>
                <div class="form-grid">
                    <div class="field"><label for="data_inicio">Início</label><input id="data_inicio" name="data_inicio" type="date" value="{{ old('data_inicio') }}" required></div>
                    <div class="field"><label for="data_fim">Fim</label><input id="data_fim" name="data_fim" type="date" value="{{ old('data_fim') }}" required></div>
                </div>
                <button class="btn btn-primary btn-full" type="submit">Registrar aviso</button>
            </form>
        @endif
    </section>
</div>
@endsection
