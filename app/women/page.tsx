"use client";

import React, { useState, useEffect } from "react";
import { ProductGrid } from "@/components/product/product-grid";
import { ProductsService } from "@/services/products.service";
import { Product } from "@/types/product";
import { Breadcrumb } from "@/components/ui/breadcrumb";
import { Tabs } from "@/components/ui/tabs";
import Image from "next/image";

export default function WomensPage() {
  const [selectedCategory, setSelectedCategory] = useState("all");
  const [products, setProducts] = useState<Product[]>([]);
  const [isLoading, setIsLoading] = useState(true);

  const categoryTabs = [
    { id: "all", label: "All Women's Pieces" },
    { id: "shirts", label: "Linen & Shirts" },
    { id: "t-shirts", label: "Tees" },
    { id: "jackets", label: "Jackets" },
    { id: "accessories", label: "Bags & Totes" },
    { id: "shoes", label: "Footwear" },
  ];

  useEffect(() => {
    let isMounted = true;
    setIsLoading(true);

    ProductsService.getAll({
      gender: "women",
      category: selectedCategory !== "all" ? selectedCategory : undefined,
    })
      .then((res) => {
        if (isMounted) {
          setProducts(res.products);
          setIsLoading(false);
        }
      })
      .catch((err) => {
        if (isMounted) {
          console.error("Failed to fetch women's products:", err);
          setProducts([]);
          setIsLoading(false);
        }
      });

    return () => {
      isMounted = false;
    };
  }, [selectedCategory]);

  return (
    <div className="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8 py-8 sm:py-12 space-y-10">
      <Breadcrumb items={[{ label: "Home", href: "/" }, { label: "Women" }]} />

      {/* Featured Header Banner */}
      <div className="relative rounded-3xl overflow-hidden bg-neutral-900 text-white p-8 sm:p-14 min-h-[260px] flex items-center">
        <div className="absolute inset-0 opacity-40">
          <Image
            src="https://images.unsplash.com/photo-1515886657613-9f3515b0c78f?w=1200&auto=format&fit=crop&q=80"
            alt="Women's Collection"
            fill
            className="object-cover"
          />
        </div>
        <div className="relative z-10 max-w-lg space-y-2">
          <span className="text-[10px] font-bold uppercase tracking-widest text-neutral-300">
            Women's Wardrobe
          </span>
          <h1 className="text-3xl sm:text-5xl font-black tracking-tight">
            Soft Silhouettes
          </h1>
          <p className="text-xs sm:text-sm text-neutral-200">
            Breathable flax linen shirts, relaxed vintage denim jackets, and everyday leather essentials made with poise.
          </p>
        </div>
      </div>

      {/* Category Tabs */}
      <div className="flex items-center justify-between pb-2">
        <Tabs tabs={categoryTabs} activeTab={selectedCategory} onChange={setSelectedCategory} />
        <span className="text-xs text-neutral-500 hidden sm:inline">
          {products.length} styles available
        </span>
      </div>

      {/* Product Grid */}
      <ProductGrid
        products={products}
        isLoading={isLoading}
        columns={4}
        emptyTitle="No women's pieces found"
        emptyMessage="Try selecting another category tab above."
      />
    </div>
  );
}
