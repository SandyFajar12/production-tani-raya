<div style="display:flex; justify-content:flex-end; align-items:center; gap:8px; margin-bottom:12px;">
  <label class="form-label" style="margin:0; white-space:nowrap;">Tampilkan</label>
  <select class="form-control" style="width:auto; height:36px;" onchange="window.location.href = updateQueryParam('per_page', this.value)">
    @foreach ([10, 20, 50, 100] as $opt)
      <option value="{{ $opt }}" @selected(request('per_page', 20) == $opt)>{{ $opt }}</option>
    @endforeach
  </select>
</div>