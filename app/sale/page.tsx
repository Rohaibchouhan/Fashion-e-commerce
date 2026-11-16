import React from "react";
import { ProductGrid } from "@/components/product/product-grid";
import { ProductsService } from "@/services/products.service";
import { Breadcrumb } from "@/components/ui/breadcrumb";

export default async function SalePage() {
  const saleProducts = await ProductsService.getSale();

  return (
    <div className="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8 py-8 sm:py-12 space-y-8">
      <div>
        <Breadcrumb items={[{ label: "Home", href: "/" }, { label: "Sale" }]} />
        <div className="flex items-center gap-3 mt-2">
          <h1 className="text-2xl sm:text-4xl font-black text-neutral-900 tracking-tight">
            Sale & Archive
          </h1>
          <span className="px-3 py-1 bg-rose-600 text-white font-bold text-xs rounded-full">
            Up to 30% Off
          </span>
        </div>
        <p className="text-xs sm:text-sm text-neutral-500 mt-1 max-w-lg">
          Limited quantity past season capsules and discounted foundational favorites.
        </p>
      </div>

      <ProductGrid products={saleProducts} columns={4} />
    </div>
  );
}
