"use client";

import React, { useState } from "react";
import Link from "next/link";
import { Sparkles, X, ChevronRight } from "lucide-react";
import { APP_CONFIG } from "@/constants";

export const AnnouncementBar: React.FC = () => {
  const [isVisible, setIsVisible] = useState(true);

  if (!isVisible) return null;

  return (
    <div className="bg-neutral-900 text-white text-xs py-2 px-4 relative z-50">
      <div className="max-w-7xl mx-auto flex items-center justify-between">
        <div className="hidden sm:flex items-center gap-4 text-neutral-400">
          <span>🇺🇸 United States (USD $)</span>
          <span>•</span>
          <Link href="/faq" className="hover:text-white transition-colors">
            Help & FAQ
          </Link>
        </div>

        <div className="flex-1 flex items-center justify-center text-center gap-1.5 font-medium">
          <Sparkles className="w-3.5 h-3.5 text-amber-400 shrink-0" />
          <span>
            Complimentary Standard Shipping on all orders over ${APP_CONFIG.freeShippingThreshold}.
          </span>
          <Link
            href="/shop"
            className="inline-flex items-center underline underline-offset-2 ml-1 hover:text-neutral-300 font-semibold"
          >
            Shop Now <ChevronRight className="w-3 h-3" />
          </Link>
        </div>

        <div className="flex items-center">
          <button
            onClick={() => setIsVisible(false)}
            className="text-neutral-400 hover:text-white p-1 transition-colors"
            aria-label="Dismiss banner"
          >
            <X className="w-3.5 h-3.5" />
          </button>
        </div>
      </div>
    </div>
  );
};

