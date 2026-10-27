<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Hash;

class ProfileController extends Controller
{
    public function edit()
    {
        return view('profile.edit');
    }

    public function update(Request $request)
    {
        $user = auth()->user();

        $data = $request->validate([
            'name' => ['required', 'string', 'max:150'],
            'email' => ['required', 'email', 'max:150', 'unique:users,email,'.$user->id],
            'phone' => ['nullable', 'string', 'max:30'],
            'password' => ['nullable', 'string', 'min:6'],
            'avatar' => ['nullable', 'image', 'max:2048'],
        ]);

        if (! empty($data['password'])) {
            $data['password'] = Hash::make($data['password']);
        } else {
            unset($data['password']);
        }

        if ($request->hasFile('avatar')) {
            $filename = 'avatar-'.now()->format('Ymd-His').'-'.uniqid().'.'.$request->file('avatar')->getClientOriginalExtension();
            $directory = storage_path('app/public/avatars');

            if (! is_dir($directory)) {
                mkdir($directory, 0755, true);
            }

            $request->file('avatar')->move($directory, $filename);
            $data['avatar'] = $filename;
        }

        $user->update($data);

        return back()->with('status', 'Profil Anda berhasil diperbarui.');
    }

    public function avatar(string $filename)
    {
        $filename = basename($filename);

        if (str_contains($filename, '..')) {
            abort(404);
        }

        $path = storage_path('app/public/avatars/'.$filename);

        if (! file_exists($path)) {
            abort(404);
        }

        return response()->file($path);
    }
}