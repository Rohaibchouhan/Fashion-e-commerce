"use client";

import React, { useState } from "react";
import { Breadcrumb } from "@/components/ui/breadcrumb";
import { Input } from "@/components/ui/input";
import { Button } from "@/components/ui/button";
import { OrdersService } from "@/services/orders.service";
import { Order } from "@/types/order";
import { formatPrice, formatDate } from "@/lib/utils";
import { ORDER_STATUS_DETAILS } from "@/constants";
import { Search, Package, Truck, CheckCircle2, Clock, AlertCircle } from "lucide-react";
import Link from "next/link";
import Image from "next/image";

export default function OrderTrackingPage() {
  const [orderQuery, setOrderQuery] = useState("WD-88219");
  const [emailQuery, setEmailQuery] = useState("");
  const [order, setOrder] = useState<Order | null>(null);
  const [isSearching, setIsSearching] = useState(false);
  const [hasSearched, setHasSearched] = useState(false);

  const handleTrack = async (e: React.FormEvent) => {
    e.preventDefault();
    if (!orderQuery.trim()) return;

    setIsSearching(true);
    setHasSearched(true);
    const result = await OrdersService.track(orderQuery);
    setOrder(result);
    setIsSearching(false);
  };

  return (
    <div className="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8 py-8 sm:py-14 space-y-10">
      <Breadcrumb items={[{ label: "Home", href: "/" }, { label: "Track Your Order" }]} />

      <div className="text-center max-w-xl mx-auto space-y-2">
        <h1 className="text-2xl sm:text-4xl font-black text-neutral-900 tracking-tight">
          Track Your Delivery
        </h1>
        <p className="text-xs sm:text-sm text-neutral-500">
          Enter your WearDay order number (e.g. WD-88219) to view live carrier dispatch status and estimated arrival.
        </p>
      </div>

      {/* Tracking Form */}
      <div className="bg-white rounded-3xl border border-neutral-200 p-6 sm:p-8 shadow-xs max-w-2xl mx-auto">
        <form onSubmit={handleTrack} className="space-y-4">
          <div className="grid grid-cols-1 sm:grid-cols-2 gap-4">
            <Input
              label="Order Number *"
              placeholder="e.g. WD-88219"
              value={orderQuery}
              onChange={(e) => setOrderQuery(e.target.value)}
              required
            />
            <Input
              label="Email Address or Zip"
              placeholder="alex@example.com"
              value={emailQuery}
              onChange={(e) => setEmailQuery(e.target.value)}
            />
          </div>
          <Button type="submit" isLoading={isSearching} className="w-full h-12 rounded-full font-bold text-xs">
            <Search className="w-4 h-4 mr-1.5" /> Look Up Shipment
          </Button>
        </form>
      </div>

      {/* Results View */}
      {hasSearched && (
        <div className="space-y-6">
          {order ? (
            <div className="bg-white rounded-3xl border border-neutral-200 overflow-hidden shadow-xs divide-y divide-neutral-100">
              {/* Header Status Strip */}
              <div className="p-6 bg-neutral-50/70 flex flex-wrap items-center justify-between gap-4">
                <div>
                  <span className="text-[10px] font-bold uppercase text-neutral-400 tracking-wider">
                    Shipment Status
                  </span>
                  <div className="flex items-center gap-2 mt-0.5">
                    <span
                      className={`px-3 py-1 text-xs font-bold rounded-full border ${
                        ORDER_STATUS_DETAILS[order.status]?.color || "bg-neutral-100 text-neutral-800"
                      }`}
                    >
                      {ORDER_STATUS_DETAILS[order.status]?.label || order.status}
                    </span>
                    <span className="text-xs font-bold text-neutral-900">#{order.orderNumber}</span>
                  </div>
                </div>

                <div className="text-right">
                  <span className="text-[10px] font-bold uppercase text-neutral-400 tracking-wider">
                    Carrier & Tracking
                  </span>
                  <p className="text-xs font-bold text-neutral-900">{order.carrier}</p>
                  <p className="text-[11px] text-neutral-500 font-mono">{order.trackingNumber}</p>
                </div>
              </div>

              {/* 8-Stage Visual Timeline */}
              <div className="p-6 sm:p-8 space-y-6">
                <h3 className="text-xs font-bold uppercase tracking-wider text-neutral-400">
                  Shipment Progression Timeline
                </h3>

                <div className="relative pl-6 sm:pl-8 border-l-2 border-neutral-200 space-y-6">
                  {order.timeline.map((step, idx) => (
                    <div key={idx} className="relative group">
                      {/* Timeline Dot */}
                      <div
                        className={`absolute -left-[31px] sm:-left-[39px] top-0 w-6 h-6 rounded-full flex items-center justify-center text-[10px] font-bold transition-all ${
                          step.completed
                            ? "bg-neutral-900 text-white"
                            : "bg-neutral-100 border-2 border-neutral-300 text-neutral-400"
                        }`}
                      >
                        {step.completed ? <CheckCircle2 className="w-3.5 h-3.5" /> : idx + 1}
                      </div>

                      <div className="min-w-0">
                        <div className="flex flex-wrap items-baseline gap-2">
                          <h4
                            className={`text-xs font-bold ${
                              step.completed ? "text-neutral-900" : "text-neutral-400"
                            }`}
                          >
                            {step.label}
                          </h4>
                          {step.timestamp && (
                            <span className="text-[10px] text-neutral-400">
                              {formatDate(step.timestamp)}
                            </span>
                          )}
                        </div>
                        <p className="text-xs text-neutral-500 mt-0.5">{step.description}</p>
                        {step.location && (
                          <p className="text-[11px] text-neutral-600 font-semibold mt-1">
                            📍 {step.location}
                          </p>
                        )}
                      </div>
                    </div>
                  ))}
                </div>
              </div>

              {/* Package Items Preview */}
              <div className="p-6 space-y-3">
                <h4 className="text-xs font-bold uppercase tracking-wider text-neutral-400">
                  Contents of Package
                </h4>
                <div className="grid grid-cols-1 sm:grid-cols-2 gap-3">
                  {order.items.map((item) => (
                    <div key={item.id} className="flex items-center gap-3 p-3 rounded-2xl bg-neutral-50 border border-neutral-100">
                      <div className="relative w-12 h-14 rounded-xl overflow-hidden bg-white shrink-0">
                        <Image src={item.image} alt={item.name} fill className="object-cover" />
                      </div>
                      <div className="min-w-0 flex-1">
                        <p className="text-xs font-bold text-neutral-900 truncate">{item.name}</p>
                        <p className="text-[11px] text-neutral-500">
                          {item.color} • {item.size} • Qty {item.quantity}
                        </p>
                      </div>
                    </div>
                  ))}
                </div>
              </div>
            </div>
          ) : (
            <div className="text-center py-12 rounded-3xl bg-neutral-50 border border-neutral-200 p-8 space-y-3">
              <AlertCircle className="w-8 h-8 text-rose-500 mx-auto" />
              <h3 className="text-sm font-bold text-neutral-900">Order Not Located</h3>
              <p className="text-xs text-neutral-500 max-w-sm mx-auto">
                We could not find an order matching "{orderQuery}". Please verify the order number on your confirmation email.
              </p>
            </div>
          )}
        </div>
      )}
    </div>
  );
}

