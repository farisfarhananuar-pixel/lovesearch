<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Payment;
use App\Models\User;

class DashboardController extends Controller
{
    public function index()
    {
        $stats = [
            'total_users' => User::count(),
            'pending_payments' => Payment::where('status', 'pending')->count(),
            'approved_payments' => Payment::where('status', 'approved')->count(),
            'total_lelaki' => User::where('gender', 'lelaki')->count(),
            'total_perempuan' => User::where('gender', 'perempuan')->count(),
        ];

        return view('admin.dashboard', compact('stats'));
    }
}
