"use client";

import React, { useState, useEffect, Suspense } from "react";
import { useSearchParams } from "next/navigation";
import { ProductGrid } from "@/components/product/product-grid";
import { ProductsService } from "@/services/products.service";
import { Product } from "@/types/product";
import { Breadcrumb } from "@/components/ui/breadcrumb";
import { Search } from "lucide-react";

function SearchPageContent() {
  const searchParams = useSearchParams();
  const initialQuery = searchParams.get("q") || "";
  const [searchTerm, setSearchTerm] = useState(initialQuery);
  const [products, setProducts] = useState<Product[]>([]);
  const [isLoading, setIsLoading] = useState(true);

  useEffect(() => {
    let isMounted = true;
    setIsLoading(true);

    const timer = setTimeout(() => {
      ProductsService.getAll({ search: searchTerm || undefined })
        .then((res) => {
          if (isMounted) {
            setProducts(res.products);
            setIsLoading(false);
          }
        })
        .catch((err) => {
          if (isMounted) {
            console.error("Search failed:", err);
            setProducts([]);
            setIsLoading(false);
          }
        });
    }, 250);

    return () => {
      isMounted = false;
      clearTimeout(timer);
    };
  }, [searchTerm]);

  return (
    <div className="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8 py-8 sm:py-12 space-y-8">
      <div>
        <Breadcrumb items={[{ label: "Home", href: "/" }, { label: "Search" }]} />
        <h1 className="text-2xl sm:text-4xl font-black text-neutral-900 tracking-tight mt-2">
          Search Results
        </h1>
        <p className="text-xs sm:text-sm text-neutral-500 mt-1">
          {isLoading
            ? "Searching styles..."
            : `Showing ${products.length} styles for "${searchTerm || "all products"}"`}
        </p>
      </div>

      {/* Search Input Bar */}
      <div className="max-w-md relative">
        <Search className="w-5 h-5 text-neutral-400 absolute left-4 top-1/2 -translate-y-1/2 pointer-events-none" />
        <input
          type="text"
          value={searchTerm}
          onChange={(e) => setSearchTerm(e.target.value)}
          placeholder="Search tees, chinos, jackets..."
          className="w-full h-12 pl-12 pr-4 text-sm bg-white border border-neutral-300 rounded-full focus:outline-none focus:ring-2 focus:ring-neutral-900 transition-shadow"
        />
      </div>

      <ProductGrid
        products={products}
        isLoading={isLoading}
        columns={4}
        emptyTitle={`No results found for "${searchTerm}"`}
        emptyMessage="Try searching with a broader keyword like shirt, pant, or hoodie."
      />
    </div>
  );
}

export default function SearchPage() {
  return (
    <Suspense fallback={<div className="max-w-7xl mx-auto p-12 text-center text-xs text-neutral-400">Loading search...</div>}>
      <SearchPageContent />
    </Suspense>
  );
}
