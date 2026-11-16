import React from "react";
import { ProductGrid } from "@/components/product/product-grid";
import { ProductsService } from "@/services/products.service";
import { Breadcrumb } from "@/components/ui/breadcrumb";

export default async function NewArrivalsPage() {
  const newProducts = await ProductsService.getNewArrivals();

  return (
    <div className="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8 py-8 sm:py-12 space-y-8">
      <div>
        <Breadcrumb items={[{ label: "Home", href: "/" }, { label: "New Arrivals" }]} />
        <h1 className="text-2xl sm:text-4xl font-black text-neutral-900 tracking-tight mt-2">
          New Arrivals
        </h1>
        <p className="text-xs sm:text-sm text-neutral-500 mt-1 max-w-lg">
          Fresh colorways, lightweight linens, and newly launched everyday outerwear for the season.
        </p>
      </div>

      <ProductGrid products={newProducts} columns={4} />
    </div>
  );
}
