"use client";

import React, { useState, useEffect } from "react";
import { ProductGrid } from "@/components/product/product-grid";
import { ProductsService } from "@/services/products.service";
import { Product } from "@/types/product";
import { Breadcrumb } from "@/components/ui/breadcrumb";
import { Tabs } from "@/components/ui/tabs";
import Image from "next/image";

export default function MensPage() {
  const [selectedCategory, setSelectedCategory] = useState("all");
  const [products, setProducts] = useState<Product[]>([]);
  const [isLoading, setIsLoading] = useState(true);

  const categoryTabs = [
    { id: "all", label: "All Men's Pieces" },
    { id: "t-shirts", label: "T-Shirts" },
    { id: "shirts", label: "Shirts" },
    { id: "pants", label: "Pants" },
    { id: "denim", label: "Denim" },
    { id: "jackets", label: "Jackets" },
    { id: "shoes", label: "Shoes" },
  ];

  useEffect(() => {
    let isMounted = true;
    setIsLoading(true);

    ProductsService.getAll({
      gender: "men",
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
          console.error("Failed to fetch men's products:", err);
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
      <Breadcrumb items={[{ label: "Home", href: "/" }, { label: "Men" }]} />

      {/* Featured Header Banner */}
      <div className="relative rounded-3xl overflow-hidden bg-neutral-900 text-white p-8 sm:p-14 min-h-[260px] flex items-center">
        <div className="absolute inset-0 opacity-40">
          <Image
            src="https://images.unsplash.com/photo-1507679799987-c73779587ccf?w=1200&auto=format&fit=crop&q=80"
            alt="Men's Collection"
            fill
            className="object-cover"
          />
        </div>
        <div className="relative z-10 max-w-lg space-y-2">
          <span className="text-[10px] font-bold uppercase tracking-widest text-neutral-300">
            Men's Wardrobe
          </span>
          <h1 className="text-3xl sm:text-5xl font-black tracking-tight">
            Effortless Essentials
          </h1>
          <p className="text-xs sm:text-sm text-neutral-200">
            Boxy drop-shoulder tees, tailored stretch chinos, and textured utility overshirts built for daily repetition.
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
        emptyTitle="No menswear pieces found"
        emptyMessage="Try selecting another category tab above."
      />
    </div>
  );
}
