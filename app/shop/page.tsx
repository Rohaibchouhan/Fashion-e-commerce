"use client";

import React, { useState, useEffect, Suspense } from "react";
import { useSearchParams } from "next/navigation";
import { ProductGrid } from "@/components/product/product-grid";
import { ProductFilters, FilterState } from "@/components/product/product-filters";
import { ProductsService } from "@/services/products.service";
import { Product } from "@/types/product";
import { Breadcrumb } from "@/components/ui/breadcrumb";

function ShopContent() {
  const searchParams = useSearchParams();

  const initialGender = searchParams.get("gender") || "all";
  const initialCategory = searchParams.get("category") || "all";
  const initialSearch = searchParams.get("search") || "";

  const [filters, setFilters] = useState<FilterState>({
    gender: initialGender,
    category: initialCategory,
    minPrice: 0,
    maxPrice: 150,
    inStockOnly: false,
    sort: "featured",
  });

  const [products, setProducts] = useState<Product[]>([]);
  const [totalCount, setTotalCount] = useState(0);
  const [isLoading, setIsLoading] = useState(true);
  const [error, setError] = useState<string | null>(null);

  const handleFilterChange = (updated: Partial<FilterState>) => {
    setFilters((prev) => ({ ...prev, ...updated }));
  };

  const handleResetFilters = () => {
    setFilters({
      gender: "all",
      category: "all",
      minPrice: 0,
      maxPrice: 150,
      inStockOnly: false,
      sort: "featured",
    });
  };

  // Fetch dynamic products from Laravel API whenever filters or query change
  useEffect(() => {
    let isCancelled = false;
    setIsLoading(true);
    setError(null);

    ProductsService.getAll({
      gender: filters.gender !== "all" ? filters.gender : undefined,
      category: filters.category !== "all" ? filters.category : undefined,
      minPrice: filters.minPrice > 0 ? filters.minPrice : undefined,
      maxPrice: filters.maxPrice < 150 ? filters.maxPrice : undefined,
      inStockOnly: filters.inStockOnly || undefined,
      sort: filters.sort as any,
      search: initialSearch || undefined,
    })
      .then((res) => {
        if (!isCancelled) {
          setProducts(res.products);
          setTotalCount(res.total);
          setIsLoading(false);
        }
      })
      .catch((err) => {
        if (!isCancelled) {
          console.error("[ShopContent] Failed to load catalog products:", err);
          setError("Failed to load products. Please check connection and try again.");
          setProducts([]);
          setTotalCount(0);
          setIsLoading(false);
        }
      });

    return () => {
      isCancelled = true;
    };
  }, [filters, initialSearch]);

  return (
    <div className="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8 py-8 sm:py-12">
      {/* Header & Breadcrumb */}
      <div className="mb-8">
        <Breadcrumb items={[{ label: "Home", href: "/" }, { label: "Shop All" }]} />
        <h1 className="text-2xl sm:text-4xl font-black text-neutral-900 tracking-tight mt-2">
          {filters.gender !== "all"
            ? `${filters.gender.charAt(0).toUpperCase() + filters.gender.slice(1)}'s Collection`
            : filters.category !== "all"
            ? `${filters.category.charAt(0).toUpperCase() + filters.category.slice(1)}`
            : "All Clothing & Essentials"}
        </h1>
        <p className="text-xs sm:text-sm text-neutral-500 mt-1 max-w-xl">
          Clean lines, honest craftsmanship, and durable fabrics tailored for everyday wear.
        </p>
      </div>

      {error && (
        <div className="mb-6 p-4 rounded-xl bg-red-50 border border-red-200 text-red-700 text-xs flex items-center justify-between">
          <span>{error}</span>
          <button
            onClick={() => handleResetFilters()}
            className="font-bold underline ml-4 hover:text-red-900"
          >
            Reset Filters
          </button>
        </div>
      )}

      {/* Main Layout: Filters & Product Grid */}
      <div className="flex flex-col lg:flex-row gap-8 items-start">
        <div className="w-full lg:w-64 shrink-0">
          <ProductFilters
            filters={filters}
            onChange={handleFilterChange}
            onReset={handleResetFilters}
            totalCount={totalCount}
          />
        </div>

        <div className="flex-1 w-full">
          <ProductGrid
            products={products}
            isLoading={isLoading}
            columns={3}
            emptyTitle="No products match your criteria"
            emptyMessage="Try adjusting price ranges, clearing category filters, or selecting another department."
          />
        </div>
      </div>
    </div>
  );
}

export default function ShopPage() {
  return (
    <Suspense
      fallback={
        <div className="max-w-7xl mx-auto p-12 text-center text-xs text-neutral-400">
          Loading catalog...
        </div>
      }
    >
      <ShopContent />
    </Suspense>
  );
}
