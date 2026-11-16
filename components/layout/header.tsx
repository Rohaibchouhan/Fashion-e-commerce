"use client";

import React, { useState } from "react";
import Link from "next/link";
import { usePathname } from "next/navigation";
import { Menu, Search, Heart, ShoppingBag, User, ChevronDown, Sparkles, LogOut, Package, RotateCcw } from "lucide-react";
import { useCartStore } from "@/stores/cart-store";
import { useWishlistStore } from "@/stores/wishlist-store";
import { useUIStore } from "@/stores/ui-store";
import { useAuthStore } from "@/stores/auth-store";
import { NAVIGATION_LINKS, APP_CONFIG } from "@/constants";
import { NotificationDropdown } from "@/components/notifications/notification-dropdown";
import { cn } from "@/lib/utils";

export const Header: React.FC = () => {
  const pathname = usePathname();
  const { toggleCart, getItemCount } = useCartStore();
  const wishlistItems = useWishlistStore((s) => s.items);
  const { openSearch, openMobileMenu } = useUIStore();
  const { user, isAuthenticated, logout } = useAuthStore();
  const [activeMegaMenu, setActiveMegaMenu] = useState<string | null>(null);
  const [userDropdownOpen, setUserDropdownOpen] = useState(false);

  const cartCount = getItemCount();

  return (
    <header className="sticky top-0 z-40 bg-white/95 backdrop-blur-md border-b border-neutral-200/80 transition-shadow">
      <div className="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8">
        <div className="flex items-center justify-between h-18 sm:h-20 gap-4">
          {/* Left: Mobile Menu Toggle & Brand Logo */}
          <div className="flex items-center gap-3 sm:gap-6">
            <button
              onClick={openMobileMenu}
              className="p-2 -ml-2 rounded-full text-neutral-800 hover:bg-neutral-100 transition-colors lg:hidden"
              aria-label="Open mobile menu"
            >
              <Menu className="w-5 h-5" />
            </button>

            <Link href="/" className="flex items-center gap-2 group">
              <span className="text-2xl font-black tracking-tight text-neutral-900 group-hover:opacity-90 transition-opacity">
                {APP_CONFIG.name}
              </span>
            </Link>
          </div>

          {/* Center (Desktop): Modern Search Bar like in reference photo */}
          <div className="hidden md:flex flex-1 max-w-md mx-6">
            <button
              onClick={openSearch}
              className="w-full flex items-center justify-between px-4 py-2 text-xs text-neutral-400 bg-neutral-100/80 hover:bg-neutral-100 rounded-full border border-neutral-200/60 transition-all cursor-pointer group"
            >
              <span className="flex items-center gap-2 text-neutral-500">
                <Search className="w-4 h-4 text-neutral-400 group-hover:text-neutral-700 transition-colors" />
                <span>Search everyday essentials...</span>
              </span>
              <kbd className="hidden lg:inline-block px-2 py-0.5 text-[10px] bg-white border border-neutral-200 rounded text-neutral-400 font-mono">
                ⌘K
              </kbd>
            </button>
          </div>

          {/* Desktop Navigation Links */}
          <nav className="hidden lg:flex items-center space-x-7">
            {NAVIGATION_LINKS.slice(0, 5).map((link) => {
              const hasMega = !!link.megaMenu;
              const isActive = pathname === link.href;

              return (
                <div
                  key={link.label}
                  className="relative"
                  onMouseEnter={() => hasMega && setActiveMegaMenu(link.label)}
                  onMouseLeave={() => setActiveMegaMenu(null)}
                >
                  <Link
                    href={link.href}
                    className={cn(
                      "text-sm font-semibold transition-colors py-2 flex items-center gap-1",
                      isActive ? "text-neutral-950 font-bold" : "text-neutral-600 hover:text-neutral-950"
                    )}
                  >
                    <span>{link.label}</span>
                    {hasMega && <ChevronDown className="w-3.5 h-3.5 text-neutral-400" />}
                  </Link>

                  {/* Mega Menu Dropdown */}
                  {hasMega && activeMegaMenu === link.label && (
                    <div className="absolute top-full -left-12 w-[600px] bg-white rounded-3xl shadow-2xl border border-neutral-100 p-6 z-50 animate-in fade-in zoom-in-95 duration-150 grid grid-cols-3 gap-6">
                      {link.megaMenu?.map((section) => (
                        <div key={section.title} className="space-y-3">
                          <h4 className="text-xs font-bold uppercase tracking-wider text-neutral-900 border-b border-neutral-100 pb-1.5">
                            {section.title}
                          </h4>
                          <ul className="space-y-2">
                            {section.items.map((item) => (
                              <li key={item.name}>
                                <Link
                                  href={item.href}
                                  onClick={() => setActiveMegaMenu(null)}
                                  className="text-xs text-neutral-600 hover:text-neutral-950 hover:font-medium transition-colors block"
                                >
                                  {item.name}
                                </Link>
                              </li>
                            ))}
                          </ul>
                        </div>
                      ))}
                    </div>
                  )}
                </div>
              );
            })}
          </nav>

          {/* Right Actions: Search (mobile), Notifications, Wishlist, User, Cart */}
          <div className="flex items-center gap-1 sm:gap-2">
            {/* Search icon button for mobile */}
            <button
              onClick={openSearch}
              className="p-2 rounded-full text-neutral-700 hover:text-neutral-950 hover:bg-neutral-100 transition-colors md:hidden"
              aria-label="Search"
            >
              <Search className="w-5 h-5" />
            </button>

            {/* Notifications with Real-time Dropdown */}
            <NotificationDropdown />

            {/* Wishlist Link */}
            <Link
              href="/account/wishlist"
              className="relative p-2 rounded-full text-neutral-700 hover:text-neutral-950 hover:bg-neutral-100 transition-colors"
              aria-label="Wishlist"
            >
              <Heart className="w-5 h-5" />
              {wishlistItems.length > 0 && (
                <span className="absolute top-1 right-1 flex h-4 w-4 items-center justify-center rounded-full bg-neutral-900 text-[10px] font-bold text-white">
                  {wishlistItems.length}
                </span>
              )}
            </Link>

            {/* Account Dropdown */}
            <div className="relative">
              <button
                onClick={() => setUserDropdownOpen(!userDropdownOpen)}
                className="p-2 rounded-full text-neutral-700 hover:text-neutral-950 hover:bg-neutral-100 transition-colors"
                aria-label="Account"
              >
                <User className="w-5 h-5" />
              </button>

              {userDropdownOpen && (
                <div
                  onMouseLeave={() => setUserDropdownOpen(false)}
                  className="absolute right-0 mt-2 w-56 rounded-2xl bg-white shadow-xl border border-neutral-100 p-2 z-50 animate-in fade-in zoom-in-95 duration-100"
                >
                  {isAuthenticated ? (
                    <div className="space-y-1">
                      <div className="px-3 py-2 border-b border-neutral-100">
                        <p className="text-xs font-bold text-neutral-900">{user?.name}</p>
                        <p className="text-[11px] text-neutral-500 truncate">{user?.email}</p>
                      </div>
                      <Link
                        href="/account"
                        onClick={() => setUserDropdownOpen(false)}
                        className="flex items-center gap-2 px-3 py-2 text-xs font-medium text-neutral-700 hover:bg-neutral-50 rounded-xl"
                      >
                        <User className="w-4 h-4 text-neutral-500" /> Account Overview
                      </Link>
                      <Link
                        href="/account/orders"
                        onClick={() => setUserDropdownOpen(false)}
                        className="flex items-center gap-2 px-3 py-2 text-xs font-medium text-neutral-700 hover:bg-neutral-50 rounded-xl"
                      >
                        <Package className="w-4 h-4 text-neutral-500" /> My Orders
                      </Link>
                      <Link
                        href="/account/returns"
                        onClick={() => setUserDropdownOpen(false)}
                        className="flex items-center gap-2 px-3 py-2 text-xs font-medium text-neutral-700 hover:bg-neutral-50 rounded-xl"
                      >
                        <RotateCcw className="w-4 h-4 text-neutral-500" /> Returns & Exchanges
                      </Link>
                      <button
                        onClick={() => {
                          logout();
                          setUserDropdownOpen(false);
                        }}
                        className="w-full flex items-center gap-2 px-3 py-2 text-xs font-medium text-rose-600 hover:bg-rose-50 rounded-xl transition-colors cursor-pointer"
                      >
                        <LogOut className="w-4 h-4 text-rose-600" /> Sign Out
                      </button>
                    </div>
                  ) : (
                    <div className="p-2 space-y-2">
                      <Link
                        href="/auth/login"
                        onClick={() => setUserDropdownOpen(false)}
                        className="w-full block py-2 px-3 bg-neutral-900 text-white text-xs font-semibold rounded-xl text-center hover:bg-neutral-800"
                      >
                        Sign In
                      </Link>
                      <Link
                        href="/auth/register"
                        onClick={() => setUserDropdownOpen(false)}
                        className="w-full block py-2 px-3 border border-neutral-200 text-neutral-800 text-xs font-medium rounded-xl text-center hover:bg-neutral-50"
                      >
                        Create Account
                      </Link>
                    </div>
                  )}
                </div>
              )}
            </div>

            {/* Shopping Bag / Mini-Cart Trigger with Badge */}
            <button
              onClick={toggleCart}
              className="relative p-2 rounded-full text-neutral-900 hover:bg-neutral-100 transition-colors"
              aria-label="Shopping Cart"
            >
              <ShoppingBag className="w-5 h-5" />
              {cartCount > 0 && (
                <span className="absolute top-1 right-1 flex h-4 w-4 items-center justify-center rounded-full bg-neutral-900 text-[10px] font-bold text-white shadow-xs">
                  {cartCount}
                </span>
              )}
            </button>
          </div>
        </div>
      </div>
    </header>
  );
};

