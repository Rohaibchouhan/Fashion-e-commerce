"use client";

import React, { useEffect, useState } from "react";
import Link from "next/link";
import Image from "next/image";
import { useAuthStore } from "@/stores/auth-store";
import { useWishlistStore } from "@/stores/wishlist-store";
import { OrdersService } from "@/services/orders.service";
import { ReturnsService } from "@/services/returns.service";
import { Order } from "@/types/order";
import { ReturnRequest } from "@/types/return";
import { formatPrice, formatDate } from "@/lib/utils";
import { ORDER_STATUS_DETAILS } from "@/constants";
import {
  Package,
  Truck,
  RotateCcw,
  Heart,
  ArrowRight,
  Clock,
  Sparkles,
  ShieldCheck,
} from "lucide-react";

export default function AccountOverviewPage() {
  const user = useAuthStore((s) => s.user);
  const wishlistItems = useWishlistStore((s) => s.items);

  const [orders, setOrders] = useState<Order[]>([]);
  const [returns, setReturns] = useState<ReturnRequest[]>([]);
  const [isLoading, setIsLoading] = useState(true);

  useEffect(() => {
    let isMounted = true;
    async function loadData() {
      try {
        const [ordersData, returnsData] = await Promise.all([
          OrdersService.getAll(),
          ReturnsService.getAll(),
        ]);
        if (isMounted) {
          setOrders(ordersData);
          setReturns(returnsData);
        }
      } catch (err) {
        console.error("Failed to load customer account dashboard data:", err);
      } finally {
        if (isMounted) setIsLoading(false);
      }
    }
    loadData();
    return () => {
      isMounted = false;
    };
  }, []);

  const activeOrders = orders.filter(
    (o) => o.status !== "delivered" && o.status !== "cancelled"
  );
  const latestActiveOrder = activeOrders[0] || orders[0];

  return (
    <div className="space-y-8">
      {/* Welcome Banner */}
      <div className="bg-white rounded-3xl border border-neutral-200 p-6 sm:p-8 shadow-xs flex flex-col sm:flex-row sm:items-center justify-between gap-4">
        <div>
          <span className="text-[10px] font-bold uppercase tracking-wider text-neutral-400">
            Customer Dashboard
          </span>
          <h1 className="text-2xl sm:text-3xl font-black text-neutral-900 tracking-tight mt-0.5">
            Welcome back, {user?.name?.split(" ")[0] || "Valued Shopper"}
          </h1>
          <p className="text-xs text-neutral-500 mt-1">
            Manage your recent orders, returns, and delivery preferences in real time.
          </p>
        </div>
        <Link
          href="/shop"
          className="px-5 py-2.5 bg-neutral-900 text-white text-xs font-bold rounded-full hover:bg-neutral-800 transition-colors self-start sm:self-auto shadow-xs"
        >
          Explore Collection
        </Link>
      </div>

      {/* Statistics Cards */}
      <div className="grid grid-cols-2 sm:grid-cols-4 gap-4">
        <Link
          href="/account/orders"
          className="bg-white p-5 rounded-2xl border border-neutral-200/80 shadow-xs hover:border-neutral-900 transition-all group"
        >
          <div className="flex items-center justify-between">
            <span className="text-[11px] font-bold text-neutral-400 uppercase tracking-wider">
              Total Orders
            </span>
            <Package className="w-4 h-4 text-neutral-400 group-hover:text-neutral-900" />
          </div>
          <div className="text-2xl font-black text-neutral-900 mt-2">
            {isLoading ? "—" : orders.length}
          </div>
          <span className="text-[10px] text-neutral-500">All-time purchases</span>
        </Link>

        <Link
          href="/account/orders"
          className="bg-white p-5 rounded-2xl border border-neutral-200/80 shadow-xs hover:border-neutral-900 transition-all group"
        >
          <div className="flex items-center justify-between">
            <span className="text-[11px] font-bold text-neutral-400 uppercase tracking-wider">
              Active Shipments
            </span>
            <Truck className="w-4 h-4 text-blue-600 group-hover:text-neutral-900" />
          </div>
          <div className="text-2xl font-black text-neutral-900 mt-2">
            {isLoading ? "—" : activeOrders.length}
          </div>
          <span className="text-[10px] text-blue-600 font-medium">In transit / processing</span>
        </Link>

        <Link
          href="/account/returns"
          className="bg-white p-5 rounded-2xl border border-neutral-200/80 shadow-xs hover:border-neutral-900 transition-all group"
        >
          <div className="flex items-center justify-between">
            <span className="text-[11px] font-bold text-neutral-400 uppercase tracking-wider">
              Returns
            </span>
            <RotateCcw className="w-4 h-4 text-purple-600 group-hover:text-neutral-900" />
          </div>
          <div className="text-2xl font-black text-neutral-900 mt-2">
            {isLoading ? "—" : returns.length}
          </div>
          <span className="text-[10px] text-purple-600 font-medium">Active RMA requests</span>
        </Link>

        <Link
          href="/account/wishlist"
          className="bg-white p-5 rounded-2xl border border-neutral-200/80 shadow-xs hover:border-neutral-900 transition-all group"
        >
          <div className="flex items-center justify-between">
            <span className="text-[11px] font-bold text-neutral-400 uppercase tracking-wider">
              Wishlist
            </span>
            <Heart className="w-4 h-4 text-rose-500 group-hover:text-neutral-900" />
          </div>
          <div className="text-2xl font-black text-neutral-900 mt-2">
            {wishlistItems.length}
          </div>
          <span className="text-[10px] text-neutral-500">Saved essentials</span>
        </Link>
      </div>

      {/* Live Active Order Progress Banner */}
      {latestActiveOrder && (
        <div className="bg-white rounded-3xl border border-neutral-200 p-6 sm:p-7 shadow-xs space-y-4">
          <div className="flex flex-wrap items-center justify-between gap-3">
            <div className="flex items-center gap-3">
              <span className="p-2.5 rounded-xl bg-blue-50 text-blue-700">
                <Truck className="w-5 h-5" />
              </span>
              <div>
                <div className="flex items-center gap-2">
                  <h3 className="text-sm font-bold text-neutral-900">
                    Order #{latestActiveOrder.orderNumber}
                  </h3>
                  <span
                    className={`px-2.5 py-0.5 text-[10px] font-bold rounded-full border ${
                      ORDER_STATUS_DETAILS[latestActiveOrder.status]?.color || "bg-neutral-100 text-neutral-800"
                    }`}
                  >
                    {ORDER_STATUS_DETAILS[latestActiveOrder.status]?.label || latestActiveOrder.status}
                  </span>
                </div>
                <p className="text-xs text-neutral-500 mt-0.5">
                  Carrier: {latestActiveOrder.carrier} • Estimated Delivery:{" "}
                  <strong>{latestActiveOrder.estimatedDelivery || "Pending"}</strong>
                </p>
              </div>
            </div>

            <Link
              href={`/account/orders/${latestActiveOrder.id}/track`}
              className="px-5 py-2 bg-neutral-900 hover:bg-neutral-800 text-white rounded-full text-xs font-bold transition-colors flex items-center gap-1.5 shadow-xs"
            >
              <span>Live Visual Tracking</span>
              <ArrowRight className="w-3.5 h-3.5" />
            </Link>
          </div>

          {/* Items Row inside Active Banner */}
          {latestActiveOrder.items && latestActiveOrder.items.length > 0 && (
            <div className="flex items-center gap-3 pt-3 border-t border-neutral-100 overflow-x-auto no-scrollbar">
              {latestActiveOrder.items.map((item) => (
                <div key={item.id} className="flex items-center gap-3 p-2 rounded-xl bg-neutral-50 shrink-0">
                  <div className="relative w-10 h-12 rounded-lg overflow-hidden bg-white shrink-0">
                    {item.image ? (
                      <Image src={item.image} alt={item.name} fill className="object-cover" />
                    ) : (
                      <div className="w-full h-full bg-neutral-200 flex items-center justify-center text-[10px] text-neutral-400">
                        WD
                      </div>
                    )}
                  </div>
                  <div className="text-xs pr-2">
                    <p className="font-bold text-neutral-900 truncate max-w-[140px]">{item.name}</p>
                    <p className="text-[10px] text-neutral-500">{item.color} • {item.size}</p>
                  </div>
                </div>
              ))}
            </div>
          )}
        </div>
      )}

      {/* Recent Purchases List */}
      <div className="bg-white rounded-3xl border border-neutral-200 p-6 sm:p-7 shadow-xs space-y-4">
        <div className="flex items-center justify-between pb-3 border-b border-neutral-100">
          <h3 className="text-sm font-bold text-neutral-900">Recent Purchases</h3>
          <Link
            href="/account/orders"
            className="text-xs font-semibold text-neutral-600 hover:text-neutral-950 flex items-center gap-1"
          >
            <span>View All Orders</span>
            <ArrowRight className="w-3.5 h-3.5" />
          </Link>
        </div>

        {isLoading ? (
          <div className="py-8 text-center text-xs text-neutral-400">Loading order history...</div>
        ) : orders.length === 0 ? (
          <div className="py-8 text-center space-y-2">
            <p className="text-xs text-neutral-500">You haven't placed any orders yet.</p>
            <Link
              href="/shop"
              className="inline-block text-xs font-bold text-neutral-900 hover:underline"
            >
              Start shopping &rarr;
            </Link>
          </div>
        ) : (
          <div className="divide-y divide-neutral-100">
            {orders.slice(0, 3).map((order) => (
              <div key={order.id} className="py-4 flex flex-col sm:flex-row sm:items-center justify-between gap-4">
                <div className="space-y-1">
                  <div className="flex items-center gap-2">
                    <span className="text-xs font-bold text-neutral-900">
                      Order #{order.orderNumber}
                    </span>
                    <span
                      className={`px-2 py-0.5 text-[10px] font-bold rounded-full ${
                        ORDER_STATUS_DETAILS[order.status]?.color || "bg-neutral-100 text-neutral-800"
                      }`}
                    >
                      {ORDER_STATUS_DETAILS[order.status]?.label || order.status}
                    </span>
                  </div>
                  <p className="text-xs text-neutral-500">
                    Placed on {formatDate(order.date)} • {order.items?.length || 0} items • {formatPrice(order.total)}
                  </p>
                </div>

                <div className="flex items-center gap-2">
                  <Link
                    href={`/account/orders/${order.id}`}
                    className="px-4 py-2 border border-neutral-200 hover:bg-neutral-50 text-neutral-800 text-xs font-semibold rounded-full transition-colors"
                  >
                    Order Details
                  </Link>
                  <Link
                    href={`/account/orders/${order.id}/track`}
                    className="px-4 py-2 bg-neutral-900 hover:bg-neutral-800 text-white text-xs font-semibold rounded-full transition-colors"
                  >
                    Track Package
                  </Link>
                </div>
              </div>
            ))}
          </div>
        )}
      </div>
    </div>
  );
}
