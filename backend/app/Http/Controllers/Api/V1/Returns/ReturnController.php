<?php

namespace App\Http\Controllers\Api\V1\Returns;

use App\Http\Controllers\Controller;
use App\Http\Resources\ReturnResource;
use App\Models\ReturnRequest;
use App\Services\ReturnService;
use Exception;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class ReturnController extends Controller
{
    public function __construct(protected ReturnService $returnService) {}

    public function index(Request $request): JsonResponse
    {
        $returns = ReturnRequest::where('user_id', $request->user()->id)
            ->with(['items.images', 'order'])
            ->latest()
            ->paginate(15);

        return response()->json([
            'success' => true,
            'data' => ReturnResource::collection($returns),
            'meta' => [
                'current_page' => $returns->currentPage(),
                'last_page' => $returns->lastPage(),
                'total' => $returns->total(),
            ],
        ]);
    }

    public function store(Request $request): JsonResponse
    {
        $validated = $request->validate([
            'order_id' => 'required',
            'resolution' => 'nullable|in:refund,exchange',
            'exchange_details' => 'nullable|string|max:500',
            'items' => 'required|array|min:1',
            'items.*.order_item_id' => 'required',
            'items.*.quantity' => 'required|integer|min:1',
            'items.*.reason' => 'required|string',
            'items.*.comment' => 'nullable|string|max:1000',
            'items.*.images' => 'nullable|array',
        ]);

        try {
            $returnRequest = $this->returnService->createReturnRequest($request->user(), $validated);

            return response()->json([
                'success' => true,
                'message' => "Return request submitted under RMA #{$returnRequest->rma_number}.",
                'data' => new ReturnResource($returnRequest),
            ], 201);
        } catch (Exception $e) {
            return response()->json([
                'success' => false,
                'message' => $e->getMessage(),
            ], 422);
        }
    }

    public function show(Request $request, string $rmaNumber): JsonResponse
    {
        $returnRequest = ReturnRequest::where('user_id', $request->user()->id)
            ->where(function ($q) use ($rmaNumber) {
                $q->where('rma_number', $rmaNumber)
                  ->orWhere('id', $rmaNumber);
            })
            ->with(['items.images', 'order'])
            ->first();

        if (!$returnRequest) {
            return response()->json(['success' => false, 'message' => 'Return record not found.'], 404);
        }

        return response()->json([
            'success' => true,
            'data' => new ReturnResource($returnRequest),
        ]);
    }
}

