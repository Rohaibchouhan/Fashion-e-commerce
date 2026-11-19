"use client";

import React, { useState } from "react";
import Image from "next/image";
import Link from "next/link";
import { Modal } from "@/components/ui/modal";
import { useUIStore } from "@/stores/ui-store";
import { useCartStore } from "@/stores/cart-store";
import { useWishlistStore } from "@/stores/wishlist-store";
import { formatPrice } from "@/lib/utils";
import { Heart, ShoppingBag, Check, ArrowRight, Star } from "lucide-react";
import { toast } from "@/components/ui/toast";

export const QuickViewModal: React.FC = () => {
  const { quickViewProduct, closeQuickView } = useUIStore();
  const { addItem, openCart } = useCartStore();
  const { toggleItem, hasItem } = useWishlistStore();

  const [selectedColorIdx, setSelectedColorIdx] = useState(0);
  const [selectedSize, setSelectedSize] = useState<string>("");
  const [activeImageIdx, setActiveImageIdx] = useState(0);
  const [quantity, setQuantity] = useState(1);

  if (!quickViewProduct) return null;

  const product = quickViewProduct;
  const isWishlisted = hasItem(product.id);
  const activeColor = product.colors[selectedColorIdx] || product.colors[0];
  const activeSize = selectedSize || product.sizes.find((s) => s.inStock)?.name || product.sizes[0].name;

  const handleAddToCart = () => {
    addItem({
      productId: product.id,
      slug: product.slug,
      name: product.name,
      price: product.price,
      originalPrice: product.compareAtPrice,
      image: product.images[activeImageIdx] || product.images[0],
      selectedColor: activeColor.name,
      selectedSize: activeSize,
      quantity,
      maxStock: 20,
    });

    closeQuickView();
    toast.success("Added to Bag", `${product.name} (${activeSize}) added.`);
    openCart();
  };

  return (
    <Modal isOpen={!!quickViewProduct} onClose={closeQuickView} maxWidth="3xl">
      <div className="grid grid-cols-1 md:grid-cols-2 gap-8 items-start">
        {/* Images Column */}
        <div className="space-y-3">
          <div className="relative aspect-3/4 rounded-2xl overflow-hidden bg-neutral-100 border border-neutral-200/60">
            <Image
              src={product.images[activeImageIdx] || product.images[0]}
              alt={product.name}
              fill
              className="object-cover"
            />
          </div>
          {product.images.length > 1 && (
            <div className="flex gap-2">
              {product.images.map((img, i) => (
                <button
                  key={i}
                  onClick={() => setActiveImageIdx(i)}
                  className={`relative w-16 h-20 rounded-xl overflow-hidden border-2 transition-all ${
                    activeImageIdx === i ? "border-neutral-900" : "border-transparent opacity-70"
                  }`}
                >
                  <Image src={img} alt="Thumbnail" fill className="object-cover" />
                </button>
              ))}
            </div>
          )}
        </div>

        {/* Info Column */}
        <div className="space-y-5">
          <div>
            <span className="text-[10px] font-bold uppercase tracking-wider text-neutral-400">
              {product.category} • {product.gender.toUpperCase()}
            </span>
            <h2 className="text-xl font-black text-neutral-900 tracking-tight mt-1">
              {product.name}
            </h2>
            <div className="flex items-center gap-3 mt-2">
              <span className="text-lg font-black text-neutral-900">
                {formatPrice(product.price)}
              </span>
              {product.compareAtPrice && product.compareAtPrice > product.price && (
                <span className="text-sm text-neutral-400 line-through">
                  {formatPrice(product.compareAtPrice)}
                </span>
              )}
              <div className="flex items-center gap-1 text-xs text-neutral-600 ml-auto">
                {product.reviewCount > 0 ? (
                  <>
                    <Star className="w-3.5 h-3.5 fill-amber-400 text-amber-400" />
                    <span className="font-bold">{product.rating.toFixed(1)}</span>
                    <span className="text-neutral-400">({product.reviewCount})</span>
                  </>
                ) : (
                  <span className="text-neutral-400 text-[11px]">0 reviews</span>
                )}
              </div>
            </div>
          </div>

          <p className="text-xs text-neutral-600 leading-relaxed line-clamp-3">
            {product.description}
          </p>

          {/* Color Selection */}
          <div className="space-y-2">
            <div className="flex justify-between text-xs">
              <span className="font-semibold text-neutral-900">Color:</span>
              <span className="text-neutral-500">{activeColor.name}</span>
            </div>
            <div className="flex gap-2">
              {product.colors.map((c, i) => (
                <button
                  key={c.name}
                  onClick={() => setSelectedColorIdx(i)}
                  className={`w-7 h-7 rounded-full border transition-all ${
                    selectedColorIdx === i
                      ? "ring-2 ring-neutral-900 ring-offset-2 border-white scale-105"
                      : "border-black/20 hover:scale-105"
                  }`}
                  style={{ backgroundColor: c.hex }}
                  title={c.name}
                />
              ))}
            </div>
          </div>

          {/* Size Selection */}
          <div className="space-y-2">
            <div className="flex justify-between text-xs">
              <span className="font-semibold text-neutral-900">Size:</span>
              <span className="text-neutral-500">{activeSize}</span>
            </div>
            <div className="flex flex-wrap gap-2">
              {product.sizes.map((s) => (
                <button
                  key={s.name}
                  disabled={!s.inStock}
                  onClick={() => setSelectedSize(s.name)}
                  className={`px-4 py-2 text-xs font-bold rounded-xl border transition-all cursor-pointer ${
                    !s.inStock
                      ? "opacity-30 border-dashed line-through cursor-not-allowed bg-neutral-100"
                      : activeSize === s.name
                      ? "bg-neutral-900 text-white border-neutral-900"
                      : "bg-white text-neutral-800 border-neutral-200 hover:border-neutral-400"
                  }`}
                >
                  {s.name}
                </button>
              ))}
            </div>
          </div>

          {/* Add to Cart & Wishlist Actions */}
          <div className="flex gap-3 pt-3">
            <button
              onClick={handleAddToCart}
              className="flex-1 h-12 bg-neutral-900 hover:bg-neutral-800 text-white rounded-full font-bold text-xs flex items-center justify-center gap-2 shadow-md transition-all active:scale-98 cursor-pointer"
            >
              <ShoppingBag className="w-4 h-4" /> Add to Bag • {formatPrice(product.price * quantity)}
            </button>
            <button
              onClick={() => toggleItem(product.id)}
              className={`w-12 h-12 rounded-full border flex items-center justify-center transition-all ${
                isWishlisted
                  ? "bg-rose-50 border-rose-200 text-rose-600"
                  : "border-neutral-200 hover:bg-neutral-50 text-neutral-700"
              }`}
            >
              <Heart className={`w-5 h-5 ${isWishlisted ? "fill-rose-600" : ""}`} />
            </button>
          </div>

          <div className="text-center pt-2">
            <Link
              href={`/products/${product.slug}`}
              onClick={closeQuickView}
              className="inline-flex items-center gap-1 text-xs font-semibold text-neutral-600 hover:text-neutral-950 underline underline-offset-4"
            >
              View Full Product Specifications <ArrowRight className="w-3.5 h-3.5" />
            </Link>
          </div>
        </div>
      </div>
    </Modal>
  );
};

