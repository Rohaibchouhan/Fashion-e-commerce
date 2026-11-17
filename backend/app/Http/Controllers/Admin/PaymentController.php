<?php

namespace App\Http\Controllers\Admin;

use App\Enums\OrderStatus;
use App\Enums\PaymentStatus;
use App\Http\Controllers\Controller;
use App\Models\ActivityLog;
use App\Models\Order;
use App\Models\Payment;
use App\Models\PaymentTransaction;
use App\Models\Setting;
use App\Services\PaymentService;
use Carbon\Carbon;
use Exception;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\View\View;

class PaymentController extends Controller
{
    public function __construct(protected PaymentService $paymentService) {}

    public function index(Request $request): View
    {
        $this->authorizeView($request);

        $tab = $request->query('tab', 'payments');
        $isTestTab = ($tab === 'test');

        // Query scoped strictly by test mode to isolate financial data
        $query = Payment::with(['order.customer', 'transactions'])
            ->where('is_test', $isTestTab)
            ->latest();

        // 1. Status Filter
        if ($request->filled('status') && $request->status !== 'all') {
            $query->where('status', $request->status);
        }

        // 2. Method Filter
        if ($request->filled('method') && $request->method !== 'all') {
            $query->where('payment_method', $request->method);
        }

        // 3. Date Filters
        if ($request->filled('date_from')) {
            $query->whereDate('created_at', '>=', Carbon::parse($request->date_from));
        }
        if ($request->filled('date_to')) {
            $query->whereDate('created_at', '<=', Carbon::parse($request->date_to));
        }

        // 4. Search Filter (order number, transaction reference, customer name/email)
        if ($request->filled('search')) {
            $search = trim($request->search);
            $query->where(function ($q) use ($search) {
                $q->where('transaction_id', 'like', "%{$search}%")
                    ->orWhereHas('order', function ($oq) use ($search) {
                        $oq->where('order_number', 'like', "%{$search}%")
                            ->orWhereHas('customer', function ($cq) use ($search) {
                                $cq->where('name', 'like', "%{$search}%")
                                    ->orWhere('email', 'like', "%{$search}%");
                            });
                    });
            });
        }

        $payments = $query->paginate(15)->withQueryString();

        // Real financial metrics (strictly excluding test transactions)
        $realMetrics = [
            'total_volume' => (float) Payment::where('is_test', false)->where('status', PaymentStatus::PAID)->sum('amount'),
            'paid_count' => Payment::where('is_test', false)->where('status', PaymentStatus::PAID)->count(),
            'pending_count' => Payment::where('is_test', false)->where('status', PaymentStatus::PENDING)->count(),
            'failed_count' => Payment::where('is_test', false)->where('status', PaymentStatus::FAILED)->count(),
            'refunded_count' => Payment::where('is_test', false)->whereIn('status', [PaymentStatus::REFUNDED, PaymentStatus::PARTIALLY_REFUNDED])->count(),
        ];

        // Test sandbox metrics (isolated from real revenue)
        $testMetrics = [
            'total_count' => Payment::where('is_test', true)->count(),
            'paid_count' => Payment::where('is_test', true)->where('status', PaymentStatus::PAID)->count(),
            'failed_count' => Payment::where('is_test', true)->where('status', PaymentStatus::FAILED)->count(),
            'pending_count' => Payment::where('is_test', true)->where('status', PaymentStatus::PENDING)->count(),
        ];

        // Candidate pending orders for the test payment simulator
        $pendingOrders = Order::where('payment_status', PaymentStatus::PENDING)
            ->where('status', OrderStatus::PLACED)
            ->with('customer')
            ->latest()
            ->take(20)
            ->get();

        $testModeEnabled = (bool) Setting::get('test_payment_mode_enabled', config('services.test_payment.enabled', false));
        $environment = app()->environment();
        $isProduction = app()->environment('production');

        return view('admin.payments.index', compact(
            'payments',
            'tab',
            'realMetrics',
            'testMetrics',
            'pendingOrders',
            'testModeEnabled',
            'environment',
            'isProduction'
        ));
    }

    public function show(Request $request, Payment $payment): View
    {
        $this->authorizeView($request);

        $payment->load(['order.customer', 'order.items.product', 'order.items.variant', 'transactions']);

        // Deep sanitize payloads to protect sensitive credentials
        $payment->gateway_response = $this->sanitizePayload($payment->gateway_response);
        foreach ($payment->transactions as $txn) {
            $txn->raw_payload = $this->sanitizePayload($txn->raw_payload);
        }

        return view('admin.payments.show', compact('payment'));
    }

    public function toggleTestMode(Request $request): RedirectResponse
    {
        $this->authorizeManage($request);

        if (app()->environment('production')) {
            return back()->with('error', 'Test payment sandbox mode cannot be enabled in production environments.');
        }

        $current = (bool) Setting::get('test_payment_mode_enabled', config('services.test_payment.enabled', false));
        $newState = !$current;

        Setting::set(
            'test_payment_mode_enabled',
            $newState ? '1' : '0',
            'financial',
            'boolean',
            'Whether sandbox test payment gateway is enabled'
        );

        ActivityLog::log(
            'toggle_test_payment_mode',
            "Administrator " . ($newState ? 'enabled' : 'disabled') . " test payment mode.",
            null,
            ['enabled' => $newState]
        );

        $msg = $newState
            ? 'Test payment sandbox mode has been ENABLED.'
            : 'Test payment sandbox mode has been DISABLED.';

        return back()->with('success', $msg);
    }

    public function simulateTestPayment(Request $request): RedirectResponse
    {
        $this->authorizeManage($request);

        if (app()->environment('production')) {
            return back()->with('error', 'Test payment simulation is strictly forbidden in production.');
        }

        if (!Setting::get('test_payment_mode_enabled', config('services.test_payment.enabled', false))) {
            return back()->with('error', 'Test payment mode is currently disabled. Please enable it first.');
        }

        $request->validate([
            'order_id' => 'required|integer|exists:orders,id',
            'scenario' => 'required|string|in:success,failure,pending',
        ]);

        $order = Order::findOrFail($request->order_id);

        if ($order->payment_status === PaymentStatus::PAID) {
            return back()->with('error', "Order #{$order->order_number} has already been paid.");
        }

        if ($order->status === OrderStatus::CANCELLED) {
            return back()->with('error', "Order #{$order->order_number} is cancelled and cannot be paid.");
        }

        try {
            $payment = $this->paymentService->processOrderPayment($order, [
                'type' => 'test',
                'scenario' => $request->scenario,
            ]);

            ActivityLog::log(
                'simulate_test_payment',
                "Administrator simulated test payment (Scenario: {$request->scenario}) for Order #{$order->order_number}.",
                $payment,
                ['order_id' => $order->id, 'scenario' => $request->scenario, 'status' => $payment->status->value]
            );

            $scenarioLabel = match ($request->scenario) {
                'success' => 'SUCCESSFUL payment confirmed and stock permanently committed',
                'failure' => 'DECLINED payment simulated (order and reservations preserved in pending state)',
                'pending' => 'PENDING payment simulated (awaiting async settlement)',
            };

            return redirect()->route('admin.payments', ['tab' => 'test'])
                ->with('success', "Test Payment Simulation Completed: {$scenarioLabel}. Txn: {$payment->transaction_id}");
        } catch (Exception $e) {
            return back()->with('error', 'Simulation failed: ' . $e->getMessage());
        }
    }

    protected function authorizeView(Request $request): void
    {
        $user = $request->user();
        if (!$user || (!$user->isSuperAdmin() && !$user->hasRole(['super_admin', 'admin']) && !$user->hasPermission('payments.view'))) {
            abort(403, 'Unauthorized. Administrative permission (payments.view) required.');
        }
    }

    protected function authorizeManage(Request $request): void
    {
        $user = $request->user();
        if (!$user || (!$user->isSuperAdmin() && !$user->hasRole(['super_admin', 'admin']) && !$user->hasPermission('settings.manage'))) {
            abort(403, 'Unauthorized. High-level administrative permission (settings.manage) required to control test payment configurations.');
        }
    }

    protected function sanitizePayload(mixed $payload): mixed
    {
        if (empty($payload) || !is_array($payload)) {
            return $payload;
        }

        $sensitiveKeywords = ['secret', 'api_key', 'key', 'webhook_secret', 'signature', 'cvv', 'cvc', 'password', 'token', 'authorization'];

        $sanitized = [];
        foreach ($payload as $k => $v) {
            $lowerKey = strtolower((string) $k);
            $isSensitive = false;
            foreach ($sensitiveKeywords as $sk) {
                if (str_contains($lowerKey, $sk)) {
                    $isSensitive = true;
                    break;
                }
            }

            if ($isSensitive && (is_string($v) || is_numeric($v))) {
                $sanitized[$k] = '***REDACTED***';
            } elseif (is_array($v)) {
                $sanitized[$k] = $this->sanitizePayload($v);
            } else {
                $sanitized[$k] = $v;
            }
        }

        return $sanitized;
    }
}
