@php $notifCount = (int) auth()->user()->unseenNotificationCount(); @endphp
<button type="button" class="icon-btn bell-btn" data-notif-bell aria-label="Notifikasi" title="Notifikasi">
  <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M18 8A6 6 0 0 0 6 8c0 7-3 9-3 9h18s-3-2-3-9"/><path d="M13.73 21a2 2 0 0 1-3.46 0"/></svg>
  <span class="bell-badge" data-notif-badge @if ($notifCount < 1) hidden @endif>{{ $notifCount > 99 ? '99+' : ($notifCount > 0 ? $notifCount : '') }}</span>
</button>