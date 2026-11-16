"use client";

import React, { useState, useEffect } from "react";
import Link from "next/link";
import { ArrowRight } from "lucide-react";
import { ProductsService } from "@/services/products.service";
import { Product } from "@/types/product";
import { ProductCard } from "@/components/product/product-card";
import { ProductCardSkeleton } from "@/components/ui/skeleton";

export const NewArrivalsSection: React.FC = () => {
  const [activeGender, setActiveGender] = useState<string>("men");
  const [products, setProducts] = useState<Product[]>([]);
  const [isLoading, setIsLoading] = useState(true);

  const tabs = [
    { id: "men", label: "Men" },
    { id: "women", label: "Women" },
    { id: "unisex", label: "Unisex" },
  ];

  useEffect(() => {
    let isMounted = true;
    setIsLoading(true);

    ProductsService.getNewArrivals(activeGender)
      .then((items) => {
        if (isMounted) {
          setProducts(items.slice(0, 5));
          setIsLoading(false);
        }
      })
      .catch((err) => {
        if (isMounted) {
          console.error("Failed to load new arrivals:", err);
          setProducts([]);
          setIsLoading(false);
        }
      });

    return () => {
      isMounted = false;
    };
  }, [activeGender]);

  return (
    <section className="py-10 sm:py-14">
      <div className="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8">
        {/* Header with Title, Gender Tabs, and View All link */}
        <div className="flex flex-col sm:flex-row sm:items-center justify-between gap-4 mb-8">
          <div className="flex items-center gap-6">
            <h2 className="text-xl sm:text-2xl font-black text-neutral-900 tracking-tight">
              New Arrivals
            </h2>
            <div className="flex items-center gap-1 border-b border-neutral-200">
              {tabs.map((tab) => {
                const isActive = activeGender === tab.id;
                return (
                  <button
                    key={tab.id}
                    onClick={() => setActiveGender(tab.id)}
                    className={`pb-2 px-3 text-xs font-bold transition-all relative cursor-pointer ${
                      isActive ? "text-neutral-900" : "text-neutral-400 hover:text-neutral-700"
                    }`}
                  >
                    {tab.label}
                    {isActive && (
                      <span className="absolute bottom-0 left-0 right-0 h-0.5 bg-neutral-900 rounded-full" />
                    )}
                  </button>
                );
              })}
            </div>
          </div>

          <Link
            href="/new-arrivals"
            className="inline-flex items-center gap-1 text-xs font-bold text-neutral-700 hover:text-neutral-950 transition-colors self-end sm:self-auto"
          >
            <span>View all</span>
            <ArrowRight className="w-3.5 h-3.5" />
          </Link>
        </div>

        {/* Product Cards Row */}
        {isLoading ? (
          <div className="grid grid-cols-2 sm:grid-cols-3 lg:grid-cols-5 gap-4 sm:gap-6">
            {Array.from({ length: 5 }).map((_, i) => (
              <ProductCardSkeleton key={i} />
            ))}
          </div>
        ) : (
          <div className="grid grid-cols-2 sm:grid-cols-3 lg:grid-cols-5 gap-4 sm:gap-6">
            {products.map((product) => (
              <ProductCard key={product.id} product={product} />
            ))}
          </div>
        )}
      </div>
    </section>
  );
};
