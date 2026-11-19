<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Refund;
use App\Services\PaymentService;
use Exception;
use Illuminate\Http\Request;

class RefundController extends Controller
{
    public function index(Request $request)
    {
        $this->authorizeView($request);

        $refunds = Refund::with(['order.customer', 'returnRequest', 'processedBy', 'transactions'])
            ->latest()
            ->paginate(15);

        return view('admin.refunds.index', compact('refunds'));
    }

    public function retry(Request $request, Refund $refund, PaymentService $paymentService)
    {
        $this->authorizeRetry($request);

        try {
            $paymentService->retryRefund($refund, $request->user());

            return back()->with('success', "Refund #{$refund->refund_number} retried and processed successfully.");
        } catch (Exception $e) {
            return back()->with('error', "Failed to retry refund #{$refund->refund_number}: " . $e->getMessage());
        }
    }

    protected function authorizeView(Request $request): void
    {
        $user = $request->user();
        if (!$user || (!$user->isSuperAdmin() && !$user->hasRole(['super_admin', 'admin']) && !$user->hasPermission('payments.view'))) {
            abort(403, 'Unauthorized. Administrative permission (payments.view) required.');
        }
    }

    protected function authorizeRetry(Request $request): void
    {
        $user = $request->user();
        if (!$user || (!$user->isSuperAdmin() && !$user->hasRole(['super_admin', 'admin']) && !$user->hasPermission('refunds.retry'))) {
            abort(403, 'Unauthorized. Administrative permission (refunds.retry) required.');
        }
    }
}

