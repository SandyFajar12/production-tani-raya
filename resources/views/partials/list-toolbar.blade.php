<form method="GET" class="list-toolbar">
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
        <a href="{{ request()->url() }}" class="btn btn-sm btn-secondary" style="width:auto;">Reset</a>
      @endif
      <button type="submit" class="btn btn-sm btn-primary" style="width:auto;">Cari</button>
    </div>
  </div>
</form>