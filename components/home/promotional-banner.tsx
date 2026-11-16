import React from "react";
import Link from "next/link";
import Image from "next/image";
import { ArrowRight, Leaf, Shield, Award } from "lucide-react";

export const PromotionalBanner: React.FC = () => {
  return (
    <section className="my-14 sm:my-20">
      <div className="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8">
        <div className="relative rounded-3xl overflow-hidden bg-neutral-900 text-white p-8 sm:p-14 lg:p-16">
          <div className="absolute inset-0 opacity-40">
            <Image
              src="https://images.unsplash.com/photo-1558769132-cb1aea458c5e?w=1400&auto=format&fit=crop&q=80"
              alt="Fabric Craftsmanship"
              fill
              className="object-cover"
            />
          </div>

          <div className="relative z-10 max-w-xl space-y-6">
            <span className="inline-flex items-center gap-1.5 px-3 py-1 rounded-full text-[11px] font-bold uppercase tracking-wider bg-white/20 backdrop-blur-md text-white border border-white/30">
              <Leaf className="w-3.5 h-3.5 text-emerald-400" />
              100% Traceable Organic Fibers
            </span>

            <h2 className="text-3xl sm:text-4xl lg:text-5xl font-black tracking-tight leading-tight">
              Crafted to Outlast Seasons, Not Just Trends.
            </h2>

            <p className="text-xs sm:text-sm text-neutral-300 leading-relaxed font-normal">
              Every garment is preshrunk, dual-needle stitched, and tailored from single-origin combed organic cotton and European flax linen. We believe true luxury is clothes that feel better with every wash.
            </p>

            <div className="pt-2 flex flex-wrap gap-4">
              <Link
                href="/about"
                className="px-8 py-3.5 rounded-full bg-white text-neutral-950 font-bold text-xs hover:bg-neutral-100 transition-all shadow-md active:scale-98"
              >
                Our Sustainability Story
              </Link>
              <Link
                href="/shop"
                className="px-8 py-3.5 rounded-full border border-white/40 text-white font-semibold text-xs hover:bg-white/10 transition-colors flex items-center gap-1.5"
              >
                <span>Shop The Capsule</span>
                <ArrowRight className="w-3.5 h-3.5" />
              </Link>
            </div>
          </div>
        </div>
      </div>
    </section>
  );
};

