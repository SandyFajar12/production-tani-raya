<form method="GET" class="list-toolbar">
  @foreach (['status', 'date', 'low_stock'] as $keep)
    @if (request($keep))
      <input type="hidden" name="{{ $keep }}" value="{{ request($keep) }}">
    @endif
  @endforeach
  <div class="list-toolbar__search">
    <svg class="list-toolbar__search-icon" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><circle cx="11" cy="11" r="8"/><line x1="21" y1="21" x2="16.65" y2="16.65"/></svg>
    <input type="text" name="search" class="form-control list-toolbar__search-input" placeholder="{{ $searchPlaceholder ?? 'Cari...' }}" value="{{ request('search') }}">
  </div>
  <div class="list-toolbar__row2">
    <div class="list-toolbar__perpage">
      <label class="form-label" style="margin:0; white-space:nowrap;">Tampilkan</label>
      <select class="form-control" style="width:auto; height:40px;" name="per_page" onchange="this.form.submit()">
        @foreach ([10, 20, 50, 100] as $opt)
          <option value="{{ $opt }}" @selected(request('per_page', 20) == $opt)>{{ $opt }}</option>
        @endforeach
      </select>
    </div>
    <div style="display:flex; gap:8px;">
      @if (request('search'))
        <a href="{{ request()->url() }}{{ ($keepQuery = http_build_query(request()->only(['status', 'date', 'low_stock']))) ? '?'.$keepQuery : '' }}" class="btn btn-sm btn-secondary btn-icon-sq" title="Reset" aria-label="Reset">
          <svg viewBox="0 0 24 24" aria-hidden="true"><polyline points="1 4 1 10 7 10"/><path d="M3.51 15a9 9 0 1 0 2.13-9.36L1 10"/></svg>
        </a>
      @endif
      <button type="submit" class="btn btn-sm btn-primary btn-icon-sq" title="Cari" aria-label="Cari">
        <svg viewBox="0 0 24 24" aria-hidden="true"><circle cx="11" cy="11" r="8"/><line x1="21" y1="21" x2="16.65" y2="16.65"/></svg>
      </button>
    </div>
  </div>
</form>