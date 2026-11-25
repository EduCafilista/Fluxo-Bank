@extends('web.layouts.app', ['title' => 'Assinaturas'])

@section('content')
@include('web.components.heading', ['heading' => 'Assinaturas e planos', 'subtitle' => 'Acompanhe seus planos, cobranças e recorrências do cartão.'])

@php
    $planosAtivos = $assinaturas->where('status', 'ATIVA')->pluck('plano_id')->all();
    $statusAssinatura = ['ATIVA' => 'Ativa', 'SUSPENSA' => 'Suspensa', 'CANCELADA' => 'Cancelada'];
@endphp

<div class="grid grid-main">
    <div class="stack">
        <section class="panel">
            <div class="panel-header"><h2>Seus planos Fluxo</h2></div>
            <div class="content-list">
                @forelse($assinaturas as $assinatura)
                    @php
                        $ativa = $assinatura->status === 'ATIVA';
                        $itensAtivos = $assinatura->itens->whereNull('data_exclusao');
                        $itensDisponiveis = $itensCatalogo->whereNotIn('id', $itensAtivos->pluck('item_id')->all());
                    @endphp
                    <article class="card-tile">
                        <div class="card-tile-footer">
                            <div>
                                <h3>{{ $assinatura->plano?->nome ?? 'Plano Fluxo' }}</h3>
                                @if($ativa)
                                    <div class="muted sm">Dia de cobrança {{ $assinatura->dia_cobranca }}</div>
                                @endif
                            </div>
                            <span class="pill {{ $ativa ? 'green' : 'orange' }}">{{ $statusAssinatura[$assinatura->status] ?? $assinatura->status }}</span>
                        </div>

                        @if($ativa)
                            <div class="detail-item">
                                <span class="key">Mensalidade atual</span>
                                <strong>R$ {{ number_format($assinatura->valor_atual_centavos / 100, 2, ',', '.') }}</strong>
                            </div>
                        @endif

                        @if($itensAtivos->isNotEmpty())
                            <div class="panel-section">
                                <h3>Itens contratados</h3>
                                <div class="detail-list">
                                    @foreach($itensAtivos as $vinculo)
                                        <div class="detail-item billing-row">
                                            <div>
                                                <strong>{{ $vinculo->item?->nome ?? 'Item' }}</strong>
                                                @if($vinculo->item)
                                                    <div class="muted sm">R$ {{ number_format($vinculo->item->valor_unitario_centavos / 100, 2, ',', '.') }}/mês</div>
                                                @endif
                                            </div>
                                            @if($ativa)
                                                <form method="post" action="{{ route('web.subscriptions.items.delete', [$assinatura, $vinculo->item_id]) }}" data-confirm="Remover este item da assinatura?">
                                                    @csrf
                                                    @method('DELETE')
                                                    <input type="hidden" name="motivo" value="Solicitação do cliente via web">
                                                    <button class="btn btn-outline btn-sm" type="submit">Remover item</button>
                                                </form>
                                            @endif
                                        </div>
                                    @endforeach
                                </div>
                            </div>
                        @endif

                        @if($assinatura->cobrancas->isNotEmpty())
                            <div class="panel-section">
                                <h3>Cobranças recentes</h3>
                                <div class="detail-list">
                                    @foreach($assinatura->cobrancas as $cobranca)
                                        <div class="detail-item billing-row">
                                            <div>
                                                <strong>{{ substr($cobranca->competencia, 5, 2) }}/{{ substr($cobranca->competencia, 0, 4) }}</strong>
                                                <div class="muted sm">Vencimento em {{ $cobranca->vencimento->format('d/m/Y') }}</div>
                                            </div>
                                            <div class="compact-actions">
                                                <strong>R$ {{ number_format($cobranca->valor_centavos / 100, 2, ',', '.') }}</strong>
                                                <span class="pill {{ $cobranca->status === 'PAGA' ? 'green' : 'blue' }}">{{ ucfirst(mb_strtolower($cobranca->status)) }}</span>
                                            </div>
                                        </div>
                                    @endforeach
                                </div>
                            </div>
                        @endif

                        @if($ativa)
                            @if($itensDisponiveis->isNotEmpty())
                                <form class="form-actions form-end" method="post" action="{{ route('web.subscriptions.items.store', $assinatura) }}">
                                    @csrf
                                    <div class="field">
                                        <label for="item-{{ $assinatura->id }}">Adicionar item</label>
                                        <select id="item-{{ $assinatura->id }}" name="item_id" required>
                                            @foreach($itensDisponiveis as $item)
                                                <option value="{{ $item->id }}">{{ $item->nome }} · R$ {{ number_format($item->valor_unitario_centavos / 100, 2, ',', '.') }}/mês</option>
                                            @endforeach
                                        </select>
                                    </div>
                                    <button class="btn btn-outline" type="submit">Adicionar</button>
                                </form>
                            @endif
                            <form method="post" action="{{ route('web.subscriptions.delete', $assinatura) }}" data-confirm="Deseja cancelar este plano?">
                                @csrf
                                @method('DELETE')
                                <button class="btn btn-danger btn-sm" type="submit">Cancelar plano</button>
                            </form>
                        @endif
                    </article>
                @empty
                    @include('web.components.empty', ['icon' => 'repeat', 'heading' => 'Sem planos contratados'])
                @endforelse
            </div>
        </section>

        <section class="panel">
            <div class="panel-header"><h2>Recorrências do cartão</h2></div>
            @forelse($recorrencias as $recorrencia)
                <div class="detail-item billing-row">
                    <div>
                        <strong>{{ $recorrencia->nome_estabelecimento ?? 'Cobrança recorrente' }}</strong>
                        <div class="muted sm">
                            @if($recorrencia->cartao)
                                Cartão final {{ substr($recorrencia->cartao->numero_mascarado, -4) }} ·
                            @endif
                            {{ $statusAssinatura[$recorrencia->status] ?? ucfirst(mb_strtolower($recorrencia->status)) }}
                        </div>
                        @if($recorrencia->proxima_cobranca && $recorrencia->status !== 'CANCELADA')
                            <div class="muted sm">Próxima cobrança prevista em {{ $recorrencia->proxima_cobranca->format('d/m/Y') }}</div>
                        @endif
                    </div>
                    <div class="compact-actions">
                        @if($recorrencia->valor_estimado_centavos !== null)
                            <strong>R$ {{ number_format($recorrencia->valor_estimado_centavos / 100, 2, ',', '.') }}</strong>
                        @endif
                        @if($recorrencia->status !== 'CANCELADA')
                            <form method="post" action="{{ route('web.recurring.delete', $recorrencia) }}" data-confirm="Marcar como cancelada no Fluxo? O cancelamento com o estabelecimento é feito diretamente com ele.">
                                @csrf
                                @method('DELETE')
                                <button class="btn btn-outline btn-sm" type="submit">Marcar como cancelada</button>
                            </form>
                        @endif
                    </div>
                </div>
            @empty
                @include('web.components.empty', ['icon' => 'repeat', 'heading' => 'Nenhuma recorrência identificada'])
            @endforelse
        </section>
    </div>

    <section class="panel">
        <div class="panel-header"><h2>Planos disponíveis</h2></div>
        <div class="content-list">
            @forelse($planos as $plano)
                <article class="card-tile">
                    <h3>{{ $plano->nome }}</h3>
                    <div class="plan-price">R$ {{ number_format($plano->mensalidade_centavos / 100, 2, ',', '.') }} <small>por mês</small></div>
                    @if(in_array($plano->id, $planosAtivos, true))
                        <div><span class="pill green">Contratado</span></div>
                    @else
                        <form class="stack" method="post" action="{{ route('web.subscriptions.store') }}" data-confirm="Confirma a contratação de {{ $plano->nome }}?">
                            @csrf
                            <input type="hidden" name="plano_id" value="{{ $plano->id }}">
                            <div class="field">
                                <label for="dia-{{ $plano->id }}">Dia de cobrança</label>
                                <input id="dia-{{ $plano->id }}" type="number" name="dia_cobranca" min="1" max="28"
                                    value="{{ (int) old('plano_id') === $plano->id ? old('dia_cobranca', 10) : 10 }}" required>
                            </div>
                            <button class="btn btn-primary btn-full" type="submit">Contratar {{ $plano->nome }}</button>
                        </form>
                    @endif
                </article>
            @empty
                @include('web.components.empty', ['icon' => 'repeat', 'heading' => 'Nenhum plano disponível'])
            @endforelse
        </div>
    </section>
</div>
@endsection
