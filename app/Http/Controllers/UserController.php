<?php

namespace App\Http\Controllers;

use App\Models\Role;
use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Hash;

class UserController extends Controller
{
    public function index()
    {
        $query = User::with('role');

        if ($search = request('search')) {
            $query->where(function ($q) use ($search) {
                $q->where('name', 'like', "%{$search}%")
                  ->orWhere('email', 'like', "%{$search}%");
            });
        }

        $users = $query->orderByDesc('updated_at')->paginate($this->perPage())->withQueryString();
        $roles = Role::orderBy('name')->get();

        return view('users.index', compact('users', 'roles'));
    }

    public function store(Request $request)
    {
        $data = $request->validate([
            'name' => ['required', 'string', 'max:150'],
            'email' => ['required', 'email', 'max:150', 'unique:users,email'],
            'phone' => ['nullable', 'string', 'max:30'],
            'password' => ['required', 'string', 'min:6'],
            'role_id' => ['required', 'exists:roles,id'],
            'is_active' => ['required', 'boolean'],
            'avatar' => ['nullable', 'image', 'max:2048'],
        ], [
            'name.required' => 'Nama lengkap wajib diisi.',
            'email.required' => 'Email / username wajib diisi.',
            'email.email' => 'Format email tidak valid.',
            'email.unique' => 'Email / username sudah terdaftar.',
            'password.required' => 'Password wajib diisi.',
            'password.min' => 'Password minimal 6 karakter.',
            'role_id.required' => 'Role / hak akses wajib dipilih.',
        ]);

        $data['password'] = Hash::make($data['password']);

        if ($request->hasFile('avatar')) {
            $data['avatar'] = $this->storeAvatarFile($request->file('avatar'));
        }

        User::create($data);

        if ($request->expectsJson()) {
            $request->session()->flash('status', 'User baru tersimpan.');

            return response()->json(['message' => 'User baru tersimpan.']);
        }

        return back()->with('status', 'User baru tersimpan.');
    }

    public function update(Request $request, User $user)
    {
        $data = $request->validate([
            'name' => ['required', 'string', 'max:150'],
            'email' => ['required', 'email', 'max:150', 'unique:users,email,'.$user->id],
            'phone' => ['nullable', 'string', 'max:30'],
            'password' => ['nullable', 'string', 'min:6'],
            'role_id' => ['required', 'exists:roles,id'],
            'is_active' => ['required', 'boolean'],
            'avatar' => ['nullable', 'image', 'max:2048'],
        ], [
            'name.required' => 'Nama lengkap wajib diisi.',
            'email.required' => 'Email / username wajib diisi.',
            'email.email' => 'Format email tidak valid.',
            'email.unique' => 'Email / username sudah terdaftar.',
            'password.min' => 'Password minimal 6 karakter.',
            'role_id.required' => 'Role / hak akses wajib dipilih.',
        ]);

        if (! empty($data['password'])) {
            $data['password'] = Hash::make($data['password']);
        } else {
            unset($data['password']);
        }

        if ($request->hasFile('avatar')) {
            $data['avatar'] = $this->storeAvatarFile($request->file('avatar'));
        }

        $user->update($data);

        return back()->with('status', 'Data user diperbarui.');
    }

    public function destroy(User $user)
    {
        if ($user->id === auth()->id()) {
            return back()->withErrors(['user' => 'Tidak bisa menghapus akun yang sedang login.']);
        }

        $user->delete();

        return back()->with('status', 'User dihapus.');
    }

    private function storeAvatarFile($file): string
    {
        $filename = 'avatar-'.now()->format('Ymd-His').'-'.uniqid().'.'.$file->getClientOriginalExtension();
        $directory = storage_path('app/public/avatars');

        if (! is_dir($directory)) {
            mkdir($directory, 0755, true);
        }

        $file->move($directory, $filename);

        return $filename;
    }
}
