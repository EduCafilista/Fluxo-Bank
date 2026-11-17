@if($paginator->hasPages())<div class="pagination"><span>Página {{ $paginator->currentPage() }} de {{ $paginator->lastPage() }}</span><div>
@if($paginator->onFirstPage())<span class="btn btn-muted btn-sm disabled">Anterior</span>@else<a class="btn btn-muted btn-sm" href="{{ $paginator->previousPageUrl() }}">Anterior</a>@endif
@if($paginator->hasMorePages())<a class="btn btn-muted btn-sm" href="{{ $paginator->nextPageUrl() }}">Próxima</a>@else<span class="btn btn-muted btn-sm disabled">Próxima</span>@endif
</div></div>@endif
