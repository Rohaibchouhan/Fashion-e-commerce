"use client";

import React, { use } from "react";
import Link from "next/link";
import Image from "next/image";
import { notFound } from "next/navigation";
import { useQuery } from "@tanstack/react-query";
import { OrdersService } from "@/services/orders.service";
import { formatPrice, formatDate, formatDateTime } from "@/lib/utils";
import { ORDER_STATUS_DETAILS, ORDER_TIMELINE_STAGES } from "@/constants";
import {
  ArrowLeft,
  Truck,
  CheckCircle2,
  Clock,
  MapPin,
  Package,
  Sparkles,
  RefreshCw,
} from "lucide-react";

export default function OrderLiveTrackPage({
  params,
}: {
  params: Promise<{ id: string }>;
}) {
  const resolvedParams = use(params);
  const orderId = resolvedParams.id;

  // Real-time enabled TanStack Query with targeted cache invalidation
  const { data: order, isLoading } = useQuery({
    queryKey: ["order", orderId],
    queryFn: () => OrdersService.getById(orderId),
    refetchInterval: false,
  });

  if (isLoading) {
    return (
      <div className="bg-white rounded-3xl p-12 text-center text-xs text-neutral-400">
        Loading live tracking telemetry...
      </div>
    );
  }

  if (!order) {
    notFound();
  }

  const currentStageIndex = ORDER_TIMELINE_STAGES.indexOf(order.status as any);

  return (
    <div className="space-y-6">
      <div className="flex items-center justify-between">
        <Link
          href={`/account/orders/${order.id}`}
          className="inline-flex items-center gap-1.5 text-xs font-semibold text-neutral-600 hover:text-neutral-950 transition-colors"
        >
          <ArrowLeft className="w-4 h-4" /> Back to Order #{order.orderNumber}
        </Link>

        <span className="text-[11px] font-semibold text-emerald-700 bg-emerald-50 px-3 py-1 rounded-full border border-emerald-200 flex items-center gap-1.5">
          <span className="w-2 h-2 rounded-full bg-emerald-500 animate-ping" />
          Live Telemetry Active
        </span>
      </div>

      <div className="bg-white rounded-3xl border border-neutral-200 overflow-hidden shadow-xs divide-y divide-neutral-100">
        {/* Banner with Status & Estimated Arrival */}
        <div className="p-6 sm:p-8 bg-neutral-900 text-white space-y-4">
          <div className="flex flex-wrap items-center justify-between gap-4">
            <div>
              <span className="text-[10px] uppercase font-bold tracking-widest text-neutral-400">
                Fulfillment Status
              </span>
              <h1 className="text-2xl sm:text-3xl font-black tracking-tight mt-0.5">
                {ORDER_STATUS_DETAILS[order.status]?.label || order.status}
              </h1>
              <p className="text-xs text-neutral-300 mt-1">
                Order #{order.orderNumber} • Handled by <strong>{order.carrier}</strong>
              </p>
            </div>

            <div className="text-right sm:text-right">
              <span className="text-[10px] uppercase font-bold tracking-widest text-neutral-400">
                Estimated Delivery
              </span>
              <p className="text-lg font-black text-amber-400 mt-0.5">
                {order.estimatedDelivery}
              </p>
              <p className="text-xs text-neutral-400 font-mono">
                Tracking #{order.trackingNumber}
              </p>
            </div>
          </div>
        </div>

        {/* 8-Stage Visual Timeline Flow */}
        <div className="p-6 sm:p-10 space-y-8">
          <div className="flex items-center justify-between">
            <h3 className="text-xs font-bold uppercase tracking-wider text-neutral-900">
              Visual Shipment Progression (8-Stage Timeline)
            </h3>
            <span className="text-xs text-neutral-400">
              Stage {currentStageIndex + 1} of 8
            </span>
          </div>

          <div className="relative pl-6 sm:pl-8 border-l-2 border-neutral-200 space-y-8">
            {order.timeline.map((step, idx) => {
              const isPast = idx < currentStageIndex;
              const isCurrent = idx === currentStageIndex;
              const isFuture = idx > currentStageIndex;

              return (
                <div key={idx} className="relative group">
                  {/* Step Node Marker */}
                  <div
                    className={`absolute -left-[31px] sm:-left-[39px] top-0 w-6 h-6 rounded-full flex items-center justify-center text-[10px] font-bold transition-all ${
                      isCurrent
                        ? "bg-neutral-950 text-white ring-4 ring-neutral-200 scale-110 shadow-sm"
                        : isPast
                        ? "bg-emerald-600 text-white"
                        : "bg-white border-2 border-neutral-300 text-neutral-400"
                    }`}
                  >
                    {isPast ? (
                      <CheckCircle2 className="w-3.5 h-3.5" />
                    ) : isCurrent ? (
                      <Truck className="w-3.5 h-3.5 animate-pulse" />
                    ) : (
                      idx + 1
                    )}
                  </div>

                  {/* Step Description */}
                  <div className="space-y-1">
                    <div className="flex flex-wrap items-center gap-2">
                      <h4
                        className={`text-xs font-bold ${
                          isCurrent
                            ? "text-neutral-950 text-sm font-black"
                            : isPast
                            ? "text-neutral-900 font-bold"
                            : "text-neutral-400 font-medium"
                        }`}
                      >
                        {step.label}
                      </h4>
                      {isCurrent && (
                        <span className="px-2 py-0.5 text-[9px] font-extrabold uppercase bg-neutral-900 text-white rounded-full">
                          Current Stage
                        </span>
                      )}
                      {step.timestamp && (
                        <span className="text-[10px] text-neutral-400 ml-auto font-medium">
                          {formatDateTime(step.timestamp)}
                        </span>
                      )}
                    </div>
                    <p className="text-xs text-neutral-500 leading-relaxed">
                      {step.description}
                    </p>
                    {step.location && (
                      <p className="text-xs text-neutral-700 font-semibold pt-0.5 flex items-center gap-1">
                        <MapPin className="w-3.5 h-3.5 text-neutral-900" />
                        <span>{step.location}</span>
                      </p>
                    )}
                  </div>
                </div>
              );
            })}
          </div>
        </div>

        {/* Destination & Package Items */}
        <div className="p-6 bg-neutral-50/60 grid grid-cols-1 md:grid-cols-2 gap-6 text-xs text-neutral-600">
          <div>
            <h4 className="font-bold text-neutral-900 uppercase text-[10px] tracking-wider mb-1">
              Delivery Destination
            </h4>
            <p className="font-semibold text-neutral-900">
              {order.shippingAddress.firstName} {order.shippingAddress.lastName}
            </p>
            <p>{order.shippingAddress.street} {order.shippingAddress.apartment}</p>
            <p>{order.shippingAddress.city}, {order.shippingAddress.state} {order.shippingAddress.zipCode}</p>
          </div>

          <div>
            <h4 className="font-bold text-neutral-900 uppercase text-[10px] tracking-wider mb-1">
              Package Contents ({order.items.length} Items)
            </h4>
            <p className="truncate">
              {order.items.map((i) => `${i.name} (${i.color})`).join(", ")}
            </p>
          </div>
        </div>
      </div>
    </div>
  );
}

