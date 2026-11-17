"use client";

import React, { use, useEffect, useState } from "react";
import Link from "next/link";
import Image from "next/image";
import { OrdersService } from "@/services/orders.service";
import { Order } from "@/types/order";
import { formatPrice, formatDate } from "@/lib/utils";
import { ORDER_STATUS_DETAILS } from "@/constants";
import {
  ArrowLeft,
  Truck,
  Printer,
  RotateCcw,
  ShieldCheck,
  CheckCircle2,
} from "lucide-react";

export default function OrderDetailsPage({
  params,
}: {
  params: Promise<{ id: string }>;
}) {
  const resolvedParams = use(params);
  const orderId = resolvedParams.id;

  const [order, setOrder] = useState<Order | null>(null);
  const [isLoading, setIsLoading] = useState(true);

  useEffect(() => {
    let isMounted = true;
    async function loadOrder() {
      try {
        const data = await OrdersService.getById(orderId);
        if (isMounted) setOrder(data);
      } catch (err) {
        console.error("Failed to load order:", err);
      } finally {
        if (isMounted) setIsLoading(false);
      }
    }
    loadOrder();
    return () => {
      isMounted = false;
    };
  }, [orderId]);

  if (isLoading) {
    return (
      <div className="py-24 text-center bg-white rounded-3xl border border-neutral-200">
        <p className="text-xs text-neutral-400">Loading order receipt...</p>
      </div>
    );
  }

  if (!order) {
    return (
      <div className="py-24 text-center bg-white rounded-3xl border border-neutral-200 space-y-4">
        <h2 className="text-lg font-bold text-neutral-900">Order Not Found</h2>
        <p className="text-xs text-neutral-500">Could not locate order #{orderId}.</p>
        <Link
          href="/account/orders"
          className="inline-block px-5 py-2.5 bg-neutral-900 text-white text-xs font-bold rounded-full hover:bg-neutral-800 transition-colors"
        >
          Back to Orders
        </Link>
      </div>
    );
  }

  return (
    <div className="space-y-6">
      {/* Top Navigation */}
      <div className="flex items-center justify-between">
        <Link
          href="/account/orders"
          className="inline-flex items-center gap-1.5 text-xs font-semibold text-neutral-600 hover:text-neutral-950 transition-colors"
        >
          <ArrowLeft className="w-4 h-4" /> Back to All Orders
        </Link>

        <div className="flex items-center gap-2">
          <button
            onClick={() => window.print()}
            className="px-4 py-2 bg-white border border-neutral-200 rounded-full text-xs font-semibold text-neutral-700 hover:bg-neutral-50 flex items-center gap-1.5 cursor-pointer shadow-xs"
          >
            <Printer className="w-3.5 h-3.5" /> Print Receipt
          </button>
          <Link
            href={`/account/orders/${order.id}/track`}
            className="px-4 py-2 bg-neutral-900 hover:bg-neutral-800 text-white text-xs font-bold rounded-full flex items-center gap-1.5 shadow-xs"
          >
            <Truck className="w-3.5 h-3.5" /> Track Package
          </Link>
        </div>
      </div>

      {/* Main Order Card */}
      <div className="bg-white rounded-3xl border border-neutral-200 overflow-hidden shadow-xs divide-y divide-neutral-100">
        {/* Banner Header */}
        <div className="p-6 sm:p-8 bg-neutral-50/70 flex flex-wrap items-center justify-between gap-4">
          <div>
            <div className="flex items-center gap-3">
              <h1 className="text-xl sm:text-2xl font-black text-neutral-900 tracking-tight">
                Order #{order.orderNumber}
              </h1>
              <span
                className={`px-3 py-1 text-xs font-bold rounded-full border ${
                  ORDER_STATUS_DETAILS[order.status]?.color || "bg-neutral-100 text-neutral-800"
                }`}
              >
                {ORDER_STATUS_DETAILS[order.status]?.label || order.status}
              </span>
            </div>
            <p className="text-xs text-neutral-500 mt-1">
              Placed on {formatDate(order.date)} • Payment via {order.paymentMethod}
            </p>
          </div>

          <div className="text-right">
            <span className="text-[10px] uppercase font-bold text-neutral-400 tracking-wider">
              Total Amount
            </span>
            <p className="text-2xl font-black text-neutral-900">{formatPrice(order.total)}</p>
          </div>
        </div>

        {/* Items List */}
        <div className="p-6 sm:p-8 space-y-4">
          <h3 className="text-xs font-bold uppercase tracking-wider text-neutral-400">
            Purchased Items ({order.items?.length || 0})
          </h3>
          <div className="divide-y divide-neutral-100">
            {order.items?.map((item) => (
              <div key={item.id} className="py-4 flex items-center justify-between gap-4">
                <div className="flex items-center gap-4">
                  <div className="relative w-16 h-20 rounded-xl overflow-hidden bg-neutral-100 shrink-0">
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
                      Color: {item.color} • Size: {item.size}
                    </p>
                    <p className="text-xs text-neutral-500">Qty: {item.quantity}</p>
                  </div>
                </div>

                <div className="text-right">
                  <p className="text-sm font-bold text-neutral-900">{formatPrice(item.price)}</p>
                  <p className="text-xs text-neutral-400 mt-0.5">
                    Subtotal: {formatPrice(item.price * item.quantity)}
                  </p>
                </div>
              </div>
            ))}
          </div>
        </div>

        {/* Two-Column Details: Shipping & Financial Breakdown */}
        <div className="p-6 sm:p-8 grid grid-cols-1 sm:grid-cols-2 gap-8">
          {/* Shipping Details */}
          <div className="space-y-4">
            <h3 className="text-xs font-bold uppercase tracking-wider text-neutral-400">
              Delivery Address & Carrier
            </h3>
            <div className="text-xs text-neutral-700 space-y-1">
              <p className="font-bold text-neutral-900">
                {order.shippingAddress?.firstName || (order.shippingAddress as any)?.first_name}{" "}
                {order.shippingAddress?.lastName || (order.shippingAddress as any)?.last_name}
              </p>
              <p>{order.shippingAddress?.street}</p>
              {order.shippingAddress?.apartment && <p>{order.shippingAddress.apartment}</p>}
              <p>
                {order.shippingAddress?.city}, {order.shippingAddress?.state}{" "}
                {order.shippingAddress?.zipCode || (order.shippingAddress as any)?.postal_code}
              </p>
              <p>{order.shippingAddress?.country}</p>
            </div>

            <div className="pt-2 text-xs text-neutral-500 space-y-1">
              <p>
                Carrier: <strong>{order.carrier || "Standard Ground"}</strong>
              </p>
              <p>
                Tracking Number: <strong>{order.trackingNumber || "Pending"}</strong>
              </p>
            </div>
          </div>

          {/* Pricing Summary */}
          <div className="space-y-3 bg-neutral-50/70 p-5 rounded-2xl">
            <h3 className="text-xs font-bold uppercase tracking-wider text-neutral-400 mb-2">
              Payment Summary
            </h3>
            <div className="flex justify-between text-xs text-neutral-600">
              <span>Subtotal</span>
              <span>{formatPrice(order.subtotal)}</span>
            </div>
            {order.discount > 0 && (
              <div className="flex justify-between text-xs text-emerald-600 font-semibold">
                <span>Discount</span>
                <span>-{formatPrice(order.discount)}</span>
              </div>
            )}
            <div className="flex justify-between text-xs text-neutral-600">
              <span>Shipping</span>
              <span>{order.shipping === 0 ? "Free" : formatPrice(order.shipping)}</span>
            </div>
            <div className="flex justify-between text-xs text-neutral-600">
              <span>Estimated Tax</span>
              <span>{formatPrice(order.tax)}</span>
            </div>
            <div className="pt-2 border-t border-neutral-200 flex justify-between text-sm font-black text-neutral-900">
              <span>Total Paid</span>
              <span>{formatPrice(order.total)}</span>
            </div>
          </div>
        </div>
      </div>
    </div>
  );
}
