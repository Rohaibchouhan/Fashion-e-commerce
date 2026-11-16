"use client";

import React from "react";
import Link from "next/link";
import Image from "next/image";
import { useWishlistStore } from "@/stores/wishlist-store";
import { useCartStore } from "@/stores/cart-store";
import { MOCK_PRODUCTS } from "@/lib/mock-data/products";
import { formatPrice } from "@/lib/utils";
import { toast } from "@/components/ui/toast";
import { Heart, ShoppingBag, Trash2, ArrowRight } from "lucide-react";

export default function AccountWishlistPage() {
  const { items, removeItem, clearWishlist } = useWishlistStore();
  const { addItem, openCart } = useCartStore();

  const wishlistProducts = MOCK_PRODUCTS.filter((p) => items.includes(p.id));

  const handleMoveToBag = (product: (typeof wishlistProducts)[0]) => {
    const defaultSize = product.sizes.find((s) => s.inStock)?.name || product.sizes[0].name;

    addItem({
      productId: product.id,
      slug: product.slug,
      name: product.name,
      price: product.price,
      originalPrice: product.compareAtPrice,
      image: product.images[0],
      selectedColor: product.colors[0]?.name || "Standard",
      selectedSize: defaultSize,
      quantity: 1,
      maxStock: 20,
    });

    removeItem(product.id);
    toast.success("Moved to Bag", `${product.name} moved to your shopping bag.`);
    openCart();
  };

  return (
    <div className="space-y-6">
      <div className="bg-white rounded-3xl border border-neutral-200 p-6 sm:p-8 shadow-xs flex flex-col sm:flex-row sm:items-center justify-between gap-4">
        <div>
          <h1 className="text-xl sm:text-2xl font-black text-neutral-900 tracking-tight">
            Saved Wishlist ({wishlistProducts.length})
          </h1>
          <p className="text-xs text-neutral-500 mt-1">
            Curate your seasonal wishlist and move pieces to your bag when you're ready.
          </p>
        </div>

        {wishlistProducts.length > 0 && (
          <button
            onClick={() => {
              clearWishlist();
              toast.info("Wishlist Cleared", "All items removed from your wishlist.");
            }}
            className="text-xs font-semibold text-neutral-500 hover:text-neutral-900 underline"
          >
            Clear Wishlist
          </button>
        )}
      </div>

      {wishlistProducts.length === 0 ? (
        <div className="py-20 text-center bg-white rounded-3xl border border-neutral-200 p-8 space-y-4">
          <div className="w-16 h-16 rounded-full bg-neutral-100 flex items-center justify-center mx-auto text-neutral-400">
            <Heart className="w-8 h-8" />
          </div>
          <h3 className="text-base font-bold text-neutral-900">Your wishlist is empty</h3>
          <p className="text-xs text-neutral-500 max-w-sm mx-auto">
            Browse our everyday collections and click the heart icon to save garments for later.
          </p>
          <Link
            href="/shop"
            className="inline-flex items-center gap-1.5 px-6 py-2.5 bg-neutral-900 text-white rounded-full text-xs font-bold hover:bg-neutral-800 transition-colors shadow-xs"
          >
            <span>Explore Essentials</span>
            <ArrowRight className="w-3.5 h-3.5" />
          </Link>
        </div>
      ) : (
        <div className="grid grid-cols-1 sm:grid-cols-2 md:grid-cols-3 gap-6">
          {wishlistProducts.map((product) => (
            <div
              key={product.id}
              className="bg-white rounded-3xl border border-neutral-200 overflow-hidden shadow-xs hover:border-neutral-300 transition-all flex flex-col justify-between group"
            >
              <div>
                <div className="relative aspect-3/4 w-full bg-neutral-100 overflow-hidden">
                  <Image
                    src={product.images[0]}
                    alt={product.name}
                    fill
                    className="object-cover group-hover:scale-105 transition-transform duration-500"
                  />
                  <button
                    onClick={() => removeItem(product.id)}
                    className="absolute top-3 right-3 p-2 rounded-full bg-white/90 text-neutral-500 hover:text-rose-600 hover:bg-white transition-colors shadow-xs"
                    aria-label="Remove item"
                  >
                    <Trash2 className="w-4 h-4" />
                  </button>
                </div>

                <div className="p-4 space-y-1">
                  <span className="text-[10px] uppercase font-bold text-neutral-400">
                    {product.category}
                  </span>
                  <Link
                    href={`/products/${product.slug}`}
                    className="text-xs font-bold text-neutral-900 hover:underline block truncate"
                  >
                    {product.name}
                  </Link>
                  <p className="text-xs font-black text-neutral-900">
                    {formatPrice(product.price)}
                  </p>
                </div>
              </div>

              <div className="p-4 pt-0">
                <button
                  onClick={() => handleMoveToBag(product)}
                  className="w-full h-11 bg-neutral-900 hover:bg-neutral-800 text-white rounded-full text-xs font-bold flex items-center justify-center gap-2 transition-colors shadow-xs"
                >
                  <ShoppingBag className="w-3.5 h-3.5" /> Move to Bag
                </button>
              </div>
            </div>
          ))}
        </div>
      )}
    </div>
  );
}

