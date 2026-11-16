"use client";

import React, { useState } from "react";
import Link from "next/link";
import { Drawer } from "@/components/ui/drawer";
import { useUIStore } from "@/stores/ui-store";
import { useAuthStore } from "@/stores/auth-store";
import { NAVIGATION_LINKS, CATEGORIES, APP_CONFIG } from "@/constants";
import { ChevronDown, ChevronRight, User, Heart, ShoppingBag, ShieldCheck, HelpCircle } from "lucide-react";

export const MobileNavDrawer: React.FC = () => {
  const { isMobileMenuOpen, closeMobileMenu } = useUIStore();
  const { user, isAuthenticated, logout } = useAuthStore();
  const [expandedSection, setExpandedSection] = useState<string | null>("Men");

  const toggleSection = (name: string) => {
    setExpandedSection(expandedSection === name ? null : name);
  };

  return (
    <Drawer
      isOpen={isMobileMenuOpen}
      onClose={closeMobileMenu}
      position="left"
      width="md"
      title={
        <div className="flex items-center gap-2">
          <span className="text-lg font-black tracking-tight text-neutral-900">{APP_CONFIG.name}</span>
        </div>
      }
      footer={
        <div className="space-y-3">
          {isAuthenticated ? (
            <div className="space-y-2">
              <div className="text-xs text-neutral-500">
                Signed in as <strong className="text-neutral-900">{user?.name}</strong>
              </div>
              <div className="grid grid-cols-2 gap-2">
                <Link
                  href="/account"
                  onClick={closeMobileMenu}
                  className="px-3 py-2 bg-neutral-100 hover:bg-neutral-200 text-xs font-semibold rounded-xl text-center text-neutral-900"
                >
                  Dashboard
                </Link>
                <button
                  onClick={() => {
                    logout();
                    closeMobileMenu();
                  }}
                  className="px-3 py-2 border border-neutral-300 hover:bg-neutral-50 text-xs font-medium rounded-xl text-center text-neutral-700"
                >
                  Sign Out
                </button>
              </div>
            </div>
          ) : (
            <div className="grid grid-cols-2 gap-2">
              <Link
                href="/auth/login"
                onClick={closeMobileMenu}
                className="px-4 py-2.5 bg-neutral-900 text-white text-xs font-semibold rounded-full text-center hover:bg-neutral-800"
              >
                Sign In
              </Link>
              <Link
                href="/auth/register"
                onClick={closeMobileMenu}
                className="px-4 py-2.5 border border-neutral-300 text-neutral-800 text-xs font-medium rounded-full text-center hover:bg-neutral-50"
              >
                Create Account
              </Link>
            </div>
          )}

          <div className="text-[11px] text-neutral-400 text-center pt-2 border-t border-neutral-100">
            {APP_CONFIG.supportEmail} • {APP_CONFIG.supportPhone}
          </div>
        </div>
      }
    >
      <div className="space-y-6">
        {/* Navigation Categories with Accordions */}
        <div className="space-y-1">
          {NAVIGATION_LINKS.map((link) => {
            const hasMega = !!link.megaMenu;
            const isExpanded = expandedSection === link.label;

            if (hasMega) {
              return (
                <div key={link.label} className="border-b border-neutral-100 pb-1">
                  <button
                    onClick={() => toggleSection(link.label)}
                    className="w-full flex items-center justify-between py-3 text-sm font-semibold text-neutral-900 text-left"
                  >
                    <span>{link.label}</span>
                    <ChevronDown
                      className={`w-4 h-4 text-neutral-500 transition-transform ${
                        isExpanded ? "rotate-180" : ""
                      }`}
                    />
                  </button>

                  {isExpanded && (
                    <div className="pl-3 pb-3 space-y-4 animate-in slide-in-from-top-2 duration-150">
                      {link.megaMenu?.map((sub) => (
                        <div key={sub.title} className="space-y-1.5">
                          <p className="text-[11px] font-bold uppercase tracking-wider text-neutral-400">
                            {sub.title}
                          </p>
                          <ul className="space-y-1 pl-1">
                            {sub.items.map((item) => (
                              <li key={item.name}>
                                <Link
                                  href={item.href}
                                  onClick={closeMobileMenu}
                                  className="block py-1 text-xs text-neutral-700 hover:text-neutral-950 font-medium"
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
            }

            return (
              <div key={link.label} className="border-b border-neutral-100">
                <Link
                  href={link.href}
                  onClick={closeMobileMenu}
                  className="flex items-center justify-between py-3 text-sm font-semibold text-neutral-900"
                >
                  <span>{link.label}</span>
                  <ChevronRight className="w-4 h-4 text-neutral-400" />
                </Link>
              </div>
            );
          })}
        </div>

        {/* Quick Help & Policies */}
        <div className="space-y-2 pt-2">
          <p className="text-[11px] font-bold uppercase tracking-wider text-neutral-400">
            Customer Care
          </p>
          <div className="space-y-1 text-xs text-neutral-700">
            <Link
              href="/order-tracking"
              onClick={closeMobileMenu}
              className="flex items-center gap-2 py-1.5 hover:text-neutral-950"
            >
              <ShoppingBag className="w-4 h-4 text-neutral-400" /> Track An Order
            </Link>
            <Link
              href="/account/returns"
              onClick={closeMobileMenu}
              className="flex items-center gap-2 py-1.5 hover:text-neutral-950"
            >
              <ShieldCheck className="w-4 h-4 text-neutral-400" /> Easy Returns & Exchanges
            </Link>
            <Link
              href="/faq"
              onClick={closeMobileMenu}
              className="flex items-center gap-2 py-1.5 hover:text-neutral-950"
            >
              <HelpCircle className="w-4 h-4 text-neutral-400" /> Help Center & FAQ
            </Link>
          </div>
        </div>
      </div>
    </Drawer>
  );
};

