<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Shipment;
use App\Models\ShippingMethod;
use App\Models\ShippingZone;
use Illuminate\Http\Request;

class ShippingController extends Controller
{
    public function index()
    {
        $zones = ShippingZone::with('methods')->get();
        $methods = ShippingMethod::with('zone')->get();
        $shipments = Shipment::with('order.customer')->latest()->paginate(15);

        return view('admin.shipping.index', compact('zones', 'methods', 'shipments'));
    }

    public function updateMethod(Request $request, ShippingMethod $method)
    {
        $validated = $request->validate([
            'cost' => 'required|numeric|min:0',
            'min_order_free' => 'nullable|numeric|min:0',
            'estimated_days_min' => 'required|integer|min:1',
            'estimated_days_max' => 'required|integer|min:1',
            'is_active' => 'nullable|boolean',
        ]);

        $validated['is_active'] = $request->boolean('is_active');
        $method->update($validated);

        return back()->with('success', "Shipping method '{$method->name}' updated.");
    }
}

