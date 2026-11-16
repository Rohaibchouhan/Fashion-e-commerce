"use client";

import React from "react";
import Link from "next/link";
import { usePathname } from "next/navigation";
import {
  LayoutDashboard,
  Package,
  RotateCcw,
  Heart,
  MapPin,
  Bell,
  User,
  Shield,
  LogOut,
  ChevronRight,
} from "lucide-react";
import { useAuthStore } from "@/stores/auth-store";
import { useNotificationStore } from "@/stores/notification-store";
import { useWishlistStore } from "@/stores/wishlist-store";
import { cn } from "@/lib/utils";

export default function AccountLayout({ children }: { children: React.ReactNode }) {
  const pathname = usePathname();
  const { user, isAuthenticated, logout } = useAuthStore();
  const unreadCount = useNotificationStore((s) => s.getUnreadCount());
  const wishlistCount = useWishlistStore((s) => s.items.length);

  const menuItems = [
    { label: "Overview", href: "/account", icon: LayoutDashboard },
    { label: "My Orders", href: "/account/orders", icon: Package },
    { label: "Returns & Exchanges", href: "/account/returns", icon: RotateCcw },
    {
      label: "Saved Wishlist",
      href: "/account/wishlist",
      icon: Heart,
      badge: wishlistCount > 0 ? wishlistCount : undefined,
    },
    { label: "Shipping Addresses", href: "/account/addresses", icon: MapPin },
    {
      label: "Notifications",
      href: "/account/notifications",
      icon: Bell,
      badge: unreadCount > 0 ? unreadCount : undefined,
    },
    { label: "Profile Details", href: "/account/profile", icon: User },
    { label: "Security & Login", href: "/account/security", icon: Shield },
  ];

  return (
    <div className="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8 py-8 sm:py-12">
      <div className="grid grid-cols-1 lg:grid-cols-12 gap-8 items-start">
        {/* Account Sidebar Navigation */}
        <aside className="lg:col-span-3 bg-white rounded-3xl border border-neutral-200 p-6 shadow-xs space-y-6">
          {/* User Profile Mini Header */}
          <div className="flex items-center gap-3 pb-6 border-b border-neutral-100">
            <div className="w-12 h-12 rounded-full bg-neutral-900 text-white font-black text-sm flex items-center justify-center shrink-0">
              {user?.name
                ?.split(" ")
                .map((n) => n[0])
                .join("") || "AM"}
            </div>
            <div className="min-w-0 flex-1">
              <h3 className="text-xs font-bold text-neutral-900 truncate">{user?.name}</h3>
              <p className="text-[11px] text-neutral-400 truncate">{user?.email}</p>
            </div>
          </div>

          {/* Navigation Links */}
          <nav className="space-y-1">
            {menuItems.map((item) => {
              const Icon = item.icon;
              const isActive =
                item.href === "/account"
                  ? pathname === "/account"
                  : pathname.startsWith(item.href);

              return (
                <Link
                  key={item.label}
                  href={item.href}
                  className={cn(
                    "flex items-center justify-between px-3.5 py-2.5 rounded-xl text-xs font-medium transition-colors",
                    isActive
                      ? "bg-neutral-900 text-white font-bold"
                      : "text-neutral-600 hover:text-neutral-950 hover:bg-neutral-50"
                  )}
                >
                  <div className="flex items-center gap-2.5">
                    <Icon className="w-4 h-4 shrink-0" />
                    <span>{item.label}</span>
                  </div>
                  {item.badge !== undefined && (
                    <span
                      className={cn(
                        "px-2 py-0.5 text-[10px] font-bold rounded-full",
                        isActive ? "bg-white text-neutral-900" : "bg-neutral-100 text-neutral-800"
                      )}
                    >
                      {item.badge}
                    </span>
                  )}
                </Link>
              );
            })}
          </nav>

          <div className="pt-4 border-t border-neutral-100">
            <button
              onClick={() => logout()}
              className="w-full flex items-center gap-2 px-3.5 py-2 text-xs font-semibold text-rose-600 hover:bg-rose-50 rounded-xl transition-colors cursor-pointer"
            >
              <LogOut className="w-4 h-4" />
              <span>Sign Out</span>
            </button>
          </div>
        </aside>

        {/* Account Page View Content */}
        <div className="lg:col-span-9">{children}</div>
      </div>
    </div>
  );
}

