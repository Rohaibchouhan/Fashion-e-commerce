"use client";

import React, { useState } from "react";
import Image from "next/image";
import Link from "next/link";
import { Heart, ShoppingBag, Eye } from "lucide-react";
import { Product } from "@/types/product";
import { formatPrice } from "@/lib/utils";
import { useWishlistStore } from "@/stores/wishlist-store";
import { useCartStore } from "@/stores/cart-store";
import { useUIStore } from "@/stores/ui-store";
import { toast } from "@/components/ui/toast";
import { cn } from "@/lib/utils";

interface ProductCardProps {
  product: Product;
  className?: string;
}

export const ProductCard: React.FC<ProductCardProps> = ({ product, className }) => {
  const [selectedColorIdx, setSelectedColorIdx] = useState(0);
  const { toggleItem, hasItem } = useWishlistStore();
  const { addItem } = useCartStore();
  const { openQuickView } = useUIStore();

  const isWishlisted = hasItem(product.id);
  const activeColor = product.colors[selectedColorIdx] || product.colors[0];

  const handleWishlistToggle = (e: React.MouseEvent) => {
    e.preventDefault();
    e.stopPropagation();
    const added = toggleItem(product.id);
    if (added) {
      toast.success("Added to Wishlist", `${product.name} saved to your wishlist.`);
    } else {
      toast.info("Removed from Wishlist", `${product.name} removed from your wishlist.`);
    }
  };

  const handleQuickAdd = (e: React.MouseEvent) => {
    e.preventDefault();
    e.stopPropagation();

    const defaultSize = product.sizes.find((s) => s.inStock)?.name || product.sizes[0].name;

    addItem({
      productId: product.id,
      slug: product.slug,
      name: product.name,
      price: product.price,
      originalPrice: product.compareAtPrice,
      image: product.images[0],
      selectedColor: activeColor?.name || "Standard",
      selectedSize: defaultSize,
      quantity: 1,
      maxStock: 20,
    });

    toast.success("Added to Bag", `${product.name} (${defaultSize}) added to your bag.`);
  };

  const discountPercent =
    product.compareAtPrice && product.compareAtPrice > product.price
      ? Math.round(((product.compareAtPrice - product.price) / product.compareAtPrice) * 100)
      : null;

  return (
    <div className={cn("group flex flex-col relative", className)}>
      {/* Image Container with Badges & Actions */}
      <div className="relative aspect-3/4 w-full overflow-hidden rounded-2xl bg-neutral-100 border border-neutral-200/60 transition-all duration-300 group-hover:shadow-md">
        <Link href={`/products/${product.slug}`} className="block w-full h-full relative">
          <Image
            src={product.images[0]}
            alt={product.name}
            fill
            sizes="(max-width: 640px) 50vw, (max-width: 1024px) 33vw, 25vw"
            className="object-cover object-center group-hover:scale-105 transition-transform duration-500 ease-out"
          />
        </Link>

        {/* Badges: Sale, New */}
        <div className="absolute top-3 left-3 flex flex-col gap-1 z-10 pointer-events-none">
          {product.isSale && discountPercent && (
            <span className="px-2 py-0.5 text-[10px] font-bold uppercase tracking-wider bg-rose-600 text-white rounded-md shadow-xs">
              Save {discountPercent}%
            </span>
          )}
          {product.isNew && !product.isSale && (
            <span className="px-2 py-0.5 text-[10px] font-bold uppercase tracking-wider bg-neutral-900 text-white rounded-md shadow-xs">
              New In
            </span>
          )}
        </div>

        {/* Wishlist Button */}
        <button
          onClick={handleWishlistToggle}
          className={cn(
            "absolute top-3 right-3 p-2 rounded-full transition-all duration-200 z-10 shadow-xs cursor-pointer",
            isWishlisted
              ? "bg-rose-50 text-rose-600 hover:scale-110"
              : "bg-white/90 text-neutral-600 hover:text-neutral-950 hover:bg-white hover:scale-110"
          )}
          aria-label={isWishlisted ? "Remove from wishlist" : "Add to wishlist"}
        >
          <Heart className={cn("w-4 h-4", isWishlisted && "fill-rose-600")} />
        </button>

        {/* Hover Action Bar */}
        <div className="absolute bottom-3 left-3 right-3 hidden sm:flex items-center gap-2 opacity-0 group-hover:opacity-100 transition-opacity duration-200 z-10">
          <button
            onClick={handleQuickAdd}
            className="flex-1 h-9 rounded-full bg-white/95 backdrop-blur-xs text-neutral-900 text-xs font-semibold hover:bg-neutral-900 hover:text-white transition-all shadow-md flex items-center justify-center gap-1.5 cursor-pointer active:scale-98"
          >
            <ShoppingBag className="w-3.5 h-3.5" /> Quick Add
          </button>
          <button
            onClick={(e) => {
              e.preventDefault();
              e.stopPropagation();
              openQuickView(product);
            }}
            className="w-9 h-9 rounded-full bg-white/95 backdrop-blur-xs text-neutral-900 hover:bg-neutral-900 hover:text-white transition-all shadow-md flex items-center justify-center shrink-0 cursor-pointer"
            aria-label="Quick View"
          >
            <Eye className="w-4 h-4" />
          </button>
        </div>
      </div>

      {/* Product Information */}
      <div className="pt-3 pb-1 flex flex-col flex-1">
        <Link href={`/products/${product.slug}`} className="block group-hover:text-neutral-700">
          <h3 className="text-sm font-semibold text-neutral-900 truncate tracking-tight">
            {product.name}
          </h3>
        </Link>

        {/* Pricing */}
        <div className="flex items-center gap-2 mt-1">
          <span className="text-xs font-bold text-neutral-900">
            {formatPrice(product.price)}
          </span>
          {product.compareAtPrice && product.compareAtPrice > product.price && (
            <span className="text-xs text-neutral-400 line-through">
              {formatPrice(product.compareAtPrice)}
            </span>
          )}
        </div>

        {/* Color Swatch Dots */}
        {product.colors && product.colors.length > 0 && (
          <div className="flex items-center gap-1.5 mt-2.5">
            {product.colors.map((color, idx) => (
              <button
                key={color.name}
                onClick={(e) => {
                  e.preventDefault();
                  e.stopPropagation();
                  setSelectedColorIdx(idx);
                }}
                title={color.name}
                className={cn(
                  "w-3.5 h-3.5 rounded-full transition-all border cursor-pointer",
                  selectedColorIdx === idx
                    ? "ring-2 ring-neutral-900 ring-offset-1 border-white"
                    : "border-black/10 hover:scale-110"
                )}
                style={{ backgroundColor: color.hex }}
              />
            ))}
          </div>
        )}
      </div>
    </div>
  );
};

