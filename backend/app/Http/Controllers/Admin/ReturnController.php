<?php

namespace App\Http\Controllers\Admin;

use App\Enums\ReturnStatus;
use App\Http\Controllers\Controller;
use App\Models\ActivityLog;
use App\Models\ReturnRequest;
use App\Services\ReturnService;
use Exception;
use Illuminate\Http\Request;

class ReturnController extends Controller
{
    public function __construct(protected ReturnService $returnService) {}

    public function index(Request $request)
    {
        $query = ReturnRequest::with(['user', 'order', 'items'])->latest();

        if ($request->filled('status') && $request->status !== 'all') {
            $query->where('status', $request->status);
        }

        $returns = $query->paginate(15)->withQueryString();
        $statuses = ReturnStatus::cases();

        return view('admin.returns.index', compact('returns', 'statuses'));
    }

    public function show(ReturnRequest $return)
    {
        $return->load(['user', 'order.items', 'items.images', 'refunds']);
        return view('admin.returns.show', compact('return'));
    }

    public function approve(Request $request, ReturnRequest $return)
    {
        $this->returnService->approveReturn($return);

        ActivityLog::log('return.approved', "Approved return RMA #{$return->rma_number}", $return);

        return back()->with('success', "Return RMA #{$return->rma_number} approved and return label allocated.");
    }

    public function reject(Request $request, ReturnRequest $return)
    {
        $request->validate(['rejection_reason' => 'required|string|max:500']);

        $this->returnService->rejectReturn($return, $request->rejection_reason);

        ActivityLog::log('return.rejected', "Rejected return RMA #{$return->rma_number}. Reason: {$request->rejection_reason}", $return);

        return back()->with('success', "Return RMA #{$return->rma_number} rejected.");
    }

    public function inspect(Request $request, ReturnRequest $return)
    {
        $validated = $request->validate([
            'results' => 'required|array',
            'results.*.condition' => 'required|in:resellable,damaged,scrap',
        ]);

        try {
            $this->returnService->completeInspection($return, $validated['results'], auth()->id());

            ActivityLog::log('return.inspected', "Completed quality inspection for RMA #{$return->rma_number}", $return);

            return back()->with('success', "Quality inspection recorded. Resellable items have been restocked to warehouse inventory.");
        } catch (Exception $e) {
            return back()->with('error', $e->getMessage());
        }
    }

    public function refund(Request $request, ReturnRequest $return)
    {
        try {
            $refund = $this->returnService->processApprovedRefund($return, auth()->id());

            ActivityLog::log('refund.processed', "Issued refund of \${$refund->amount} for RMA #{$return->rma_number}", $refund);

            return back()->with('success', "Refund of \${$refund->amount} successfully processed via payment gateway.");
        } catch (Exception $e) {
            return back()->with('error', $e->getMessage());
        }
    }
}

