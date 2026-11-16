<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Refund;
use Illuminate\Http\Request;

class RefundController extends Controller
{
    public function index()
    {
        $refunds = Refund::with(['order.customer', 'returnRequest', 'processedBy'])
            ->latest()
            ->paginate(15);

        return view('admin.refunds.index', compact('refunds'));
    }
}

