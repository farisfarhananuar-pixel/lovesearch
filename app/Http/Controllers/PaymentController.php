<?php

namespace App\Http\Controllers;

use App\Models\Payment;
use App\Models\Setting;
use Illuminate\Http\Request;

class PaymentController extends Controller
{
    public const PACKAGES = [
        '50' => ['credits' => 50, 'price' => 2.00],
        '100' => ['credits' => 100, 'price' => 3.00],
    ];

    public function index(Request $request)
    {
        $qrPath = Setting::get('qr_code_path');
        $payments = $request->user()->payments()->latest()->take(5)->get();

        return view('payment.buy', [
            'packages' => self::PACKAGES,
            'qrPath' => $qrPath,
            'recentPayments' => $payments,
        ]);
    }

    public function submit(Request $request)
    {
        $validated = $request->validate([
            'package' => ['required', 'in:50,100'],
            'payer_full_name' => ['required', 'string', 'max:150'],
            'receipt' => ['required', 'image', 'max:5120'],
        ], [
            'payer_full_name.required' => 'Sila masukkan nama penuh seperti dalam akaun bank.',
            'receipt.required' => 'Sila muat naik resit pembayaran.',
        ]);

        $package = self::PACKAGES[$validated['package']];
        $path = $request->file('receipt')->store('receipts', 'public');

        Payment::create([
            'user_id' => $request->user()->id,
            'payer_full_name' => $validated['payer_full_name'],
            'package_credits' => $package['credits'],
            'package_price' => $package['price'],
            'receipt_path' => $path,
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
