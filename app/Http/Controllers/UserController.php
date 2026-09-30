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
        $users = User::with('role')->orderBy('name')->get();
        $roles = Role::orderBy('name')->get();

        return view('users.index', compact('users', 'roles'));
    }

    public function store(Request $request)
    {
        $data = $request->validate([
            'name' => ['required', 'string', 'max:150'],
            'email' => ['required', 'email', 'max:150', 'unique:users,email'],
            'password' => ['required', 'string', 'min:6'],
            'role_id' => ['required', 'exists:roles,id'],
            'is_active' => ['required', 'boolean'],
        ]);

        $data['password'] = Hash::make($data['password']);

        User::create($data);

        return back()->with('status', 'User baru tersimpan.');
    }

    public function update(Request $request, User $user)
    {
        $data = $request->validate([
            'name' => ['required', 'string', 'max:150'],
            'email' => ['required', 'email', 'max:150', 'unique:users,email,'.$user->id],
            'password' => ['nullable', 'string', 'min:6'],
            'role_id' => ['required', 'exists:roles,id'],
            'is_active' => ['required', 'boolean'],
        ]);

        if (! empty($data['password'])) {
            $data['password'] = Hash::make($data['password']);
        } else {
            unset($data['password']);
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
}
