"use client";

import React, { use } from "react";
import Link from "next/link";
import Image from "next/image";
import { notFound } from "next/navigation";
import { MOCK_ORDERS } from "@/lib/mock-data/orders";
import { formatPrice, formatDate } from "@/lib/utils";
import { ORDER_STATUS_DETAILS } from "@/constants";
import {
  ArrowLeft,
  Truck,
  Printer,
  Download,
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

  const order = MOCK_ORDERS.find(
    (o) => o.id.toLowerCase() === orderId.toLowerCase() || o.orderNumber.toLowerCase() === orderId.toLowerCase()
  );

  if (!order) {
    notFound();
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
            className="px-4 py-2 bg-white border border-neutral-200 rounded-full text-xs font-semibold text-neutral-700 hover:bg-neutral-50 flex items-center gap-1.5"
          >
            <Printer className="w-3.5 h-3.5" /> Print Invoice
          </button>
          <Link
            href={`/account/orders/${order.id}/track`}
            className="px-4 py-2 bg-neutral-900 text-white rounded-full text-xs font-bold hover:bg-neutral-800 flex items-center gap-1.5 shadow-xs"
          >
            <Truck className="w-3.5 h-3.5" /> Live Package Track
          </Link>
        </div>
      </div>

      {/* Main Order Card */}
      <div className="bg-white rounded-3xl border border-neutral-200 overflow-hidden shadow-xs divide-y divide-neutral-100">
        {/* Order Header Summary */}
        <div className="p-6 bg-neutral-50/80 flex flex-wrap items-center justify-between gap-4">
          <div>
            <div className="flex items-center gap-2.5">
              <h1 className="text-lg font-black text-neutral-900">Order #{order.orderNumber}</h1>
              <span
                className={`px-3 py-1 text-xs font-bold rounded-full border ${
                  ORDER_STATUS_DETAILS[order.status]?.color
                }`}
              >
                {ORDER_STATUS_DETAILS[order.status]?.label}
              </span>
            </div>
            <p className="text-xs text-neutral-500 mt-1">
              Placed on {formatDate(order.date)} • Payment via {order.paymentMethod}
            </p>
          </div>

          <div className="text-right">
            <span className="text-[10px] uppercase font-bold text-neutral-400 tracking-wider">
              Carrier & Tracking
            </span>
            <p className="text-xs font-bold text-neutral-900">{order.carrier}</p>
            <p className="text-[11px] text-neutral-500 font-mono">{order.trackingNumber}</p>
          </div>
        </div>

        {/* Ordered Items List */}
        <div className="p-6 space-y-4">
          <div className="flex items-center justify-between">
            <h3 className="text-xs font-bold uppercase tracking-wider text-neutral-400">
              Purchased Items ({order.items.length})
            </h3>
            {order.status === "delivered" && (
              <Link
                href={`/account/returns/new?orderId=${order.id}`}
                className="text-xs font-semibold text-neutral-900 underline flex items-center gap-1"
              >
                <RotateCcw className="w-3.5 h-3.5" /> Request a Return or Exchange
              </Link>
            )}
          </div>

          <div className="divide-y divide-neutral-100">
            {order.items.map((item) => (
              <div key={item.id} className="py-4 flex items-center justify-between gap-4">
                <div className="flex items-center gap-4">
                  <div className="relative w-16 h-20 rounded-2xl overflow-hidden bg-neutral-100 shrink-0 border border-neutral-100">
                    <Image src={item.image} alt={item.name} fill className="object-cover" />
                  </div>
                  <div>
                    <Link
                      href={`/products/${item.slug}`}
                      className="text-xs font-bold text-neutral-900 hover:underline block truncate max-w-sm"
                    >
                      {item.name}
                    </Link>
                    <p className="text-[11px] text-neutral-500 mt-0.5">
                      Color: {item.color} • Size: {item.size}
                    </p>
                    <p className="text-xs font-semibold text-neutral-900 mt-1">
                      {formatPrice(item.price)} × {item.quantity}
                    </p>
                  </div>
                </div>

                <div className="text-right">
                  <span className="text-sm font-bold text-neutral-900">
                    {formatPrice(item.price * item.quantity)}
                  </span>
                </div>
              </div>
            ))}
          </div>
        </div>

        {/* Shipping Destination & Pricing Breakdown */}
        <div className="p-6 grid grid-cols-1 md:grid-cols-2 gap-8 text-xs text-neutral-600">
          <div className="space-y-4">
            <div>
              <h4 className="font-bold text-neutral-900 uppercase text-[10px] tracking-wider mb-1">
                Shipping Destination
              </h4>
              <p className="font-semibold text-neutral-900">
                {order.shippingAddress.firstName} {order.shippingAddress.lastName}
              </p>
              <p>{order.shippingAddress.street} {order.shippingAddress.apartment}</p>
              <p>{order.shippingAddress.city}, {order.shippingAddress.state} {order.shippingAddress.zipCode}</p>
              <p>{order.shippingAddress.country}</p>
              <p className="text-neutral-500 mt-1">{order.shippingAddress.phone}</p>
            </div>

            <div>
              <h4 className="font-bold text-neutral-900 uppercase text-[10px] tracking-wider mb-1">
                Billing Information
              </h4>
              <p>{order.paymentMethod}</p>
              <p className="text-emerald-700 font-semibold mt-0.5">Payment Status: Authorized & Paid</p>
            </div>
          </div>

          <div className="space-y-2.5 bg-neutral-50 p-5 rounded-2xl border border-neutral-100">
            <h4 className="font-bold text-neutral-900 uppercase text-[10px] tracking-wider mb-2">
              Cost Summary
            </h4>
            <div className="flex justify-between">
              <span>Items Subtotal</span>
              <span className="font-semibold text-neutral-900">{formatPrice(order.subtotal)}</span>
            </div>
            {order.discount > 0 && (
              <div className="flex justify-between text-emerald-700 font-semibold">
                <span>Discount</span>
                <span>-{formatPrice(order.discount)}</span>
              </div>
            )}
            <div className="flex justify-between">
              <span>Shipping</span>
              <span className="font-semibold text-neutral-900">
                {order.shipping === 0 ? "FREE" : formatPrice(order.shipping)}
              </span>
            </div>
            <div className="flex justify-between">
              <span>Estimated Sales Tax</span>
              <span className="font-semibold text-neutral-900">{formatPrice(order.tax)}</span>
            </div>
            <div className="flex justify-between text-base font-black text-neutral-900 pt-3 border-t border-neutral-200">
              <span>Total Paid</span>
              <span>{formatPrice(order.total)}</span>
            </div>
          </div>
        </div>
      </div>
    </div>
  );
}

