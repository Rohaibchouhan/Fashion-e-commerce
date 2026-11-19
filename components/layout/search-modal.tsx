"use client";

import React, { useState, useEffect } from "react";
import Image from "next/image";
import Link from "next/link";
import { Search, X, ArrowRight, TrendingUp, Sparkles, Loader2 } from "lucide-react";
import { useUIStore } from "@/stores/ui-store";
import { ProductsService } from "@/services/products.service";
import { Product } from "@/types/product";
import { formatPrice } from "@/lib/utils";

export const SearchModal: React.FC = () => {
  const { isSearchOpen, closeSearch } = useUIStore();
  const [query, setQuery] = useState("");
  const [searchResults, setSearchResults] = useState<Product[]>([]);
  const [isSearching, setIsSearching] = useState(false);

  useEffect(() => {
    const handleKeyDown = (e: KeyboardEvent) => {
      if ((e.metaKey || e.ctrlKey) && e.key === "k") {
        e.preventDefault();
        useUIStore.getState().toggleSearch();
      }
      if (e.key === "Escape" && isSearchOpen) {
        closeSearch();
      }
    };
    window.addEventListener("keydown", handleKeyDown);
    return () => window.removeEventListener("keydown", handleKeyDown);
  }, [isSearchOpen, closeSearch]);

  useEffect(() => {
    if (!query.trim()) {
      setSearchResults([]);
      setIsSearching(false);
      return;
    }

    setIsSearching(true);
    const timer = setTimeout(() => {
      ProductsService.getAll({ search: query.trim(), limit: 6 })
        .then((res) => {
          setSearchResults(res.products);
          setIsSearching(false);
        })
        .catch((err) => {
          console.error("Modal search failed:", err);
          setSearchResults([]);
          setIsSearching(false);
        });
    }, 200);

    return () => clearTimeout(timer);
  }, [query]);

  const popularSearches = ["Relaxed Fit Tee", "Easy Chino Pants", "Linen Shirt", "White Sneakers", "Overshirt"];

  if (!isSearchOpen) return null;

  return (
    <div className="fixed inset-0 z-50 overflow-y-auto">
      <div className="fixed inset-0 bg-black/60 backdrop-blur-sm" onClick={closeSearch} />

      <div className="relative min-h-screen flex items-start justify-center pt-16 sm:pt-24 px-4">
        <div className="relative w-full max-w-2xl bg-white rounded-3xl shadow-2xl border border-neutral-100 overflow-hidden animate-in zoom-in-95 duration-150">
          {/* Search Input Bar */}
          <div className="flex items-center px-6 py-4 border-b border-neutral-100 gap-3">
            <Search className="w-5 h-5 text-neutral-400 shrink-0" />
            <input
              type="text"
              autoFocus
              value={query}
              onChange={(e) => setQuery(e.target.value)}
              placeholder="Search everyday essentials, chinos, linen shirts..."
              className="w-full text-base sm:text-lg text-neutral-900 placeholder:text-neutral-400 bg-transparent focus:outline-none"
            />
            {query && (
              <button
                onClick={() => setQuery("")}
                className="p-1 rounded-full text-neutral-400 hover:text-neutral-700"
              >
                <X className="w-4 h-4" />
              </button>
            )}
            <button
              onClick={closeSearch}
              className="text-xs font-semibold px-2.5 py-1 bg-neutral-100 text-neutral-600 rounded-lg hover:bg-neutral-200"
            >
              ESC
            </button>
          </div>

          {/* Results or Suggestions */}
          <div className="p-6 max-h-[60vh] overflow-y-auto">
            {query.trim() === "" ? (
              <div className="space-y-6">
                <div>
                  <div className="flex items-center gap-2 text-xs font-bold text-neutral-400 uppercase tracking-wider mb-3">
                    <TrendingUp className="w-3.5 h-3.5" /> Popular Searches
                  </div>
                  <div className="flex flex-wrap gap-2">
                    {popularSearches.map((term) => (
                      <button
                        key={term}
                        onClick={() => setQuery(term)}
                        className="px-3 py-1.5 text-xs font-medium bg-neutral-100 hover:bg-neutral-200 text-neutral-800 rounded-full transition-colors cursor-pointer"
                      >
                        {term}
                      </button>
                    ))}
                  </div>
                </div>

                <div>
                  <div className="flex items-center gap-2 text-xs font-bold text-neutral-400 uppercase tracking-wider mb-3">
                    <Sparkles className="w-3.5 h-3.5 text-amber-500" /> Trending Collections
                  </div>
                  <div className="grid grid-cols-2 gap-2 text-sm">
                    <Link
                      href="/shop?gender=men"
                      onClick={closeSearch}
                      className="p-3 rounded-2xl bg-neutral-50 hover:bg-neutral-100 flex items-center justify-between font-medium text-neutral-800 transition-colors"
                    >
                      <span>Men's Essentials</span>
                      <ArrowRight className="w-4 h-4 text-neutral-400" />
                    </Link>
                    <Link
                      href="/shop?gender=women"
                      onClick={closeSearch}
                      className="p-3 rounded-2xl bg-neutral-50 hover:bg-neutral-100 flex items-center justify-between font-medium text-neutral-800 transition-colors"
                    >
                      <span>Women's Essentials</span>
                      <ArrowRight className="w-4 h-4 text-neutral-400" />
                    </Link>
                  </div>
                </div>
              </div>
            ) : searchResults.length > 0 ? (
              <div className="space-y-4">
                <div className="flex items-center justify-between text-xs text-neutral-500">
                  <span>Found {searchResults.length} matching products</span>
                  <Link
                    href={`/search?q=${encodeURIComponent(query)}`}
                    onClick={closeSearch}
                    className="font-semibold text-neutral-900 hover:underline flex items-center gap-1"
                  >
                    View all results <ArrowRight className="w-3 h-3" />
                  </Link>
                </div>
                <div className="grid grid-cols-1 sm:grid-cols-2 gap-3">
                  {searchResults.map((product) => (
                    <Link
                      key={product.id}
                      href={`/products/${product.slug}`}
                      onClick={closeSearch}
                      className="flex items-center gap-3 p-2.5 rounded-2xl border border-neutral-100 hover:border-neutral-200 hover:bg-neutral-50 transition-all group"
                    >
                      <div className="relative w-14 h-16 rounded-xl overflow-hidden bg-neutral-100 shrink-0">
                        <Image
                          src={product.images[0]}
                          alt={product.name}
                          fill
                          className="object-cover group-hover:scale-105 transition-transform duration-300"
                        />
                      </div>
                      <div className="min-w-0 flex-1">
                        <span className="text-[10px] uppercase font-bold text-neutral-400 tracking-wider">
                          {product.category}
                        </span>
                        <h4 className="text-xs font-bold text-neutral-900 truncate group-hover:text-neutral-700">
                          {product.name}
                        </h4>
                        <div className="text-xs font-semibold text-neutral-900 mt-0.5">
                          {formatPrice(product.price)}
                        </div>
                      </div>
                    </Link>
                  ))}
                </div>
              </div>
            ) : (
              <div className="py-12 text-center">
                <p className="text-sm text-neutral-700 font-semibold">No products found for "{query}"</p>
                <p className="text-xs text-neutral-500 mt-1">Try searching for tees, trousers, or overshirts.</p>
              </div>
            )}
          </div>
        </div>
      </div>
    </div>
  );
};

