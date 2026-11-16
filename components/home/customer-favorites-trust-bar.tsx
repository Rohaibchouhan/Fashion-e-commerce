"use client";

import React from "react";
import Link from "next/link";
import { Star, Heart, RotateCcw, ShieldCheck, ArrowRight } from "lucide-react";

export const CustomerFavoritesTrustBar: React.FC = () => {
  const trustItems = [
    {
      icon: Star,
      title: "Great Quality",
      description: "Loved by thousands",
    },
    {
      icon: Heart,
      title: "Everyday Comfort",
      description: "Designed for real life",
    },
    {
      icon: RotateCcw,
      title: "Easy Returns",
      description: "No hassle, ever",
    },
    {
      icon: ShieldCheck,
      title: "Secure Checkout",
      description: "Shop with confidence",
    },
  ];

  return (
    <section className="py-6 sm:py-8 border-y border-neutral-200/70 bg-neutral-50/50 my-6">
      <div className="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8">
        <div className="flex items-center justify-between mb-5">
          <h2 className="text-lg sm:text-xl font-black text-neutral-900 tracking-tight">
            Customer Favorites
          </h2>
          <Link
            href="/best-sellers"
            className="inline-flex items-center gap-1 text-xs font-bold text-neutral-600 hover:text-neutral-950 transition-colors"
          >
            <span>View all</span>
            <ArrowRight className="w-3.5 h-3.5" />
          </Link>
        </div>

        <div className="grid grid-cols-2 md:grid-cols-4 gap-4 sm:gap-6 py-2">
          {trustItems.map((item, index) => {
            const Icon = item.icon;
            return (
              <div
                key={index}
                className="flex items-center gap-3.5 p-3 rounded-2xl bg-white border border-neutral-100 shadow-2xs hover:shadow-xs transition-shadow"
              >
                <div className="w-10 h-10 rounded-full bg-neutral-100/80 flex items-center justify-center shrink-0 text-neutral-900">
                  <Icon className="w-5 h-5 stroke-[1.75]" />
                </div>
                <div className="leading-tight">
                  <p className="text-xs sm:text-sm font-bold text-neutral-950">
                    {item.title}
                  </p>
                  <p className="text-[11px] text-neutral-500 font-medium mt-0.5">
                    {item.description}
                  </p>
                </div>
              </div>
            );
          })}
        </div>
      </div>
    </section>
  );
};

