<?php

namespace App\Http\Controllers;

use App\Models\Payment;
use App\Models\Setting;
use App\Support\ImageCompressor;
use Illuminate\Http\Request;

class PaymentController extends Controller
{
    public const PACKAGES = [
        '50' => ['credits' => 50, 'price' => 2.00],
        '100' => ['credits' => 100, 'price' => 3.00],
    ];

    public function index(Request $request)
    {
        $qrData = Setting::get('qr_code_data');
        $payments = $request->user()->payments()->latest()->take(5)->get();

        return view('payment.buy', [
            'packages' => self::PACKAGES,
            'qrData' => $qrData,
            'recentPayments' => $payments,
        ]);
    }

    public function submit(Request $request)
    {
        $validated = $request->validate([
            'package' => ['required', 'in:50,100'],
            'payer_full_name' => ['required', 'string', 'max:150'],
            'receipt' => ['required', 'mimes:jpeg,jpg,png,gif,bmp,webp,heic,heif', 'max:15360'],
        ], [
            'payer_full_name.required' => 'Sila masukkan nama penuh seperti dalam akaun bank.',
            'receipt.required' => 'Sila muat naik resit pembayaran.',
        ]);

        $package = self::PACKAGES[$validated['package']];

        // Resit disimpan terus dalam DB (base64) supaya tak hilang walaupun
        // storage server di-reset semasa redeploy.
        $receiptData = ImageCompressor::toDataUri($request->file('receipt'), 'document', 700);

        Payment::create([
            'user_id' => $request->user()->id,
            'payer_full_name' => $validated['payer_full_name'],
            'package_credits' => $package['credits'],
            'package_price' => $package['price'],
            'receipt_data' => $receiptData,
            'status' => 'pending',
        ]);

        return redirect()->route('payment.index')
            ->with('status', 'Resit anda dah dihantar! Tunggu admin approve - credit akan masuk automatik selepas diluluskan.');
    }

    public function history(Request $request)
    {
        $payments = $request->user()->payments()->latest()->get();

        return view('payment.history', compact('payments'));
    }
}
