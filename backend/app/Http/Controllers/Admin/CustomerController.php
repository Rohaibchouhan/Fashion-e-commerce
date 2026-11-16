<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\User;
use App\Services\UserService;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\View\View;

class CustomerController extends Controller
{
    public function __construct(protected UserService $userService) {}

    public function index(Request $request): View
    {
        $params = array_merge($request->all(), ['role' => 'customer']);
        $customers = $this->userService->getUsers($params);

        return view('admin.customers.index', compact('customers'));
    }

    public function show(User $customer): View
    {
        $customer->load(['customerProfile', 'addresses', 'orders.items', 'returnRequests']);
        return view('admin.customers.show', compact('customer'));
    }

    public function toggleStatus(User $customer): RedirectResponse
    {
        $newStatus = $this->userService->toggleUserStatus($customer, Auth::user());
        return back()->with('success', "Customer account is now {$newStatus}.");
    }

    public function destroy(User $customer): RedirectResponse
    {
        $result = $this->userService->deleteUser($customer, Auth::user());
        return redirect()->route('admin.customers')->with('success', $result['message']);
    }
}
