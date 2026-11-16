import React from "react";
import { ProductGrid } from "@/components/product/product-grid";
import { ProductsService } from "@/services/products.service";
import { Breadcrumb } from "@/components/ui/breadcrumb";

export default async function BestSellersPage() {
  const bestSellers = await ProductsService.getBestSellers();

  return (
    <div className="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8 py-8 sm:py-12 space-y-8">
      <div>
        <Breadcrumb items={[{ label: "Home", href: "/" }, { label: "Best Sellers" }]} />
        <h1 className="text-2xl sm:text-4xl font-black text-neutral-900 tracking-tight mt-2">
          Best Sellers
        </h1>
        <p className="text-xs sm:text-sm text-neutral-500 mt-1 max-w-lg">
          The most repurchased foundation staples — reviewed and praised by thousands of wearers.
        </p>
      </div>

      <ProductGrid products={bestSellers} columns={4} />
    </div>
  );
}
