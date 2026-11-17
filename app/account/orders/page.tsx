"use client";

import React, { useState, useEffect } from "react";
import Link from "next/link";
import Image from "next/image";
import { OrdersService } from "@/services/orders.service";
import { Order } from "@/types/order";
import { formatPrice, formatDate } from "@/lib/utils";
import { ORDER_STATUS_DETAILS } from "@/constants";
import { Search, Package, Truck, ArrowRight, Eye, RefreshCw, RotateCcw } from "lucide-react";
import { Tabs } from "@/components/ui/tabs";

export default function AccountOrdersPage() {
  const [orders, setOrders] = useState<Order[]>([]);
  const [isLoading, setIsLoading] = useState(true);
  const [statusFilter, setStatusFilter] = useState("all");
  const [searchQuery, setSearchQuery] = useState("");

  useEffect(() => {
    let isMounted = true;
    async function fetchOrders() {
      try {
        const data = await OrdersService.getAll();
        if (isMounted) setOrders(data);
      } catch (err) {
        console.error("Failed to fetch customer orders:", err);
      } finally {
        if (isMounted) setIsLoading(false);
      }
    }
    fetchOrders();
    return () => {
      isMounted = false;
    };
  }, []);

  const filteredOrders = orders.filter((order) => {
    const matchesStatus = statusFilter === "all" || order.status === statusFilter;
    const matchesQuery =
      searchQuery.trim() === "" ||
      order.orderNumber.toLowerCase().includes(searchQuery.toLowerCase()) ||
      order.items?.some((i) => i.name.toLowerCase().includes(searchQuery.toLowerCase()));
    return matchesStatus && matchesQuery;
  });

  const filterTabs = [
    { id: "all", label: "All Orders", count: orders.length },
    {
      id: "processing",
      label: "Processing",
      count: orders.filter((o) => o.status === "processing").length,
    },
    {
      id: "dispatched",
      label: "Dispatched",
      count: orders.filter((o) => o.status === "dispatched" || o.status === "in_transit").length,
    },
    {
      id: "delivered",
      label: "Delivered",
      count: orders.filter((o) => o.status === "delivered").length,
    },
  ];

  return (
    <div className="space-y-6">
      {/* Header */}
      <div className="bg-white rounded-3xl border border-neutral-200 p-6 sm:p-8 shadow-xs space-y-4">
        <div>
          <h1 className="text-xl sm:text-2xl font-black text-neutral-900 tracking-tight">
            Order History & Fulfillment
          </h1>
          <p className="text-xs text-neutral-500 mt-1">
            Track current deliveries, review historical purchases, and request returns or exchanges.
          </p>
        </div>

        {/* Filter Controls & Search */}
        <div className="flex flex-col sm:flex-row items-stretch sm:items-center justify-between gap-4 pt-2">
          <Tabs tabs={filterTabs} activeTab={statusFilter} onChange={setStatusFilter} variant="underline" />

          <div className="relative sm:w-64">
            <Search className="w-4 h-4 text-neutral-400 absolute left-3 top-1/2 -translate-y-1/2" />
            <input
              type="text"
              placeholder="Search by order #..."
              value={searchQuery}
              onChange={(e) => setSearchQuery(e.target.value)}
              className="w-full h-10 pl-9 pr-3 text-xs bg-neutral-50 border border-neutral-200 rounded-xl focus:outline-none focus:ring-1 focus:ring-neutral-900"
            />
          </div>
        </div>
      </div>

      {/* Orders List */}
      {isLoading ? (
        <div className="py-16 text-center bg-white rounded-3xl border border-neutral-200 p-8">
          <p className="text-xs text-neutral-400">Loading your orders...</p>
        </div>
      ) : filteredOrders.length === 0 ? (
        <div className="py-16 text-center bg-white rounded-3xl border border-neutral-200 p-8 space-y-3">
          <Package className="w-10 h-10 text-neutral-300 mx-auto" />
          <h3 className="text-sm font-bold text-neutral-900">No orders found</h3>
          <p className="text-xs text-neutral-500 max-w-sm mx-auto">
            No purchases match the selected filter. Explore our shop to discover new staples.
          </p>
          <Link
            href="/shop"
            className="inline-block mt-2 px-5 py-2.5 bg-neutral-900 text-white text-xs font-bold rounded-full hover:bg-neutral-800 transition-colors"
          >
            Explore Catalog
          </Link>
        </div>
      ) : (
        <div className="space-y-4">
          {filteredOrders.map((order) => (
            <div
              key={order.id}
              className="bg-white rounded-3xl border border-neutral-200 overflow-hidden shadow-xs hover:border-neutral-300 transition-all divide-y divide-neutral-100"
            >
              {/* Card Header Strip */}
              <div className="p-5 bg-neutral-50/70 flex flex-wrap items-center justify-between gap-3 text-xs">
                <div className="flex items-center gap-3">
                  <div>
                    <span className="text-[10px] uppercase font-bold text-neutral-400 tracking-wider">
                      Order Number
                    </span>
                    <p className="font-bold text-neutral-900">#{order.orderNumber}</p>
                  </div>
                  <span className="text-neutral-300">•</span>
                  <div>
                    <span className="text-[10px] uppercase font-bold text-neutral-400 tracking-wider">
                      Date
                    </span>
                    <p className="font-semibold text-neutral-700">{formatDate(order.date)}</p>
                  </div>
                  <span className="text-neutral-300">•</span>
                  <div>
                    <span className="text-[10px] uppercase font-bold text-neutral-400 tracking-wider">
                      Total
                    </span>
                    <p className="font-bold text-neutral-900">{formatPrice(order.total)}</p>
                  </div>
                </div>

                <div className="flex items-center gap-2">
                  <span
                    className={`px-3 py-1 text-[11px] font-bold rounded-full border ${
                      ORDER_STATUS_DETAILS[order.status]?.color || "bg-neutral-100 text-neutral-800"
                    }`}
                  >
                    {ORDER_STATUS_DETAILS[order.status]?.label || order.status}
                  </span>
                </div>
              </div>

              {/* Items Preview */}
              <div className="p-5 sm:p-6 space-y-4">
                <div className="grid grid-cols-1 sm:grid-cols-2 gap-4">
                  {order.items?.map((item) => (
                    <div key={item.id} className="flex items-center gap-3">
                      <div className="relative w-14 h-18 rounded-xl overflow-hidden bg-neutral-100 shrink-0">
                        {item.image ? (
                          <Image src={item.image} alt={item.name} fill className="object-cover" />
                        ) : (
                          <div className="w-full h-full bg-neutral-200 flex items-center justify-center text-xs text-neutral-400">
                            WD
                          </div>
                        )}
                      </div>
                      <div className="min-w-0 flex-1">
                        <h4 className="text-xs font-bold text-neutral-900 truncate">{item.name}</h4>
                        <p className="text-[11px] text-neutral-500">
                          {item.color} • {item.size} • Qty {item.quantity}
                        </p>
                        <p className="text-xs font-semibold text-neutral-900 mt-0.5">
                          {formatPrice(item.price)}
                        </p>
                      </div>
                    </div>
                  ))}
                </div>

                {/* Footer Actions */}
                <div className="pt-4 border-t border-neutral-100 flex flex-wrap items-center justify-between gap-3">
                  <div className="text-[11px] text-neutral-500 flex items-center gap-1.5">
                    <Truck className="w-3.5 h-3.5 text-neutral-700" />
                    <span>
                      Tracking: <strong>{order.trackingNumber || "Pending"}</strong> ({order.carrier || "Standard Ground"})
                    </span>
                  </div>

                  <div className="flex items-center gap-2">
                    {order.status === "delivered" && (
                      <Link
                        href={`/account/returns/new?orderId=${order.id}`}
                        className="px-4 py-2 border border-neutral-200 hover:bg-neutral-50 text-neutral-700 text-xs font-semibold rounded-full transition-colors flex items-center gap-1"
                      >
                        <RotateCcw className="w-3.5 h-3.5" /> Return Items
                      </Link>
                    )}
                    <Link
                      href={`/account/orders/${order.id}`}
                      className="px-4 py-2 border border-neutral-200 hover:bg-neutral-50 text-neutral-800 text-xs font-semibold rounded-full transition-colors"
                    >
                      View Receipt
                    </Link>
                    <Link
                      href={`/account/orders/${order.id}/track`}
                      className="px-4 py-2 bg-neutral-900 hover:bg-neutral-800 text-white text-xs font-bold rounded-full transition-colors flex items-center gap-1"
                    >
                      <span>Track Shipment</span>
                      <ArrowRight className="w-3.5 h-3.5" />
                    </Link>
                  </div>
                </div>
              </div>
            </div>
          ))}
        </div>
      )}
    </div>
  );
}
