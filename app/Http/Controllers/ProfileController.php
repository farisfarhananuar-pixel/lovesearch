<?php

namespace App\Http\Controllers;

use App\Support\ImageCompressor;
use Illuminate\Http\Request;

class ProfileController extends Controller
{
    public function edit(Request $request)
    {
        return view('profile.edit', ['user' => $request->user()]);
    }

    public function update(Request $request)
    {
        $user = $request->user();

        $validated = $request->validate([
            'display_name' => ['nullable', 'string', 'max:50'],
            'profile_photo' => ['nullable', 'image', 'max:5120'],
        ]);

        $user->display_name = trim($validated['display_name'] ?? '') !== ''
            ? $validated['display_name']
            : null;

        if ($request->hasFile('profile_photo')) {
            $user->profile_photo = ImageCompressor::toDataUri($request->file('profile_photo'), 'photo', 480);
        }

        $user->save();

        return back()->with('status', 'Profil anda dikemaskini! ✨');
    }

    public function removePhoto(Request $request)
    {
        $request->user()->update(['profile_photo' => null]);

        return back()->with('status', 'Gambar profil dibuang.');
    }
}
