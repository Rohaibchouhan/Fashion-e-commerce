"use client";

import React, { useRef } from "react";
import Link from "next/link";
import Image from "next/image";
import { ArrowRight, ChevronRight } from "lucide-react";

interface PromoCardData {
  id: string;
  title: string;
  subtitle: string;
  linkText: string;
  linkHref: string;
  bgColor: string;
  image: string;
  imageAlt: string;
}

const PROMO_CARDS: PromoCardData[] = [
  {
    id: "summer-collection",
    title: "Summer\nCollection\n2024",
    subtitle: "Fresh styles for\nevery moment.",
    linkText: "Shop Women",
    linkHref: "/women",
    bgColor: "bg-[#faeee5]",
    image: "/images/promo/promo-summer-women.jpg",
    imageAlt: "Summer Collection 2024 - Women's styles",
  },
  {
    id: "mens-essentials",
    title: "Men's\nEssentials",
    subtitle: "Timeless looks\nfor every man.",
    linkText: "Shop Men",
    linkHref: "/men",
    bgColor: "bg-[#dce9f4]",
    image: "/images/promo/promo-mens-essentials.jpg",
    imageAlt: "Men's Essentials - Timeless menswear",
  },
  {
    id: "big-sale",
    title: "Big Sale\nUp to 50% Off",
    subtitle: "Don't miss out on\nexclusive deals!",
    linkText: "Shop Now",
    linkHref: "/sale",
    bgColor: "bg-[#faeee5]",
    image: "/images/promo/promo-sale-bags.jpg",
    imageAlt: "Big Sale Up to 50% Off - Exclusive deals",
  },
];

export const PromoBannerCards: React.FC = () => {
  const scrollContainerRef = useRef<HTMLDivElement>(null);

  const scrollNext = () => {
    if (scrollContainerRef.current) {
      const container = scrollContainerRef.current;
      const scrollAmount = container.clientWidth * 0.75;
      container.scrollBy({ left: scrollAmount, behavior: "smooth" });
    }
  };

  return (
    <section className="py-4 sm:py-6 relative">
      <div className="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8">
        <div className="relative group/section">
          {/* Cards Grid: 3 columns on desktop, horizontal scroll on mobile */}
          <div
            ref={scrollContainerRef}
            className="flex lg:grid lg:grid-cols-3 gap-4 sm:gap-6 overflow-x-auto no-scrollbar scroll-smooth snap-x snap-mandatory py-1"
          >
            {PROMO_CARDS.map((card) => (
              <div
                key={card.id}
                className={`relative rounded-3xl overflow-hidden ${card.bgColor} min-h-[220px] sm:min-h-[240px] lg:min-h-[250px] shrink-0 w-[86%] sm:w-[55%] md:w-[45%] lg:w-auto snap-start shadow-xs hover:shadow-md transition-all duration-300 group`}
              >
                <div className="flex h-full items-stretch justify-between relative z-10">
                  {/* Left Column: Editorial Serif Heading, Subtitle & Action Link */}
                  <div className="p-6 sm:p-7 flex flex-col justify-between max-w-[56%] sm:max-w-[55%] z-20">
                    <div className="space-y-2">
                      <h3 className="font-serif text-2xl sm:text-[28px] font-normal sm:font-medium text-neutral-900 leading-[1.12] whitespace-pre-line tracking-tight">
                        {card.title}
                      </h3>
                      <p className="text-xs sm:text-[13px] text-neutral-700 font-normal leading-relaxed whitespace-pre-line">
                        {card.subtitle}
                      </p>
                    </div>

                    <Link
                      href={card.linkHref}
                      className="inline-flex items-center gap-1.5 text-xs sm:text-sm font-bold text-neutral-950 hover:underline mt-4 group-hover:text-black transition-colors"
                    >
                      <span>{card.linkText}</span>
                      <ArrowRight className="w-3.5 h-3.5 transition-transform duration-300 group-hover:translate-x-1.5" />
                    </Link>
                  </div>

                  {/* Right Column: High Quality Cutout/Model Image */}
                  <div className="relative w-[48%] sm:w-[48%] h-full shrink-0 flex items-end justify-end overflow-hidden">
                    <div className="relative w-full h-full min-h-[210px]">
                      <Image
                        src={card.image}
                        alt={card.imageAlt}
                        fill
                        sizes="(max-width: 768px) 50vw, (max-width: 1200px) 33vw, 420px"
                        className="object-cover object-center group-hover:scale-105 transition-transform duration-700 ease-out"
                      />
                    </div>
                  </div>
                </div>

                {/* Whole card clickable overlay */}
                <Link
                  href={card.linkHref}
                  className="absolute inset-0 z-30"
                  aria-label={`${card.title.replace(/\n/g, " ")} - ${card.linkText}`}
                />
              </div>
            ))}
          </div>

          {/* Floating Right Scroll Arrow Button (matching reference screenshot) */}
          <button
            onClick={scrollNext}
            aria-label="Scroll next"
            className="absolute -right-2 sm:-right-3 top-1/2 -translate-y-1/2 z-40 w-10 h-10 sm:w-11 sm:h-11 rounded-full bg-white text-neutral-900 shadow-md border border-neutral-100 flex items-center justify-center hover:bg-neutral-50 hover:scale-105 active:scale-95 transition-all cursor-pointer hidden sm:flex"
          >
            <ChevronRight className="w-5 h-5 stroke-[2.2]" />
          </button>
        </div>
      </div>
    </section>
  );
};
