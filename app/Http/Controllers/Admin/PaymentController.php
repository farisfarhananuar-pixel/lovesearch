<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Payment;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

class PaymentController extends Controller
{
    public function index()
    {
        $pending = Payment::with('user')->where('status', 'pending')->latest()->get();
        $recent = Payment::with('user')->whereIn('status', ['approved', 'rejected'])->latest()->take(20)->get();

        return view('admin.payments', compact('pending', 'recent'));
    }

    public function approve(Request $request, Payment $payment)
    {
        if ($payment->status !== 'pending') {
            return back()->with('error', 'Pembayaran ini sudah diproses.');
        }

        DB::transaction(function () use ($payment, $request) {
            $payment->user()->increment('credits', $payment->package_credits);

            $payment->update([
                'status' => 'approved',
                'approved_by' => $request->user()->id,
                'approved_at' => now(),
            ]);
        });

        return back()->with('status', 'Pembayaran diluluskan. Credit telah masuk ke akaun user.');
    }

    public function reject(Request $request, Payment $payment)
    {
        if ($payment->status !== 'pending') {
            return back()->with('error', 'Pembayaran ini sudah diproses.');
        }

        $payment->update([
            'status' => 'rejected',
            'approved_by' => $request->user()->id,
            'approved_at' => now(),
        ]);

        return back()->with('status', 'Pembayaran ditolak.');
    }
}
