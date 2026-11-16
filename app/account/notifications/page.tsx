"use client";

import React, { useState } from "react";
import Link from "next/link";
import { useNotificationStore } from "@/stores/notification-store";
import { formatDate } from "@/lib/utils";
import {
  Bell,
  CheckCheck,
  Package,
  Tag,
  Shield,
  ExternalLink,
  Sliders,
  Check,
} from "lucide-react";

export default function AccountNotificationsPage() {
  const { notifications, markAsRead, markAllAsRead, getUnreadCount } = useNotificationStore();
  const [filter, setFilter] = useState<"all" | "unread" | "order" | "promo">("all");
  const unreadCount = getUnreadCount();

  const filteredNotifications = notifications.filter((n) => {
    if (filter === "unread") return !n.read;
    if (filter === "order") return n.type === "order_update";
    if (filter === "promo") return n.type === "promo" || n.type === "price_drop";
    return true;
  });

  const getIcon = (type: string) => {
    switch (type) {
      case "order_update":
        return <Package className="w-5 h-5 text-blue-600" />;
      case "price_drop":
      case "promo":
        return <Tag className="w-5 h-5 text-amber-600" />;
      case "security":
        return <Shield className="w-5 h-5 text-rose-600" />;
      default:
        return <Bell className="w-5 h-5 text-neutral-600" />;
    }
  };

  return (
    <div className="space-y-6">
      <div className="bg-white rounded-3xl border border-neutral-200 p-6 sm:p-8 shadow-xs flex flex-col sm:flex-row sm:items-center justify-between gap-4">
        <div>
          <div className="flex items-center gap-2.5">
            <h1 className="text-xl sm:text-2xl font-black text-neutral-900 tracking-tight">
              Activity & Notifications
            </h1>
            {unreadCount > 0 && (
              <span className="px-2.5 py-0.5 text-xs font-bold bg-neutral-900 text-white rounded-full">
                {unreadCount} unread
              </span>
            )}
          </div>
          <p className="text-xs text-neutral-500 mt-1">
            Real-time fulfillment events, price drop alerts, and account activity.
          </p>
        </div>

        {unreadCount > 0 && (
          <button
            onClick={markAllAsRead}
            className="px-4 py-2 bg-neutral-100 hover:bg-neutral-200 text-neutral-800 rounded-full text-xs font-semibold transition-colors flex items-center gap-1.5 self-start sm:self-auto cursor-pointer"
          >
            <CheckCheck className="w-4 h-4" /> Mark All as Read
          </button>
        )}
      </div>

      {/* Filter Tabs */}
      <div className="flex gap-2">
        {[
          { id: "all", label: "All Activity" },
          { id: "unread", label: "Unread Only" },
          { id: "order", label: "Shipments & Orders" },
          { id: "promo", label: "Offers & Price Alerts" },
        ].map((tab) => (
          <button
            key={tab.id}
            onClick={() => setFilter(tab.id as any)}
            className={`px-4 py-2 text-xs font-bold rounded-full border transition-all cursor-pointer ${
              filter === tab.id
                ? "bg-neutral-900 text-white border-neutral-900"
                : "bg-white text-neutral-700 border-neutral-200 hover:border-neutral-300"
            }`}
          >
            {tab.label}
          </button>
        ))}
      </div>

      {/* Notifications History List */}
      <div className="bg-white rounded-3xl border border-neutral-200 divide-y divide-neutral-100 overflow-hidden shadow-xs">
        {filteredNotifications.length === 0 ? (
          <div className="py-16 text-center text-xs text-neutral-500">
            No notifications match your current filter.
          </div>
        ) : (
          filteredNotifications.map((notif) => (
            <div
              key={notif.id}
              onClick={() => markAsRead(notif.id)}
              className={`p-5 flex items-start gap-4 transition-colors cursor-pointer hover:bg-neutral-50/80 ${
                !notif.read ? "bg-neutral-50/50" : ""
              }`}
            >
              <div className="p-3 rounded-2xl bg-white shadow-xs border border-neutral-200 shrink-0">
                {getIcon(notif.type)}
              </div>

              <div className="flex-1 min-w-0">
                <div className="flex items-center justify-between gap-2">
                  <h4
                    className={`text-xs ${
                      !notif.read ? "font-black text-neutral-950 text-sm" : "font-semibold text-neutral-800"
                    }`}
                  >
                    {notif.title}
                  </h4>
                  <span className="text-[10px] text-neutral-400 font-medium">
                    {formatDate(notif.timestamp)}
                  </span>
                </div>

                <p className="text-xs text-neutral-600 mt-1 leading-relaxed">{notif.message}</p>

                {notif.link && (
                  <Link
                    href={notif.link}
                    className="inline-flex items-center gap-1 text-xs font-bold text-neutral-900 hover:underline mt-2"
                  >
                    <span>View Related Details</span>
                    <ExternalLink className="w-3.5 h-3.5" />
                  </Link>
                )}
              </div>

              {!notif.read && (
                <span className="w-2.5 h-2.5 rounded-full bg-neutral-900 shrink-0 mt-2" />
              )}
            </div>
          ))
        )}
      </div>
    </div>
  );
}

