@props(['records'])
@if($records->hasPages())
<nav class="pagination buttons" aria-label="Pagination">
    @if($records->previousPageUrl())<a class="button secondary is-secondary" href="{{ $records->previousPageUrl() }}">← Previous</a>@endif
    <span>Page {{ $records->currentPage() }} of {{ $records->lastPage() }}</span>
    @if($records->nextPageUrl())<a class="button secondary is-secondary" href="{{ $records->nextPageUrl() }}">Next →</a>@endif
</nav>
@endif
