"use client";

import React, { useEffect, use } from "react";
import Image from "next/image";
import Link from "next/link";
import { CheckCircle2, Package, ArrowRight, Printer, Clock, Truck, ShieldCheck } from "lucide-react";
import { MOCK_ORDERS } from "@/lib/mock-data/orders";
import { formatPrice, formatDate } from "@/lib/utils";
import confetti from "canvas-confetti";

export default function OrderConfirmationPage({
  params,
}: {
  params: Promise<{ orderId: string }>;
}) {
  const resolvedParams = use(params);
  const orderId = resolvedParams.orderId;

  const order = MOCK_ORDERS.find(
    (o) => o.id.toLowerCase() === orderId.toLowerCase() || o.orderNumber.toLowerCase() === orderId.toLowerCase()
  ) || MOCK_ORDERS[0];

  useEffect(() => {
    // Fire festive celebration confetti
    try {
      confetti({
        particleCount: 80,
        spread: 60,
        origin: { y: 0.6 },
      });
    } catch (e) {
      // ignore in headless
    }
  }, []);

  return (
    <div className="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8 py-10 sm:py-16 space-y-10">
      {/* Top Banner Celebration */}
      <div className="text-center space-y-3">
        <div className="w-16 h-16 rounded-full bg-emerald-100 text-emerald-600 flex items-center justify-center mx-auto shadow-sm">
          <CheckCircle2 className="w-9 h-9" />
        </div>
        <span className="text-[11px] font-bold uppercase tracking-wider text-neutral-400">
          Payment Confirmed
        </span>
        <h1 className="text-3xl sm:text-4xl font-black text-neutral-900 tracking-tight">
          Thank You For Your Order!
        </h1>
        <p className="text-xs sm:text-sm text-neutral-600 max-w-md mx-auto">
          We received your order <strong>#{order.orderNumber}</strong>. A receipt and tracking notifications have been sent to your email.
        </p>

        <div className="pt-2 flex flex-wrap items-center justify-center gap-3">
          <Link
            href={`/account/orders/${order.id}/track`}
            className="px-6 py-2.5 bg-neutral-900 text-white text-xs font-bold rounded-full hover:bg-neutral-800 transition-colors shadow-sm flex items-center gap-1.5"
          >
            <Truck className="w-4 h-4" /> Track Live Package
          </Link>
          <button
            onClick={() => window.print()}
            className="px-5 py-2.5 border border-neutral-300 bg-white text-neutral-800 text-xs font-semibold rounded-full hover:bg-neutral-50 transition-colors flex items-center gap-1.5"
          >
            <Printer className="w-4 h-4" /> Print Receipt
          </button>
        </div>
      </div>

      {/* Order Summary Card */}
      <div className="bg-white rounded-3xl border border-neutral-200 overflow-hidden shadow-xs divide-y divide-neutral-100">
        <div className="p-6 flex flex-wrap items-center justify-between gap-4 bg-neutral-50/70">
          <div>
            <span className="text-[10px] font-bold uppercase text-neutral-400 tracking-wider">
              Order Identifier
            </span>
            <p className="text-sm font-black text-neutral-900">{order.orderNumber}</p>
          </div>
          <div>
            <span className="text-[10px] font-bold uppercase text-neutral-400 tracking-wider">
              Date Placed
            </span>
            <p className="text-xs font-semibold text-neutral-800">{formatDate(order.date)}</p>
          </div>
          <div>
            <span className="text-[10px] font-bold uppercase text-neutral-400 tracking-wider">
              Estimated Delivery
            </span>
            <p className="text-xs font-semibold text-neutral-800">{order.estimatedDelivery}</p>
          </div>
          <div>
            <span className="text-[10px] font-bold uppercase text-neutral-400 tracking-wider">
              Total Amount
            </span>
            <p className="text-sm font-black text-neutral-900">{formatPrice(order.total)}</p>
          </div>
        </div>

        {/* Items List */}
        <div className="p-6 space-y-4">
          <h3 className="text-xs font-bold uppercase tracking-wider text-neutral-400">
            Items in This Order ({order.items.length})
          </h3>
          <div className="divide-y divide-neutral-100">
            {order.items.map((item) => (
              <div key={item.id} className="py-3 flex items-center gap-4">
                <div className="relative w-14 h-18 rounded-xl overflow-hidden bg-neutral-100 shrink-0">
                  <Image src={item.image} alt={item.name} fill className="object-cover" />
                </div>
                <div className="flex-1 min-w-0">
                  <h4 className="text-xs font-bold text-neutral-900 truncate">{item.name}</h4>
                  <p className="text-[11px] text-neutral-500">
                    {item.color} • Size {item.size} • Qty {item.quantity}
                  </p>
                </div>
                <span className="text-xs font-bold text-neutral-900">
                  {formatPrice(item.price * item.quantity)}
                </span>
              </div>
            ))}
          </div>
        </div>

        {/* Addresses & Payment Breakdown */}
        <div className="p-6 grid grid-cols-1 md:grid-cols-2 gap-6 text-xs text-neutral-600">
          <div className="space-y-1">
            <h4 className="font-bold text-neutral-900 uppercase text-[10px] tracking-wider">
              Shipping Destination
            </h4>
            <p className="font-semibold text-neutral-800">
              {order.shippingAddress.firstName} {order.shippingAddress.lastName}
            </p>
            <p>{order.shippingAddress.street} {order.shippingAddress.apartment}</p>
            <p>{order.shippingAddress.city}, {order.shippingAddress.state} {order.shippingAddress.zipCode}</p>
            <p>{order.shippingAddress.phone}</p>
          </div>

          <div className="space-y-1">
            <h4 className="font-bold text-neutral-900 uppercase text-[10px] tracking-wider">
              Payment & Shipping Method
            </h4>
            <p className="font-semibold text-neutral-800">{order.paymentMethod}</p>
            <p className="text-neutral-500">Carrier: {order.carrier}</p>
            <p className="text-neutral-500">Tracking: {order.trackingNumber}</p>
          </div>
        </div>
      </div>
    </div>
  );
}

