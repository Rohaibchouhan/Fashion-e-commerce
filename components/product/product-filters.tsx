"use client";

import React, { useState, useEffect } from "react";
import { SlidersHorizontal, X, RotateCcw } from "lucide-react";
import { CategoriesService, Category } from "@/services/categories.service";
import { Drawer } from "@/components/ui/drawer";

export interface FilterState {
  gender: string;
  category: string;
  minPrice: number;
  maxPrice: number;
  inStockOnly: boolean;
  sort: string;
}

interface ProductFiltersProps {
  filters: FilterState;
  onChange: (updated: Partial<FilterState>) => void;
  onReset: () => void;
  totalCount: number;
  categories?: Category[];
}

export const ProductFilters: React.FC<ProductFiltersProps> = ({
  filters,
  onChange,
  onReset,
  totalCount,
  categories: initialCategories,
}) => {
  const [isMobileFilterOpen, setIsMobileFilterOpen] = useState(false);
  const [categoriesList, setCategoriesList] = useState<Category[]>(initialCategories || []);

  useEffect(() => {
    if (initialCategories && initialCategories.length > 0) {
      setCategoriesList(initialCategories);
      return;
    }

    let isMounted = true;
    CategoriesService.getAll({
      gender: filters.gender !== "all" ? filters.gender : undefined,
    })
      .then((cats) => {
        if (isMounted) setCategoriesList(cats);
      })
      .catch((err) => {
        console.error("Failed to load categories for filters:", err);
      });

    return () => {
      isMounted = false;
    };
  }, [filters.gender, initialCategories]);

  const sortOptions = [
    { value: "featured", label: "Featured" },
    { value: "newest", label: "Newest Arrivals" },
    { value: "price-asc", label: "Price: Low to High" },
    { value: "price-desc", label: "Price: High to Low" },
    { value: "rating", label: "Top Customer Rating" },
  ];

  const hasActiveFilters =
    filters.gender !== "all" ||
    filters.category !== "all" ||
    filters.minPrice > 0 ||
    filters.maxPrice < 150 ||
    filters.inStockOnly;

  const FilterContent = (
    <div className="space-y-6">
      {/* Gender / Department */}
      <div className="space-y-2.5">
        <label className="text-xs font-bold uppercase tracking-wider text-neutral-900 block">
          Department
        </label>
        <div className="flex flex-wrap gap-1.5">
          {[
            { id: "all", label: "All" },
            { id: "men", label: "Men" },
            { id: "women", label: "Women" },
            { id: "unisex", label: "Unisex" },
          ].map((item) => (
            <button
              key={item.id}
              onClick={() => onChange({ gender: item.id })}
              className={`px-3 py-1.5 text-xs font-semibold rounded-full border transition-all cursor-pointer ${
                filters.gender === item.id
                  ? "bg-neutral-900 text-white border-neutral-900"
                  : "bg-white text-neutral-700 border-neutral-200 hover:border-neutral-400"
              }`}
            >
              {item.label}
            </button>
          ))}
        </div>
      </div>

      {/* Category */}
      <div className="space-y-2.5">
        <label className="text-xs font-bold uppercase tracking-wider text-neutral-900 block">
          Category
        </label>
        <div className="flex flex-wrap gap-1.5">
          <button
            onClick={() => onChange({ category: "all" })}
            className={`px-3 py-1.5 text-xs font-semibold rounded-full border transition-all cursor-pointer ${
              filters.category === "all"
                ? "bg-neutral-900 text-white border-neutral-900"
                : "bg-white text-neutral-700 border-neutral-200 hover:border-neutral-400"
            }`}
          >
            All Pieces
          </button>
          {categoriesList.map((cat) => (
            <button
              key={cat.id}
              onClick={() => onChange({ category: cat.slug })}
              className={`px-3 py-1.5 text-xs font-semibold rounded-full border transition-all cursor-pointer ${
                filters.category === cat.slug
                  ? "bg-neutral-900 text-white border-neutral-900"
                  : "bg-white text-neutral-700 border-neutral-200 hover:border-neutral-400"
              }`}
            >
              {cat.name}
            </button>
          ))}
        </div>
      </div>

      {/* Price Range */}
      <div className="space-y-2.5">
        <div className="flex justify-between items-center text-xs">
          <label className="font-bold uppercase tracking-wider text-neutral-900">
            Price Range
          </label>
          <span className="font-medium text-neutral-600">
            ${filters.minPrice} — ${filters.maxPrice}
          </span>
        </div>
        <input
          type="range"
          min="10"
          max="150"
          step="5"
          value={filters.maxPrice}
          onChange={(e) => onChange({ maxPrice: Number(e.target.value) })}
          className="w-full accent-neutral-900 cursor-pointer"
        />
      </div>

      {/* In-Stock Toggle */}
      <div className="pt-2">
        <label className="flex items-center gap-2 cursor-pointer text-xs font-semibold text-neutral-800">
          <input
            type="checkbox"
            checked={filters.inStockOnly}
            onChange={(e) => onChange({ inStockOnly: e.target.checked })}
            className="w-4 h-4 rounded text-neutral-900 accent-neutral-900 cursor-pointer"
          />
          <span>In Stock Only</span>
        </label>
      </div>

      {hasActiveFilters && (
        <div className="pt-4 border-t border-neutral-100">
          <button
            onClick={onReset}
            className="w-full py-2 text-xs font-semibold text-neutral-600 hover:text-neutral-900 bg-neutral-100 rounded-xl hover:bg-neutral-200 transition-colors flex items-center justify-center gap-1.5 cursor-pointer"
          >
            <RotateCcw className="w-3.5 h-3.5" /> Reset All Filters
          </button>
        </div>
      )}
    </div>
  );

  return (
    <div className="space-y-4">
      {/* Top Bar with Total Count, Filter Toggle (mobile), and Sort Select */}
      <div className="flex flex-col sm:flex-row sm:items-center justify-between gap-4 pb-4 border-b border-neutral-200/80">
        <div className="flex items-center gap-3">
          <button
            onClick={() => setIsMobileFilterOpen(true)}
            className="lg:hidden flex items-center gap-2 px-4 py-2 bg-neutral-100 text-neutral-900 text-xs font-semibold rounded-full hover:bg-neutral-200 transition-colors cursor-pointer"
          >
            <SlidersHorizontal className="w-3.5 h-3.5" />
            <span>Filters</span>
            {hasActiveFilters && (
              <span className="w-2 h-2 rounded-full bg-neutral-900" />
            )}
          </button>

          <span className="text-xs font-medium text-neutral-500">
            Showing <strong className="text-neutral-900">{totalCount}</strong> styles
          </span>
        </div>

        {/* Sort Dropdown */}
        <div className="flex items-center gap-2">
          <span className="text-xs text-neutral-500 font-medium whitespace-nowrap">
            Sort by:
          </span>
          <select
            value={filters.sort}
            onChange={(e) => onChange({ sort: e.target.value })}
            className="text-xs font-semibold bg-white border border-neutral-200 rounded-xl px-3 py-2 text-neutral-800 focus:outline-none focus:ring-1 focus:ring-neutral-900 cursor-pointer"
          >
            {sortOptions.map((opt) => (
              <option key={opt.value} value={opt.value}>
                {opt.label}
              </option>
            ))}
          </select>
        </div>
      </div>

      {/* Desktop Inline Facets Filter Sidebar */}
      <div className="hidden lg:block w-64 shrink-0 pr-6">
        {FilterContent}
      </div>

      {/* Mobile Drawer */}
      <Drawer
        isOpen={isMobileFilterOpen}
        onClose={() => setIsMobileFilterOpen(false)}
        position="left"
        width="sm"
        title="Filter & Refine"
        footer={
          <button
            onClick={() => setIsMobileFilterOpen(false)}
            className="w-full py-3 bg-neutral-900 text-white rounded-full text-xs font-bold"
          >
            Show {totalCount} Results
          </button>
        }
      >
        <div className="py-2">{FilterContent}</div>
      </Drawer>
    </div>
  );
};

