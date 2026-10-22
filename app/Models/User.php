<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Foundation\Auth\User as Authenticatable;
use Illuminate\Notifications\Notifiable;

class User extends Authenticatable
{
    use HasFactory, Notifiable;

    protected $fillable = [
        'name', 'email', 'password', 'role_id', 'phone', 'is_active', 'avatar',
    ];

    protected $hidden = [
        'password', 'remember_token',
    ];

    protected $casts = [
        'is_active' => 'boolean',
        'last_login_at' => 'datetime',
        'notifications_seen_at' => 'datetime',
    ];

    public function role()
    {
        return $this->belongsTo(Role::class);
    }

    protected $permissionSlugs;

    /** Peta izin untuk request ini: izin bawaan dibaca sebagai izin modul asal (dipakai halaman master bersama) */
    protected $permissionMap = [];

    public function mapPermissions(array $map): void
    {
        $this->permissionMap = $map;
    }

    /**
     * Daftar slug permission milik user (lewat role-nya), diambil sekali per request.
     */
    public function permissionSlugs(): array
    {
        if ($this->permissionSlugs === null) {
            $this->permissionSlugs = $this->role
                ? $this->role->permissions()->pluck('slug')->all()
                : [];
        }

        return $this->permissionSlugs;
    }

    public function hasPermission(string $slug): bool
    {
        $slug = $this->permissionMap[$slug] ?? $slug;

        return in_array($slug, $this->permissionSlugs(), true);
    }

    public function hasAnyPermission(array $slugs): bool
    {
        return count(array_intersect($slugs, $this->permissionSlugs())) > 0;
    }

    public function isSuperAdmin(): bool
    {
        return $this->role && $this->role->slug === 'superadmin';
    }

    /** Jumlah notifikasi yang dibuat setelah lonceng terakhir dibuka (angka badge) */
    public function unseenNotificationCount(): int
    {
        try {
            $query = \Illuminate\Support\Facades\DB::table('notifications')
                ->where('notifiable_type', $this->getMorphClass())
                ->where('notifiable_id', $this->id);

            if ($this->notifications_seen_at) {
                $query->where('created_at', '>', $this->notifications_seen_at);
            }

            return (int) $query->count();
        } catch (\Throwable $e) {
            return 0; // jangan sampai seluruh halaman error kalau tabel belum ada
        }
    }
}
