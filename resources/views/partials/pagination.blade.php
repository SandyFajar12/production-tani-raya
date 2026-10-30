@php
  $current = $paginator->currentPage();
  $last = $paginator->lastPage();
  $icFirst = '<svg viewBox="0 0 24 24" aria-hidden="true"><polyline points="11 17 6 12 11 7"/><polyline points="18 17 13 12 18 7"/></svg>';
  $icPrev = '<svg viewBox="0 0 24 24" aria-hidden="true"><polyline points="15 18 9 12 15 6"/></svg>';
  $icNext = '<svg viewBox="0 0 24 24" aria-hidden="true"><polyline points="9 18 15 12 9 6"/></svg>';
  $icLast = '<svg viewBox="0 0 24 24" aria-hidden="true"><polyline points="13 17 18 12 13 7"/><polyline points="6 17 11 12 6 7"/></svg>';
  $icRefresh = '<svg viewBox="0 0 24 24" aria-hidden="true"><polyline points="23 4 23 10 17 10"/><path d="M20.49 15a9 9 0 1 1-2.12-9.36L23 10"/></svg>';
@endphp
<div class="pager" style="margin-top:10px; padding-top:0px;">
  <div class="pager__nav">
    <select class="form-control pager__size" name="per_page" aria-label="Jumlah data per halaman"
      onchange="window.location.href = updateQueryParam('per_page', this.value)">
      @foreach ([10, 20, 50, 100] as $opt)
        <option value="{{ $opt }}" @selected(request('per_page', 20) == $opt)>{{ $opt }}</option>
      @endforeach
    </select>

    @if ($current > 1)
      <a href="{{ $paginator->url(1) }}" class="pager__btn" title="Halaman pertama" aria-label="Halaman pertama">{!! $icFirst !!}</a>
      <a href="{{ $paginator->previousPageUrl() }}" class="pager__btn" title="Sebelumnya" aria-label="Sebelumnya">{!! $icPrev !!}</a>
    @else
      <span class="pager__btn is-disabled">{!! $icFirst !!}</span>
      <span class="pager__btn is-disabled">{!! $icPrev !!}</span>
    @endif

    <span class="pager__text">Halaman</span>
    <input type="number" class="pager__input" value="{{ $current }}" min="1" max="{{ $last }}" data-last="{{ $last }}"
      aria-label="Nomor halaman" onchange="goToPage(this)" onkeydown="if (event.key === 'Enter') this.blur()">
    <span class="pager__text">dari {{ $last }}</span>

    @if ($current < $last)
      <a href="{{ $paginator->nextPageUrl() }}" class="pager__btn" title="Berikutnya" aria-label="Berikutnya">{!! $icNext !!}</a>
      <a href="{{ $paginator->url($last) }}" class="pager__btn" title="Halaman terakhir" aria-label="Halaman terakhir">{!! $icLast !!}</a>
    @else
      <span class="pager__btn is-disabled">{!! $icNext !!}</span>
      <span class="pager__btn is-disabled">{!! $icLast !!}</span>
    @endif

    <button type="button" class="pager__btn" onclick="window.location.reload()" title="Muat ulang" aria-label="Muat ulang">{!! $icRefresh !!}</button>
  </div>

  <span class="pager__info">Menampilkan {{ $paginator->firstItem() ?? 0 }} s/d {{ $paginator->lastItem() ?? 0 }} dari {{ $paginator->total() }} data</span>
</div>