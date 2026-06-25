<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Setting;
use App\Support\ImageCompressor;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Hash;

class SettingsController extends Controller
{
    public function edit()
    {
        $qrData = Setting::get('qr_code_data');

        return view('admin.settings', compact('qrData'));
    }

    public function updateQr(Request $request)
    {
        $request->validate(['qr_code' => ['required', 'image', 'max:5120']]);

        // Simpan terus dalam DB sebagai base64 - elak QR "hilang" bila storage
        // server di-reset (contoh: lepas redeploy di Railway).
        $dataUri = ImageCompressor::toDataUri($request->file('qr_code'), 'document', 700);
        Setting::set('qr_code_data', $dataUri);

        return back()->with('status', 'QR code bayaran berjaya dikemaskini.');
    }

    public function updatePassword(Request $request)
    {
        $request->validate([
            'current_password' => ['required', 'string'],
            'new_password' => ['required', 'string', 'min:6', 'confirmed'],
        ]);

        $admin = Auth::guard('admin')->user();

        if (! Hash::check($request->input('current_password'), $admin->password)) {
            return back()->withErrors(['current_password' => 'Password semasa salah.']);
        }

        $admin->update(['password' => Hash::make($request->input('new_password'))]);

        return back()->with('status', 'Password admin berjaya ditukar.');
    }
}
