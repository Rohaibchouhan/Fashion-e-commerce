"use client";

import React, { useState } from "react";
import Link from "next/link";
import { ArrowRight, Check, ShieldCheck, Truck, RefreshCw, Lock } from "lucide-react";
import { APP_CONFIG } from "@/constants";

export const Footer: React.FC = () => {
  const [email, setEmail] = useState("");
  const [subscribed, setSubscribed] = useState(false);

  const handleSubscribe = (e: React.FormEvent) => {
    e.preventDefault();
    if (!email.trim()) return;
    setSubscribed(true);
    setEmail("");
  };

  return (
    <footer className="bg-white border-t border-neutral-200 mt-20 pb-16 sm:pb-0">
      {/* Trust Badges Strip (matching reference image) */}
      <div className="border-b border-neutral-200/80 bg-neutral-50/50">
        <div className="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8 py-8">
          <div className="grid grid-cols-2 md:grid-cols-4 gap-6 text-center sm:text-left">
            <div className="flex flex-col sm:flex-row items-center gap-3">
              <div className="w-10 h-10 rounded-full bg-white shadow-xs border border-neutral-200 flex items-center justify-center text-neutral-800 shrink-0">
                <ShieldCheck className="w-5 h-5" />
              </div>
              <div>
                <h4 className="text-xs font-bold text-neutral-900">Great Quality</h4>
                <p className="text-[11px] text-neutral-500 mt-0.5">Loved by thousands of daily wearers</p>
              </div>
            </div>

            <div className="flex flex-col sm:flex-row items-center gap-3">
              <div className="w-10 h-10 rounded-full bg-white shadow-xs border border-neutral-200 flex items-center justify-center text-neutral-800 shrink-0">
                <Truck className="w-5 h-5" />
              </div>
              <div>
                <h4 className="text-xs font-bold text-neutral-900">Everyday Comfort</h4>
                <p className="text-[11px] text-neutral-500 mt-0.5">Designed and tested for real life</p>
              </div>
            </div>

            <div className="flex flex-col sm:flex-row items-center gap-3">
              <div className="w-10 h-10 rounded-full bg-white shadow-xs border border-neutral-200 flex items-center justify-center text-neutral-800 shrink-0">
                <RefreshCw className="w-5 h-5" />
              </div>
              <div>
                <h4 className="text-xs font-bold text-neutral-900">Easy Returns</h4>
                <p className="text-[11px] text-neutral-500 mt-0.5">30-day hassle-free prepaid return portal</p>
              </div>
            </div>

            <div className="flex flex-col sm:flex-row items-center gap-3">
              <div className="w-10 h-10 rounded-full bg-white shadow-xs border border-neutral-200 flex items-center justify-center text-neutral-800 shrink-0">
                <Lock className="w-5 h-5" />
              </div>
              <div>
                <h4 className="text-xs font-bold text-neutral-900">Secure Checkout</h4>
                <p className="text-[11px] text-neutral-500 mt-0.5">256-bit encrypted card & digital wallet</p>
              </div>
            </div>
          </div>
        </div>
      </div>

      {/* Main Footer Content */}
      <div className="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8 py-16">
        <div className="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-5 gap-10">
          {/* Brand Info & Newsletter */}
          <div className="lg:col-span-2 space-y-4">
            <span className="text-2xl font-black tracking-tight text-neutral-900">
              {APP_CONFIG.name}
            </span>
            <p className="text-xs text-neutral-500 leading-relaxed max-w-sm">
              {APP_CONFIG.description} We curate timeless silhouettes and functional essentials engineered with premium textiles.
            </p>

            <div className="pt-2">
              <h5 className="text-xs font-bold uppercase tracking-wider text-neutral-900 mb-2">
                Join the WearDay Community
              </h5>
              <p className="text-xs text-neutral-500 mb-3">
                Receive 10% off your first purchase plus early access to seasonal capsules.
              </p>

              {subscribed ? (
                <div className="p-3 rounded-2xl bg-emerald-50 text-emerald-800 border border-emerald-200 flex items-center gap-2 text-xs font-semibold">
                  <Check className="w-4 h-4 text-emerald-600" />
                  <span>Thank you for subscribing! Check your inbox for your welcome code.</span>
                </div>
              ) : (
                <form onSubmit={handleSubscribe} className="flex gap-2 max-w-md">
                  <input
                    type="email"
                    required
                    value={email}
                    onChange={(e) => setEmail(e.target.value)}
                    placeholder="Enter your email address"
                    className="flex-1 px-4 py-2.5 text-xs bg-white border border-neutral-300 rounded-full focus:outline-none focus:ring-2 focus:ring-neutral-900"
                  />
                  <button
                    type="submit"
                    className="px-5 py-2.5 bg-neutral-900 text-white text-xs font-semibold rounded-full hover:bg-neutral-800 transition-colors flex items-center gap-1 shrink-0"
                  >
                    <span>Subscribe</span>
                    <ArrowRight className="w-3.5 h-3.5" />
                  </button>
                </form>
              )}
            </div>
          </div>

          {/* Quick Links */}
          <div className="space-y-3">
            <h5 className="text-xs font-bold uppercase tracking-wider text-neutral-900">
              Collections
            </h5>
            <ul className="space-y-2 text-xs text-neutral-600">
              <li>
                <Link href="/men" className="hover:text-neutral-950 transition-colors">
                  Men's Wardrobe
                </Link>
              </li>
              <li>
                <Link href="/women" className="hover:text-neutral-950 transition-colors">
                  Women's Wardrobe
                </Link>
              </li>
              <li>
                <Link href="/new-arrivals" className="hover:text-neutral-950 transition-colors">
                  New Arrivals
                </Link>
              </li>
              <li>
                <Link href="/best-sellers" className="hover:text-neutral-950 transition-colors">
                  Best Sellers
                </Link>
              </li>
              <li>
                <Link href="/trending" className="hover:text-neutral-950 transition-colors">
                  Trending Pieces
                </Link>
              </li>
              <li>
                <Link href="/sale" className="hover:text-neutral-950 transition-colors font-semibold text-rose-600">
                  Sale & Archive
                </Link>
              </li>
            </ul>
          </div>

          {/* Customer Experience */}
          <div className="space-y-3">
            <h5 className="text-xs font-bold uppercase tracking-wider text-neutral-900">
              Help & Support
            </h5>
            <ul className="space-y-2 text-xs text-neutral-600">
              <li>
                <Link href="/order-tracking" className="hover:text-neutral-950 transition-colors">
                  Track Your Order
                </Link>
              </li>
              <li>
                <Link href="/account/returns" className="hover:text-neutral-950 transition-colors">
                  Returns & Exchanges
                </Link>
              </li>
              <li>
                <Link href="/faq" className="hover:text-neutral-950 transition-colors">
                  Frequently Asked Questions
                </Link>
              </li>
              <li>
                <Link href="/contact" className="hover:text-neutral-950 transition-colors">
                  Contact Us
                </Link>
              </li>
              <li>
                <Link href="/about" className="hover:text-neutral-950 transition-colors">
                  Our Story & Craft
                </Link>
              </li>
              <li>
                <Link href="/blog" className="hover:text-neutral-950 transition-colors">
                  Styling Journal
                </Link>
              </li>
            </ul>
          </div>

          {/* Brand Policies */}
          <div className="space-y-3">
            <h5 className="text-xs font-bold uppercase tracking-wider text-neutral-900">
              Policies
            </h5>
            <ul className="space-y-2 text-xs text-neutral-600">
              <li>
                <Link href="/policies/privacy" className="hover:text-neutral-950 transition-colors">
                  Privacy Policy
                </Link>
              </li>
              <li>
                <Link href="/policies/terms" className="hover:text-neutral-950 transition-colors">
                  Terms & Conditions
                </Link>
              </li>
              <li>
                <Link href="/policies/returns" className="hover:text-neutral-950 transition-colors">
                  Return Policy
                </Link>
              </li>
              <li>
                <Link href="/policies/refund" className="hover:text-neutral-950 transition-colors">
                  Refund Policy
                </Link>
              </li>
              <li>
                <Link href="/policies/shipping" className="hover:text-neutral-950 transition-colors">
                  Shipping Policy
                </Link>
              </li>
              <li>
                <Link href="/policies/cancellation" className="hover:text-neutral-950 transition-colors">
                  Cancellation Policy
                </Link>
              </li>
              <li>
                <Link href="/policies/exchange" className="hover:text-neutral-950 transition-colors">
                  Exchange Policy
                </Link>
              </li>
              <li>
                <Link href="/policies/payment" className="hover:text-neutral-950 transition-colors">
                  Payment Policy
                </Link>
              </li>
              <li>
                <Link href="/policies/cookies" className="hover:text-neutral-950 transition-colors">
                  Cookie Policy
                </Link>
              </li>
            </ul>
          </div>
        </div>

        {/* Bottom Sub-footer */}
        <div className="pt-12 mt-12 border-t border-neutral-100 flex flex-col sm:flex-row items-center justify-between gap-4 text-xs text-neutral-500">
          <p>© {new Date().getFullYear()} {APP_CONFIG.name} Apparel Inc. All rights reserved.</p>
          <div className="flex items-center gap-4 text-neutral-400">
            <span>Visa</span>
            <span>•</span>
            <span>Mastercard</span>
            <span>•</span>
            <span>American Express</span>
            <span>•</span>
            <span>Apple Pay</span>
            <span>•</span>
            <span>PayPal</span>
          </div>
        </div>
      </div>
    </footer>
  );
};

