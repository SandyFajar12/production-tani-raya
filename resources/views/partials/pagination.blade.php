@if ($paginator->hasPages())
<div style="display:flex; justify-content:space-between; align-items:center; margin-top:16px; padding-top:12px; border-top:1px solid var(--color-border);">
  @if ($paginator->onFirstPage())
    <span class="btn btn-sm btn-secondary" style="width:auto; opacity:0.5;">‹ Sebelumnya</span>
  @else
    <a href="{{ $paginator->previousPageUrl() }}" class="btn btn-sm btn-secondary" style="width:auto;">‹ Sebelumnya</a>
  @endif

  <span style="font-size:12.5px; color:var(--color-text-muted); font-weight:600;">
    Hal {{ $paginator->currentPage() }} / {{ $paginator->lastPage() }} &middot; {{ $paginator->total() }} data
  </span>

  @if ($paginator->hasMorePages())
    <a href="{{ $paginator->nextPageUrl() }}" class="btn btn-sm btn-secondary" style="width:auto;">Berikutnya ›</a>
  @else
    <span class="btn btn-sm btn-secondary" style="width:auto; opacity:0.5;">Berikutnya ›</span>
  @endif
</div>
@endif