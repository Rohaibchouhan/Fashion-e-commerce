<?php

namespace App\Services;

use App\Enums\ReturnStatus;
use App\Events\ReturnStatusUpdatedEvent;
use App\Models\Exchange;
use App\Models\Order;
use App\Models\OrderItem;
use App\Models\Refund;
use App\Models\RefundTransaction;
use App\Models\ReturnItem;
use App\Models\ReturnRequest;
use App\Models\Setting;
use App\Models\User;
use Carbon\Carbon;
use Exception;
use Illuminate\Contracts\Pagination\LengthAwarePaginator;
use Illuminate\Support\Facades\DB;

class ReturnService
{
    public function __construct(
        protected InventoryService $inventoryService,
        protected PaymentService $paymentService
    ) {}

    public function createReturnRequest(User $user, array $data): ReturnRequest
    {
        return DB::transaction(function () use ($user, $data) {
            $order = Order::where('customer_id', $user->id)
                ->where(function ($q) use ($data) {
                    $q->where('id', $data['order_id'] ?? $data['orderId'])
                      ->orWhere('order_number', $data['order_id'] ?? $data['orderId']);
                })
                ->firstOrFail();

            // Validate return eligibility period
            $returnWindowDays = (int) Setting::get('return_window_days', 30);
            if ($order->created_at->diffInDays(Carbon::now()) > $returnWindowDays) {
                throw new Exception("This order was placed over {$returnWindowDays} days ago and is past the return eligibility window.");
            }

            $itemsData = $data['items'] ?? [];
            if (empty($itemsData)) {
                throw new Exception("Please select at least one item to return.");
            }

            $rmaNumber = 'RMA-' . strtoupper(bin2hex(random_bytes(4)));
            while (ReturnRequest::where('rma_number', $rmaNumber)->exists()) {
                $rmaNumber = 'RMA-' . strtoupper(bin2hex(random_bytes(4)));
            }

            $resolution = $data['resolution'] ?? 'refund';
            $refundAmount = 0.00;

            $returnRequest = ReturnRequest::create([
                'rma_number' => $rmaNumber,
                'order_id' => $order->id,
                'user_id' => $user->id,
                'status' => ReturnStatus::REQUESTED,
                'resolution' => $resolution,
                'exchange_details' => $data['exchangeDetails'] ?? $data['exchange_details'] ?? null,
                'refund_amount' => 0.00,
            ]);

            foreach ($itemsData as $itemInput) {
                $orderItemId = $itemInput['orderItemId'] ?? $itemInput['order_item_id'];
                $orderItem = OrderItem::where('order_id', $order->id)->findOrFail($orderItemId);

                $qty = max(1, (int) $itemInput['quantity']);

                // Validate quantity: cannot return more than purchased minus already returned
                $eligibleQty = $orderItem->eligible_return_quantity;
                if ($qty > $eligibleQty) {
                    throw new Exception("Cannot return {$qty} units of {$orderItem->product_name}. Maximum eligible is {$eligibleQty}.");
                }

                $itemRefund = round((float) $orderItem->unit_price * $qty, 2);
                $refundAmount += $itemRefund;

                $returnItem = ReturnItem::create([
                    'return_id' => $returnRequest->id,
                    'order_item_id' => $orderItem->id,
                    'product_id' => $orderItem->product_id,
                    'variant_id' => $orderItem->variant_id,
                    'product_name' => $orderItem->product_name,
                    'selected_size' => $orderItem->selected_size,
                    'selected_color' => $orderItem->selected_color,
                    'image_url' => $orderItem->image_url,
                    'unit_price' => $orderItem->unit_price,
                    'quantity' => $qty,
                    'reason' => $itemInput['reason'] ?? 'wrong_size',
                    'customer_comment' => $itemInput['comment'] ?? $itemInput['description'] ?? null,
                    'inspection_result' => 'pending',
                ]);

                // Store uploaded proof images if present
                if (!empty($itemInput['images'])) {
                    foreach ($itemInput['images'] as $imgUrl) {
                        $returnItem->images()->create(['url' => $imgUrl]);
                    }
                }
            }

            $returnRequest->update(['refund_amount' => $refundAmount]);

            event(new ReturnStatusUpdatedEvent($returnRequest, null, ReturnStatus::REQUESTED->value));

            return $returnRequest->fresh(['items.images', 'order', 'user']);
        });
    }

    public function approveReturn(ReturnRequest $returnRequest, ?string $labelUrl = null, ?string $trackingNumber = null): ReturnRequest
    {
        $prevStatus = $returnRequest->status->value;
        $returnRequest->update([
            'status' => ReturnStatus::APPROVED,
            'label_url' => $labelUrl ?? "https://shipping.wearday.com/labels/{$returnRequest->rma_number}.pdf",
            'tracking_number' => $trackingNumber ?? ('1Z' . strtoupper(bin2hex(random_bytes(8)))),
        ]);

        event(new ReturnStatusUpdatedEvent($returnRequest, $prevStatus, ReturnStatus::APPROVED->value));
        return $returnRequest;
    }

    public function rejectReturn(ReturnRequest $returnRequest, string $reason): ReturnRequest
    {
        $prevStatus = $returnRequest->status->value;
        $returnRequest->update([
            'status' => ReturnStatus::REJECTED,
            'rejection_reason' => $reason,
        ]);

        event(new ReturnStatusUpdatedEvent($returnRequest, $prevStatus, ReturnStatus::REJECTED->value));
        return $returnRequest;
    }

    public function completeInspection(ReturnRequest $returnRequest, array $inspectionResults, ?int $adminUserId = null): ReturnRequest
    {
        return DB::transaction(function () use ($returnRequest, $inspectionResults, $adminUserId) {
            $allPassed = true;

            foreach ($returnRequest->items as $item) {
                $resultData = $inspectionResults[$item->id] ?? null;
                $condition = $resultData['condition'] ?? 'resellable'; // resellable, damaged, scrap

                $item->update([
                    'inspection_result' => $condition,
                ]);

                // Only restock if resellable!
                if ($condition === 'resellable' && $item->variant_id) {
                    $this->inventoryService->restockReturnedItem(
                        $returnRequest->id,
                        $item->variant_id,
                        $item->quantity,
                        $adminUserId
                    );
                    $item->update(['restocked_quantity' => $item->quantity]);
                } else {
                    $allPassed = false;
                }
            }

            $prevStatus = $returnRequest->status->value;
            $returnRequest->update([
                'status' => ReturnStatus::INSPECTED,
                'inspected_at' => now(),
                'inspection_passed' => $allPassed,
                'restocked' => true,
            ]);

            event(new ReturnStatusUpdatedEvent($returnRequest, $prevStatus, ReturnStatus::INSPECTED->value));

            return $returnRequest->fresh(['items']);
        });
    }

    public function processApprovedRefund(ReturnRequest $returnRequest, ?int $adminUserId = null): Refund
    {
        return DB::transaction(function () use ($returnRequest, $adminUserId) {
            $order = $returnRequest->order;
            $payment = $order->latestPayment;

            $amount = (float) $returnRequest->refund_amount;
            if ($amount <= 0) {
                throw new Exception("Refund amount must be greater than zero.");
            }

            // Create refund record
            $refundNumber = 'REF-' . strtoupper(bin2hex(random_bytes(5)));
            $refund = Refund::create([
                'refund_number' => $refundNumber,
                'return_id' => $returnRequest->id,
                'order_id' => $order->id,
                'payment_id' => $payment?->id,
                'amount' => $amount,
                'reason' => "Return RMA #{$returnRequest->rma_number}",
                'status' => 'approved',
                'processed_by' => $adminUserId,
                'processed_at' => now(),
            ]);

            if ($payment) {
                $res = $this->paymentService->refundPayment($payment, $amount, "Return RMA #{$returnRequest->rma_number}", $refundNumber);
                $refund->update([
                    'status' => 'processed',
                    'gateway_refund_id' => $res['refund_id'] ?? null,
                    'processed_at' => now(),
                ]);
                RefundTransaction::create([
                    'refund_id' => $refund->id,
                    'amount' => $amount,
                    'gateway' => $payment->payment_method ?? 'card',
                    'reference' => $res['refund_id'] ?? null,
                    'status' => 'success',
                    'response_payload' => $res,
                ]);
            }

            $prevStatus = $returnRequest->status->value;
            $returnRequest->update(['status' => ReturnStatus::REFUNDED]);

            event(new ReturnStatusUpdatedEvent($returnRequest, $prevStatus, ReturnStatus::REFUNDED->value));

            return $refund;
        });
    }
}

