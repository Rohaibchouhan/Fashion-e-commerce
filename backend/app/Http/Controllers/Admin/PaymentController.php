<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Payment;
use App\Models\PaymentTransaction;
use Illuminate\Http\Request;

class PaymentController extends Controller
{
    public function index()
    {
        $payments = Payment::with(['order.customer', 'transactions'])->latest()->paginate(15);
        $transactions = PaymentTransaction::with('payment.order')->latest()->limit(20)->get();

        return view('admin.payments.index', compact('payments', 'transactions'));
    }
}

