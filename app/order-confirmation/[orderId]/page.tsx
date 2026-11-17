"use client";

import React, { useEffect, useState, use } from "react";
import Image from "next/image";
import Link from "next/link";
import { CheckCircle2, Clock, Truck, ShieldCheck, Printer, ArrowRight, Package } from "lucide-react";
import { OrdersService } from "@/services/orders.service";
import { Order } from "@/types/order";
import { formatPrice, formatDate } from "@/lib/utils";
import confetti from "canvas-confetti";

export default function OrderConfirmationPage({
  params,
}: {
  params: Promise<{ orderId: string }>;
}) {
  const resolvedParams = use(params);
  const orderId = resolvedParams.orderId;

  const [order, setOrder] = useState<Order | null>(null);
  const [isLoading, setIsLoading] = useState(true);

  useEffect(() => {
    let isMounted = true;
    async function loadOrder() {
      try {
        const data = await OrdersService.getById(orderId);
        if (isMounted) setOrder(data);
      } catch (err) {
        console.error("Failed to load confirmation order:", err);
      } finally {
        if (isMounted) setIsLoading(false);
      }
    }
    loadOrder();

    try {
      confetti({
        particleCount: 70,
        spread: 60,
        origin: { y: 0.6 },
      });
    } catch (e) {
      // ignore in headless
    }

    return () => {
      isMounted = false;
    };
  }, [orderId]);

  if (isLoading) {
    return (
      <div className="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8 py-24 text-center">
        <p className="text-xs text-neutral-400">Loading order details...</p>
      </div>
    );
  }

  if (!order) {
    return (
      <div className="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8 py-24 text-center space-y-4">
        <div className="w-16 h-16 rounded-full bg-neutral-100 text-neutral-800 flex items-center justify-center mx-auto shadow-sm">
          <Package className="w-8 h-8" />
        </div>
        <h1 className="text-3xl font-black text-neutral-900 tracking-tight">
          Order Recorded
        </h1>
        <p className="text-xs text-neutral-500 max-w-md mx-auto">
          Your order reference is <strong>#{orderId}</strong>.
        </p>
        <Link
          href="/account/orders"
          className="inline-block px-6 py-2.5 bg-neutral-900 text-white text-xs font-bold rounded-full hover:bg-neutral-800 transition-colors shadow-sm"
        >
          View Account Orders
        </Link>
      </div>
    );
  }

  return (
    <div className="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8 py-10 sm:py-16 space-y-10">
      {/* Top Banner */}
      <div className="text-center space-y-3">
        <div className="w-16 h-16 rounded-full bg-amber-50 text-amber-700 flex items-center justify-center mx-auto border border-amber-200 shadow-sm">
          <Clock className="w-8 h-8" />
        </div>
        <div className="inline-flex items-center gap-1.5 px-3 py-1 rounded-full bg-amber-50 border border-amber-200 text-amber-800 text-xs font-bold uppercase tracking-wider">
          <span className="w-2 h-2 rounded-full bg-amber-500 animate-pulse" />
          <span>Order Placed • Payment Pending</span>
        </div>
        <h1 className="text-3xl sm:text-4xl font-black text-neutral-900 tracking-tight">
          Thank You For Your Order!
        </h1>
        <p className="text-xs sm:text-sm text-neutral-600 max-w-lg mx-auto leading-relaxed">
          We have received your order <strong>#{order.orderNumber}</strong>. Your garments and inventory have been reserved.
        </p>

        <div className="pt-2 flex flex-wrap items-center justify-center gap-3">
          <Link
            href={`/account/orders/${order.id}`}
            className="px-6 py-2.5 bg-neutral-900 text-white text-xs font-bold rounded-full hover:bg-neutral-800 transition-colors shadow-sm flex items-center gap-1.5"
          >
            <span>View Full Order Details</span>
            <ArrowRight className="w-3.5 h-3.5" />
          </Link>
          <button
            onClick={() => window.print()}
            className="px-5 py-2.5 bg-white border border-neutral-200 text-neutral-800 text-xs font-semibold rounded-full hover:bg-neutral-50 transition-colors shadow-xs flex items-center gap-1.5 cursor-pointer"
          >
            <Printer className="w-3.5 h-3.5" /> Print Receipt
          </button>
        </div>
      </div>

      {/* Next Steps for Payment Box */}
      <div className="max-w-3xl mx-auto p-5 sm:p-6 rounded-3xl bg-neutral-50 border border-neutral-200 space-y-3">
        <div className="flex items-center gap-2">
          <ShieldCheck className="w-4 h-4 text-neutral-700" />
          <h3 className="text-xs font-bold uppercase tracking-wider text-neutral-900">
            Next Steps for Payment & Fulfillment
          </h3>
        </div>
        <div className="grid grid-cols-1 sm:grid-cols-3 gap-3 pt-1 text-xs">
          <div className="p-3 bg-white rounded-2xl border border-neutral-200/80 space-y-1">
            <span className="text-[10px] font-bold text-neutral-400 uppercase">Step 1</span>
            <p className="font-bold text-neutral-900">Inventory Reserved</p>
            <p className="text-neutral-500 text-[11px]">Your items are locked and will not be sold to other shoppers.</p>
          </div>
          <div className="p-3 bg-white rounded-2xl border border-neutral-200/80 space-y-1">
            <span className="text-[10px] font-bold text-amber-600 uppercase">Step 2 • Current</span>
            <p className="font-bold text-neutral-900">Payment Verification</p>
            <p className="text-neutral-500 text-[11px]">Payment instructions sent to your email. Status will update once settled.</p>
          </div>
          <div className="p-3 bg-white rounded-2xl border border-neutral-200/80 space-y-1">
            <span className="text-[10px] font-bold text-neutral-400 uppercase">Step 3</span>
            <p className="font-bold text-neutral-900">Carrier Dispatch</p>
            <p className="text-neutral-500 text-[11px]">Order will be tailored, packaged, and handed to courier.</p>
          </div>
        </div>
      </div>

      {/* Main Order Card */}
      <div className="bg-white rounded-3xl border border-neutral-200 overflow-hidden shadow-xs divide-y divide-neutral-100">
        <div className="p-6 sm:p-8 flex flex-wrap items-center justify-between gap-4 bg-neutral-50/60">
          <div>
            <span className="text-[10px] uppercase font-bold text-neutral-400 tracking-wider">
              Order Reference
            </span>
            <p className="text-lg font-black text-neutral-900">#{order.orderNumber}</p>
            <p className="text-xs text-neutral-500 mt-0.5">Placed on {formatDate(order.date)}</p>
          </div>

          <div className="text-right">
            <span className="text-[10px] uppercase font-bold text-neutral-400 tracking-wider">
              Payable Amount
            </span>
            <p className="text-2xl font-black text-neutral-900">{formatPrice(order.total)}</p>
            <p className="text-xs text-amber-700 font-semibold mt-0.5">Status: Pending Payment</p>
          </div>
        </div>

        {/* Items Summary */}
        <div className="p-6 sm:p-8 space-y-4">
          <h3 className="text-xs font-bold uppercase tracking-wider text-neutral-400">
            Order Items ({order.items?.length || 0})
          </h3>
          <div className="divide-y divide-neutral-100">
            {order.items?.map((item) => (
              <div key={item.id} className="py-4 flex items-center justify-between gap-4">
                <div className="flex items-center gap-4">
                  <div className="relative w-14 h-18 rounded-xl overflow-hidden bg-neutral-100 shrink-0">
                    {item.image ? (
                      <Image src={item.image} alt={item.name} fill className="object-cover" />
                    ) : (
                      <div className="w-full h-full bg-neutral-200 flex items-center justify-center text-xs text-neutral-400">
                        WD
                      </div>
                    )}
                  </div>
                  <div>
                    <h4 className="text-sm font-bold text-neutral-900">{item.name}</h4>
                    <p className="text-xs text-neutral-500 mt-0.5">
                      {item.color} • {item.size} • Qty {item.quantity}
                    </p>
                    {item.sku && <p className="text-[10px] text-neutral-400 font-mono">SKU: {item.sku}</p>}
                  </div>
                </div>

                <p className="text-sm font-bold text-neutral-900">{formatPrice(item.price * item.quantity)}</p>
              </div>
            ))}
          </div>
        </div>

        {/* Pricing Breakdown */}
        <div className="p-6 sm:p-8 bg-neutral-50/40">
          <div className="max-w-sm ml-auto space-y-2 text-xs text-neutral-600">
            <div className="flex justify-between">
              <span>Subtotal</span>
              <span className="font-semibold text-neutral-900">{formatPrice(order.subtotal)}</span>
            </div>
            {order.discount > 0 && (
              <div className="flex justify-between text-emerald-700 font-semibold">
                <span>Discount</span>
                <span>-{formatPrice(order.discount)}</span>
              </div>
            )}
            <div className="flex justify-between">
              <span>Shipping ({order.carrier || "Ground"})</span>
              <span className="font-semibold text-neutral-900">
                {order.shipping === 0 ? "FREE" : formatPrice(order.shipping)}
              </span>
            </div>
            <div className="flex justify-between">
              <span>Sales Tax</span>
              <span className="font-semibold text-neutral-900">{formatPrice(order.tax)}</span>
            </div>
            <div className="flex justify-between text-base font-black text-neutral-900 pt-3 border-t border-neutral-200">
              <span>Total</span>
              <span>{formatPrice(order.total)}</span>
            </div>
          </div>
        </div>

        {/* Shipping & Delivery Info */}
        <div className="p-6 sm:p-8 grid grid-cols-1 sm:grid-cols-2 gap-8">
          <div className="space-y-2 text-xs">
            <h4 className="font-bold uppercase tracking-wider text-neutral-400 text-[10px]">
              Delivery Destination
            </h4>
            <p className="font-bold text-neutral-900">
              {order.shippingAddress?.firstName || (order.shippingAddress as any)?.first_name}{" "}
              {order.shippingAddress?.lastName || (order.shippingAddress as any)?.last_name}
            </p>
            <p className="text-neutral-600">{order.shippingAddress?.street}</p>
            <p className="text-neutral-600">
              {order.shippingAddress?.city}, {order.shippingAddress?.state} {order.shippingAddress?.zipCode || (order.shippingAddress as any)?.postal_code}
            </p>
            <p className="text-neutral-600">{order.shippingAddress?.country || "United States"}</p>
          </div>

          <div className="space-y-2 text-xs">
            <h4 className="font-bold uppercase tracking-wider text-neutral-400 text-[10px]">
              Estimated Delivery
            </h4>
            <p className="font-bold text-neutral-900">
              {order.estimatedDelivery ? formatDate(order.estimatedDelivery) : "3-5 Business Days"}
            </p>
            <p className="text-neutral-500">
              Carrier: <strong>{order.carrier || "Standard Ground"}</strong>
            </p>
            <p className="text-neutral-500">
              Tracking Number: <strong>{order.trackingNumber || "Pending Allocation"}</strong>
            </p>
          </div>
        </div>
      </div>
    </div>
  );
}
