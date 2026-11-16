import React from "react";
import { Product } from "@/types/product";
import { ProductCard } from "./product-card";
import { ProductCardSkeleton } from "@/components/ui/skeleton";
import { ShoppingBag } from "lucide-react";
import { cn } from "@/lib/utils";

interface ProductGridProps {
  products: Product[];
  isLoading?: boolean;
  columns?: 2 | 3 | 4;
  className?: string;
  emptyTitle?: string;
  emptyMessage?: string;
}

export const ProductGrid: React.FC<ProductGridProps> = ({
  products,
  isLoading = false,
  columns = 4,
  className,
  emptyTitle = "No products found",
  emptyMessage = "Try relaxing your filters or search keywords.",
}) => {
  const colStyles = {
    2: "grid-cols-2",
    3: "grid-cols-2 md:grid-cols-3",
    4: "grid-cols-2 md:grid-cols-3 lg:grid-cols-4",
  };

  if (isLoading) {
    return (
      <div className={cn("grid gap-x-4 gap-y-8 sm:gap-x-6 sm:gap-y-10", colStyles[columns], className)}>
        {Array.from({ length: 8 }).map((_, i) => (
          <ProductCardSkeleton key={i} />
        ))}
      </div>
    );
  }

  if (products.length === 0) {
    return (
      <div className="py-20 text-center rounded-3xl bg-neutral-50 border border-neutral-200/60 p-8">
        <div className="w-16 h-16 rounded-full bg-white shadow-xs border border-neutral-200 flex items-center justify-center mx-auto text-neutral-400 mb-4">
          <ShoppingBag className="w-8 h-8" />
        </div>
        <h3 className="text-base font-bold text-neutral-900">{emptyTitle}</h3>
        <p className="text-xs text-neutral-500 mt-1 max-w-sm mx-auto">{emptyMessage}</p>
      </div>
    );
  }

  return (
    <div className={cn("grid gap-x-4 gap-y-8 sm:gap-x-6 sm:gap-y-10", colStyles[columns], className)}>
      {products.map((product) => (
        <ProductCard key={product.id} product={product} />
      ))}
    </div>
  );
};

