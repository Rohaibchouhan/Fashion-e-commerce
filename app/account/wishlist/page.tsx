"use client";

import React, { useState, useEffect } from "react";
import Link from "next/link";
import Image from "next/image";
import { useWishlistStore } from "@/stores/wishlist-store";
import { useCartStore } from "@/stores/cart-store";
import { WishlistService } from "@/services/wishlist.service";
import { Product } from "@/types/product";
import { formatPrice } from "@/lib/utils";
import { toast } from "@/components/ui/toast";
import { Heart, ShoppingBag, Trash2, ArrowRight } from "lucide-react";

export default function AccountWishlistPage() {
  const { items, removeItem, clearWishlist } = useWishlistStore();
  const { addItem, openCart } = useCartStore();

  const [wishlistProducts, setWishlistProducts] = useState<Product[]>([]);
  const [isLoading, setIsLoading] = useState(true);

  useEffect(() => {
    let isMounted = true;
    async function loadWishlist() {
      try {
        const products = await WishlistService.getItems(items);
        if (isMounted) setWishlistProducts(products);
      } catch (err) {
        console.error("Failed to load wishlist items:", err);
      } finally {
        if (isMounted) setIsLoading(false);
      }
    }
    loadWishlist();
    return () => {
      isMounted = false;
    };
  }, [items]);

  const handleMoveToBag = (product: Product) => {
    const defaultSize = product.sizes?.find((s) => s.inStock)?.name || product.sizes?.[0]?.name || "M";

    addItem({
      productId: product.id,
      slug: product.slug,
      name: product.name,
      price: product.price,
      originalPrice: product.compareAtPrice,
      image: product.images?.[0] || "",
      selectedColor: product.colors?.[0]?.name || "Standard",
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
            className="text-xs font-semibold text-neutral-500 hover:text-neutral-900 underline cursor-pointer"
          >
            Clear Wishlist
          </button>
        )}
      </div>

      {isLoading ? (
        <div className="py-24 text-center bg-white rounded-3xl border border-neutral-200 p-8">
          <p className="text-xs text-neutral-400">Loading your wishlist...</p>
        </div>
      ) : wishlistProducts.length === 0 ? (
        <div className="py-20 text-center bg-white rounded-3xl border border-neutral-200 p-8 space-y-4">
          <div className="w-14 h-14 rounded-full bg-neutral-100 flex items-center justify-center mx-auto text-neutral-400">
            <Heart className="w-6 h-6" />
          </div>
          <h3 className="text-base font-bold text-neutral-900">Your wishlist is empty</h3>
          <p className="text-xs text-neutral-500 max-w-sm mx-auto">
            Explore our collections and click the heart icon on any product to save it here for later.
          </p>
          <Link
            href="/shop"
            className="inline-flex items-center gap-1.5 px-6 py-2.5 bg-neutral-900 text-white text-xs font-bold rounded-full hover:bg-neutral-800 transition-colors shadow-xs"
          >
            <span>Explore Collection</span>
            <ArrowRight className="w-3.5 h-3.5" />
          </Link>
        </div>
      ) : (
        <div className="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-3 gap-6">
          {wishlistProducts.map((product) => (
            <div
              key={product.id}
              className="bg-white rounded-3xl border border-neutral-200 overflow-hidden shadow-xs hover:shadow-md transition-all flex flex-col justify-between group"
            >
              <div>
                <div className="relative aspect-3/4 w-full bg-neutral-100 overflow-hidden">
                  {product.images?.[0] ? (
                    <Image
                      src={product.images[0]}
                      alt={product.name}
                      fill
                      className="object-cover group-hover:scale-103 transition-transform duration-500"
                    />
                  ) : (
                    <div className="w-full h-full flex items-center justify-center text-xs text-neutral-400">
                      WD
                    </div>
                  )}
                  <button
                    onClick={() => removeItem(product.id)}
                    className="absolute top-3 right-3 p-2 rounded-full bg-white/90 backdrop-blur-xs text-neutral-400 hover:text-rose-600 transition-colors shadow-xs"
                    aria-label="Remove from wishlist"
                  >
                    <Trash2 className="w-4 h-4" />
                  </button>
                </div>

                <div className="p-5 space-y-1">
                  <span className="text-[10px] font-bold uppercase tracking-wider text-neutral-400">
                    {product.category || "Apparel"}
                  </span>
                  <Link href={`/products/${product.slug}`}>
                    <h3 className="text-sm font-bold text-neutral-900 hover:underline line-clamp-1">
                      {product.name}
                    </h3>
                  </Link>
                  <div className="flex items-center gap-2 pt-1">
                    <span className="text-sm font-black text-neutral-900">
                      {formatPrice(product.price)}
                    </span>
                    {product.compareAtPrice && product.compareAtPrice > product.price && (
                      <span className="text-xs text-neutral-400 line-through">
                        {formatPrice(product.compareAtPrice)}
                      </span>
                    )}
                  </div>
                </div>
              </div>

              <div className="p-5 pt-0">
                <button
                  onClick={() => handleMoveToBag(product)}
                  className="w-full py-2.5 bg-neutral-900 hover:bg-neutral-800 text-white text-xs font-bold rounded-xl transition-colors flex items-center justify-center gap-2 shadow-xs cursor-pointer"
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
