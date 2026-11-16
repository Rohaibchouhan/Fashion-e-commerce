"use client";

import React, { useState, useEffect } from "react";
import Image from "next/image";
import Link from "next/link";
import { Shirt, RotateCcw } from "lucide-react";
import { APP_CONFIG } from "@/constants";

export const HeroBanner: React.FC = () => {
  const [activeSlide, setActiveSlide] = useState(0);

  const slides = [
    {
      id: 1,
      titleLine1: "Everyday",
      titleLine2: "Looks. Made",
      titleLine3: "Effortless.",
      subtitleLine1: "Practical pieces. Real comfort.",
      subtitleLine2: "For every part of your day.",
      image: "/images/hero/hero-1.jpg",
      fallbackImage: "/images/hero/uploaded-hero.jpg",
      ctaShop: "/shop",
      ctaNew: "/new-arrivals",
    },
    {
      id: 2,
      titleLine1: "Modern",
      titleLine2: "Layers. Crafted",
      titleLine3: "Timeless.",
      subtitleLine1: "European linen & organic cotton.",
      subtitleLine2: "Breathe easy wherever you roam.",
      image: "/images/hero/hero-2.jpg",
      fallbackImage: "https://images.unsplash.com/photo-1515886657613-9f3515b0c78f?w=1400&auto=format&fit=crop&q=80",
      ctaShop: "/men",
      ctaNew: "/shop?gender=men",
    },
    {
      id: 3,
      titleLine1: "Everyday",
      titleLine2: "Capsule. Lived",
      titleLine3: "In Poise.",
      subtitleLine1: "Heavyweight terry & tailored chinos.",
      subtitleLine2: "Designed and tested for real life.",
      image: "/images/hero/hero-3.jpg",
      fallbackImage: "https://images.unsplash.com/photo-1490481651871-ab68de25d43d?w=1400&auto=format&fit=crop&q=80",
      ctaShop: "/women",
      ctaNew: "/shop?gender=women",
    },
  ];

  // Auto-advance slide every 6 seconds
  useEffect(() => {
    const timer = setInterval(() => {
      setActiveSlide((prev) => (prev + 1) % slides.length);
    }, 6000);
    return () => clearInterval(timer);
  }, [slides.length]);

  const current = slides[activeSlide];

  return (
    <section className="relative overflow-hidden pt-3 sm:pt-4 pb-6">
      <div className="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8">
        <div className="relative rounded-3xl sm:rounded-[36px] overflow-hidden bg-[#e8e6e1] min-h-[480px] sm:min-h-[540px] lg:min-h-[580px] flex items-center shadow-xs">
          {/* Background Images with Crossfade Transition */}
          {slides.map((slide, idx) => (
            <div
              key={slide.id}
              className={`absolute inset-0 transition-opacity duration-1000 ease-in-out ${
                activeSlide === idx ? "opacity-100 z-0" : "opacity-0 pointer-events-none"
              }`}
            >
              <Image
                src={slide.image}
                alt={`${slide.titleLine1} ${slide.titleLine2} ${slide.titleLine3}`}
                fill
                priority={idx === 0}
                className="object-cover object-right sm:object-[center_right] lg:object-center brightness-[0.98]"
              />
              {/* Soft Left Lightening Vignette for Text Legibility */}
              <div className="absolute inset-0 bg-gradient-to-r from-[#fbfbf9]/95 via-[#fbfbf9]/75 to-transparent w-full sm:w-[65%] lg:w-[55%]" />
            </div>
          ))}

          {/* Left Text & CTA Content (Matching Reference Layout) */}
          <div className="relative z-10 max-w-xl p-6 sm:p-12 lg:p-16 space-y-6">
            <div className="space-y-3">
              <h1 className="text-4xl sm:text-6xl lg:text-[68px] font-black text-neutral-950 tracking-tight leading-[1.04]">
                <span className="block">{current.titleLine1}</span>
                <span className="block">{current.titleLine2}</span>
                <span className="block">{current.titleLine3}</span>
              </h1>

              <div className="text-sm sm:text-base text-neutral-600 font-medium leading-relaxed pt-1 space-y-0.5">
                <p>{current.subtitleLine1}</p>
                <p>{current.subtitleLine2}</p>
              </div>
            </div>

            {/* Pill CTA Buttons */}
            <div className="flex flex-wrap items-center gap-3.5 pt-1">
              <Link
                href={current.ctaShop}
                className="px-8 py-3.5 rounded-2xl sm:rounded-full bg-neutral-950 text-white font-bold text-xs sm:text-sm hover:bg-neutral-800 transition-all shadow-md active:scale-98"
              >
                Shop Now
              </Link>
              <Link
                href={current.ctaNew}
                className="px-8 py-3.5 rounded-2xl sm:rounded-full bg-white text-neutral-950 border border-neutral-200/80 font-bold text-xs sm:text-sm hover:bg-neutral-50 transition-all shadow-xs active:scale-98"
              >
                New In
              </Link>
            </div>
          </div>

          {/* Floating Badge Card on Bottom-Right (Matching Exact Reference Screenshot) */}
          <div className="absolute right-4 bottom-4 sm:right-8 sm:bottom-8 z-10 hidden sm:block">
            <div className="bg-white/95 backdrop-blur-md rounded-2xl sm:rounded-3xl p-4 sm:p-5 shadow-lg border border-neutral-100/90 text-neutral-900 space-y-3 min-w-[200px]">
              {/* Row 1: Free Shipping */}
              <div className="flex items-center gap-3">
                <div className="w-8 h-8 rounded-full bg-neutral-100/80 flex items-center justify-center shrink-0 text-neutral-800">
                  <Shirt className="w-4 h-4 stroke-[1.8]" />
                </div>
                <div className="leading-tight">
                  <p className="text-xs font-bold text-neutral-950">Free Shipping</p>
                  <p className="text-[10px] text-neutral-500 font-medium">on orders ${APP_CONFIG.freeShippingThreshold}+</p>
                </div>
              </div>

              {/* Divider */}
              <div className="h-px bg-neutral-100" />

              {/* Row 2: Easy Returns */}
              <div className="flex items-center gap-3">
                <div className="w-8 h-8 rounded-full bg-neutral-100/80 flex items-center justify-center shrink-0 text-neutral-800">
                  <RotateCcw className="w-4 h-4 stroke-[1.8]" />
                </div>
                <div className="leading-tight">
                  <p className="text-xs font-bold text-neutral-950">Easy Returns</p>
                  <p className="text-[10px] text-neutral-500 font-medium">30-day returns</p>
                </div>
              </div>
            </div>
          </div>

          {/* Carousel Navigation Dots (● ○ ○) positioned at bottom */}
          <div className="absolute bottom-5 left-1/2 -translate-x-1/2 sm:left-48 lg:left-64 flex items-center gap-2 z-20">
            {slides.map((_, i) => (
              <button
                key={i}
                onClick={() => setActiveSlide(i)}
                className={`transition-all duration-300 rounded-full cursor-pointer ${
                  activeSlide === i
                    ? "w-4 h-4 bg-white border-2 border-neutral-900 ring-2 ring-white/50"
                    : "w-3 h-3 bg-white/70 hover:bg-white border border-neutral-400"
                }`}
                aria-label={`Go to slide ${i + 1}`}
              />
            ))}
          </div>
        </div>
      </div>
    </section>
  );
};
