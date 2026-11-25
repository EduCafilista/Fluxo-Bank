<div class="empty-state">
    <span class="empty-icon">@include('web.components.icon', ['name' => $icon ?? 'history'])</span>
    <strong>{{ $heading ?? 'Nenhum registro por enquanto' }}</strong>
    @if(!empty($description))
        <p>{{ $description }}</p>
    @endif
</div>
