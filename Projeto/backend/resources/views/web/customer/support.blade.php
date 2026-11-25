@extends('web.layouts.app', ['title' => 'Suporte'])

@section('content')
@include('web.components.heading', ['heading' => 'Central de ajuda', 'subtitle' => 'Envie uma solicitação e acompanhe as respostas do suporte.'])

<div class="grid grid-main">
    <section class="panel">
        <div class="panel-header"><h2>Meus chamados</h2></div>
        <div class="content-list">
            @forelse($chamados as $chamado)
                <article class="card-tile">
                    <div class="card-tile-footer">
                        <div>
                            <h3>{{ $chamado->assunto }}</h3>
                            <small class="muted">Chamado #{{ $chamado->id }} · {{ $chamado->created_at?->format('d/m/Y H:i') }}</small>
                        </div>
                        @php
                            $status = ['ABERTO' => ['Aberto', 'orange'], 'EM_ATENDIMENTO' => ['Em atendimento', 'blue'], 'RESOLVIDO' => ['Resolvido', 'green']];
                            [$rotulo, $cor] = $status[$chamado->status] ?? [$chamado->status, 'blue'];
                        @endphp
                        <span class="pill {{ $cor }}">{{ $rotulo }}</span>
                    </div>
                    <p class="ticket-message">{{ $chamado->mensagem }}</p>
                    @if($chamado->resposta)
                        <div class="info-box">
                            @include('web.components.icon', ['name' => 'headphones'])
                            <div>
                                <strong>Resposta do suporte</strong>
                                <p class="ticket-message">{{ $chamado->resposta }}</p>
                            </div>
                        </div>
                    @endif
                </article>
            @empty
                @include('web.components.empty', ['icon' => 'headphones', 'heading' => 'Nenhum chamado registrado'])
            @endforelse
        </div>
    </section>

    <section class="panel">
        <div class="panel-header"><h2>Abrir chamado</h2></div>
        <form class="stack" method="post" action="{{ route('web.support.store') }}">
            @csrf
            <div class="field">
                <label for="assunto">Assunto</label>
                <input id="assunto" name="assunto" value="{{ old('assunto') }}" maxlength="120" required>
            </div>
            <div class="field">
                <label for="mensagem">Mensagem</label>
                <textarea id="mensagem" name="mensagem" maxlength="2000" placeholder="Descreva o que está acontecendo" required rows="5">{{ old('mensagem') }}</textarea>
            </div>
            <button class="btn btn-primary btn-full" type="submit">Enviar solicitação</button>
        </form>
    </section>
</div>
@endsection
