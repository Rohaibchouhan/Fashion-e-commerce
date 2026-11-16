"use client";

import React, { useRef, useEffect } from "react";
import Link from "next/link";
import { Bell, CheckCheck, Package, Tag, Shield, ExternalLink } from "lucide-react";
import { useNotificationStore } from "@/stores/notification-store";
import { formatDate } from "@/lib/utils";

export const NotificationDropdown: React.FC = () => {
  const { notifications, isOpen, toggleDropdown, closeDropdown, markAsRead, markAllAsRead, getUnreadCount } =
    useNotificationStore();
  const dropdownRef = useRef<HTMLDivElement>(null);
  const unreadCount = getUnreadCount();

  useEffect(() => {
    function handleClickOutside(event: MouseEvent) {
      if (dropdownRef.current && !dropdownRef.current.contains(event.target as Node)) {
        closeDropdown();
      }
    }
    if (isOpen) {
      document.addEventListener("mousedown", handleClickOutside);
    }
    return () => {
      document.removeEventListener("mousedown", handleClickOutside);
    };
  }, [isOpen, closeDropdown]);

  const getIcon = (type: string) => {
    switch (type) {
      case "order_update":
        return <Package className="w-4 h-4 text-blue-600" />;
      case "price_drop":
      case "promo":
        return <Tag className="w-4 h-4 text-amber-600" />;
      case "security":
        return <Shield className="w-4 h-4 text-rose-600" />;
      default:
        return <Bell className="w-4 h-4 text-neutral-600" />;
    }
  };

  return (
    <div className="relative" ref={dropdownRef}>
      <button
        onClick={toggleDropdown}
        className="relative p-2 rounded-full text-neutral-700 hover:text-neutral-950 hover:bg-neutral-100 transition-colors"
        aria-label="Notifications"
      >
        <Bell className="w-5 h-5" />
        {unreadCount > 0 && (
          <span className="absolute top-1 right-1 flex h-4 w-4 items-center justify-center rounded-full bg-neutral-900 text-[10px] font-bold text-white">
            {unreadCount > 9 ? "9+" : unreadCount}
          </span>
        )}
      </button>

      {isOpen && (
        <div className="absolute right-0 mt-2 w-80 sm:w-96 rounded-2xl bg-white shadow-2xl border border-neutral-100 p-4 z-50 animate-in fade-in zoom-in-95 duration-150">
          <div className="flex items-center justify-between pb-3 border-b border-neutral-100">
            <div className="flex items-center gap-2">
              <h3 className="text-sm font-bold text-neutral-900">Notifications</h3>
              {unreadCount > 0 && (
                <span className="px-2 py-0.5 text-[10px] font-semibold bg-neutral-100 text-neutral-800 rounded-full">
                  {unreadCount} unread
                </span>
              )}
            </div>
            {unreadCount > 0 && (
              <button
                onClick={markAllAsRead}
                className="text-xs text-neutral-500 hover:text-neutral-900 flex items-center gap-1 font-medium transition-colors"
              >
                <CheckCheck className="w-3.5 h-3.5" /> Mark all read
              </button>
            )}
          </div>

          <div className="divide-y divide-neutral-100 max-h-80 overflow-y-auto my-2">
            {notifications.length === 0 ? (
              <div className="py-8 text-center text-xs text-neutral-500">
                You have no notifications right now.
              </div>
            ) : (
              notifications.slice(0, 5).map((n) => (
                <div
                  key={n.id}
                  onClick={() => markAsRead(n.id)}
                  className={`py-3 px-2 flex items-start gap-3 rounded-xl transition-colors cursor-pointer hover:bg-neutral-50 ${
                    !n.read ? "bg-neutral-50/70" : ""
                  }`}
                >
                  <div className="p-2 rounded-xl bg-white shadow-xs border border-neutral-100 shrink-0 mt-0.5">
                    {getIcon(n.type)}
                  </div>
                  <div className="flex-1 min-w-0">
                    <div className="flex items-center justify-between gap-1">
                      <p className={`text-xs font-semibold ${!n.read ? "text-neutral-950 font-bold" : "text-neutral-700"}`}>
                        {n.title}
                      </p>
                      <span className="text-[10px] text-neutral-400 whitespace-nowrap">
                        {formatDate(n.timestamp)}
                      </span>
                    </div>
                    <p className="text-xs text-neutral-500 line-clamp-2 mt-0.5 leading-relaxed">
                      {n.message}
                    </p>
                    {n.link && (
                      <Link
                        href={n.link}
                        onClick={closeDropdown}
                        className="inline-flex items-center gap-1 text-[11px] font-semibold text-neutral-900 hover:underline mt-1.5"
                      >
                        View Details <ExternalLink className="w-3 h-3" />
                      </Link>
                    )}
                  </div>
                  {!n.read && (
                    <span className="w-2 h-2 rounded-full bg-neutral-900 shrink-0 mt-1.5" />
                  )}
                </div>
              ))
            )}
          </div>

          <div className="pt-2 border-t border-neutral-100 text-center">
            <Link
              href="/account/notifications"
              onClick={closeDropdown}
              className="text-xs font-semibold text-neutral-900 hover:underline block py-1"
            >
              View All Notifications ({notifications.length})
            </Link>
          </div>
        </div>
      )}
    </div>
  );
};

