<div class="page-heading">
    <div>
        @if(!empty($eyebrow))
            <p class="eyebrow">{{ $eyebrow }}</p>
        @endif
        <h1>{{ $heading }}</h1>
        @if(!empty($subtitle))
            <p class="page-subtitle">{{ $subtitle }}</p>
        @endif
    </div>
    @if(!empty($actionLabel) && !empty($actionUrl))
        <a class="btn btn-primary" href="{{ $actionUrl }}">
            @include('web.components.icon', ['name' => 'arrow-up-right'])
            {{ $actionLabel }}
        </a>
    @endif
</div>
