"use client";

import React, { useState } from "react";
import Image from "next/image";
import Link from "next/link";
import { Drawer } from "@/components/ui/drawer";
import { useCartStore } from "@/stores/cart-store";
import { formatPrice } from "@/lib/utils";
import { Plus, Minus, Trash2, ArrowRight, ShoppingBag, Sparkles, Tag, Check } from "lucide-react";
import { APP_CONFIG } from "@/constants";
import { CartService } from "@/services/cart.service";
import { toast } from "@/components/ui/toast";

export const MiniCartDrawer: React.FC = () => {
  const {
    items,
    isOpen,
    closeCart,
    removeItem,
    updateQuantity,
    getSubtotal,
    getDiscount,
    getShipping,
    getTotal,
    coupon,
    applyCoupon,
    removeCoupon,
  } = useCartStore();

  const [couponCode, setCouponCode] = useState("");
  const [couponLoading, setCouponLoading] = useState(false);

  const subtotal = getSubtotal();
  const discount = getDiscount();
  const shipping = getShipping();
  const total = getTotal();

  const freeShippingThreshold = APP_CONFIG.freeShippingThreshold;
  const freeShippingProgress = Math.min(100, (subtotal / freeShippingThreshold) * 100);
  const remainingForFreeShipping = Math.max(0, freeShippingThreshold - subtotal);

  const handleApplyCoupon = async (e: React.FormEvent) => {
    e.preventDefault();
    if (!couponCode.trim()) return;

    try {
      setCouponLoading(true);
      const res = await CartService.applyCoupon(couponCode);
      applyCoupon(res.coupon);
      toast.success("Coupon Applied!", `${res.coupon.description}`);
      setCouponCode("");
    } catch (err: any) {
      toast.error("Invalid Promo Code", err.message || "Please check the code and try again.");
    } finally {
      setCouponLoading(false);
    }
  };

  return (
    <Drawer
      isOpen={isOpen}
      onClose={closeCart}
      width="md"
      title={
        <div className="flex items-center gap-2">
          <ShoppingBag className="w-5 h-5 text-neutral-900" />
          <span>Shopping Bag ({items.length})</span>
        </div>
      }
      footer={
        items.length > 0 ? (
          <div className="space-y-4">
            {/* Promo Code Input */}
            {coupon ? (
              <div className="flex items-center justify-between p-2.5 rounded-xl bg-emerald-50 border border-emerald-200 text-xs">
                <div className="flex items-center gap-2 text-emerald-800 font-semibold">
                  <Tag className="w-3.5 h-3.5" />
                  <span>{coupon.code} applied (-{formatPrice(discount)})</span>
                </div>
                <button
                  onClick={removeCoupon}
                  className="text-emerald-700 hover:text-emerald-900 font-bold underline"
                >
                  Remove
                </button>
              </div>
            ) : (
              <form onSubmit={handleApplyCoupon} className="flex gap-2">
                <input
                  type="text"
                  placeholder="Promo code (e.g. WEARDAY15)"
                  value={couponCode}
                  onChange={(e) => setCouponCode(e.target.value)}
                  className="flex-1 text-xs px-3.5 py-2.5 bg-white border border-neutral-300 rounded-xl uppercase tracking-wider focus:outline-none focus:ring-1 focus:ring-neutral-900"
                />
                <button
                  type="submit"
                  disabled={couponLoading || !couponCode.trim()}
                  className="px-4 py-2.5 bg-neutral-900 text-white text-xs font-semibold rounded-xl hover:bg-neutral-800 disabled:opacity-50"
                >
                  {couponLoading ? "..." : "Apply"}
                </button>
              </form>
            )}

            {/* Price Breakdown */}
            <div className="space-y-1.5 text-xs text-neutral-600">
              <div className="flex justify-between">
                <span>Subtotal</span>
                <span className="font-semibold text-neutral-900">{formatPrice(subtotal)}</span>
              </div>
              {discount > 0 && (
                <div className="flex justify-between text-emerald-700 font-medium">
                  <span>Discount</span>
                  <span>-{formatPrice(discount)}</span>
                </div>
              )}
              <div className="flex justify-between">
                <span>Shipping</span>
                <span>{shipping === 0 ? "FREE" : formatPrice(shipping)}</span>
              </div>
              <div className="flex justify-between text-sm font-bold text-neutral-900 pt-2 border-t border-neutral-200">
                <span>Total</span>
                <span>{formatPrice(total)}</span>
              </div>
            </div>

            {/* Checkout Action Button */}
            <div className="space-y-2 pt-1">
              <Link
                href="/checkout"
                onClick={closeCart}
                className="w-full flex items-center justify-center gap-2 h-12 bg-neutral-950 text-white rounded-full font-semibold text-sm hover:bg-neutral-800 transition-all shadow-md active:scale-[0.99]"
              >
                <span>Checkout</span>
                <ArrowRight className="w-4 h-4" />
              </Link>
              <Link
                href="/cart"
                onClick={closeCart}
                className="w-full flex items-center justify-center h-10 border border-neutral-300 bg-white text-neutral-800 rounded-full font-medium text-xs hover:bg-neutral-50 transition-colors"
              >
                View Full Bag & Estimates
              </Link>
            </div>
          </div>
        ) : null
      }
    >
      {items.length === 0 ? (
        <div className="py-16 text-center space-y-4">
          <div className="w-16 h-16 rounded-full bg-neutral-100 mx-auto flex items-center justify-center text-neutral-400">
            <ShoppingBag className="w-8 h-8" />
          </div>
          <div>
            <h3 className="text-base font-bold text-neutral-900">Your bag is empty</h3>
            <p className="text-xs text-neutral-500 mt-1 max-w-xs mx-auto">
              Explore our everyday minimalist staples and make yourself at home.
            </p>
          </div>
          <Link
            href="/shop"
            onClick={closeCart}
            className="inline-flex items-center gap-2 px-6 py-2.5 rounded-full bg-neutral-900 text-white text-xs font-semibold hover:bg-neutral-800 transition-colors"
          >
            Explore Collection
          </Link>
        </div>
      ) : (
        <div className="space-y-4">
          {/* Free Shipping Meter */}
          <div className="p-3.5 rounded-2xl bg-neutral-50 border border-neutral-100 space-y-2">
            <div className="flex items-center justify-between text-xs">
              <span className="font-semibold text-neutral-800 flex items-center gap-1.5">
                <Sparkles className="w-3.5 h-3.5 text-amber-500" />
                {remainingForFreeShipping === 0 ? (
                  <span className="text-emerald-700 font-bold">You unlocked FREE shipping!</span>
                ) : (
                  <span>
                    Add <strong>{formatPrice(remainingForFreeShipping)}</strong> for Free Shipping
                  </span>
                )}
              </span>
              <span className="text-[11px] text-neutral-500 font-mono">
                {Math.round(freeShippingProgress)}%
              </span>
            </div>
            <div className="h-1.5 w-full bg-neutral-200 rounded-full overflow-hidden">
              <div
                className="h-full bg-neutral-900 rounded-full transition-all duration-300"
                style={{ width: `${freeShippingProgress}%` }}
              />
            </div>
          </div>

          {/* Cart Item Cards */}
          <div className="divide-y divide-neutral-100">
            {items.map((item) => (
              <div key={item.id} className="py-4 flex gap-4">
                <div className="relative w-20 h-24 rounded-2xl overflow-hidden bg-neutral-100 shrink-0 border border-neutral-100">
                  <Image src={item.image} alt={item.name} fill className="object-cover" />
                </div>
                <div className="flex-1 min-w-0 flex flex-col justify-between">
                  <div>
                    <div className="flex items-start justify-between gap-1">
                      <Link
                        href={`/products/${item.slug}`}
                        onClick={closeCart}
                        className="text-xs font-bold text-neutral-900 hover:underline truncate"
                      >
                        {item.name}
                      </Link>
                      <button
                        onClick={() => removeItem(item.id)}
                        className="text-neutral-400 hover:text-rose-600 transition-colors p-1"
                        aria-label="Remove item"
                      >
                        <Trash2 className="w-3.5 h-3.5" />
                      </button>
                    </div>

                    <div className="flex flex-wrap items-center gap-1.5 text-[11px] text-neutral-500 mt-1">
                      <span>Color: {item.selectedColor}</span>
                      <span>•</span>
                      <span>Size: {item.selectedSize}</span>
                      {item.sku && (
                        <>
                          <span>•</span>
                          <span className="font-mono text-[10px] text-neutral-400">SKU: {item.sku}</span>
                        </>
                      )}
                    </div>

                    <div className="text-xs font-semibold text-neutral-900 mt-1">
                      {formatPrice(item.price)}
                    </div>
                  </div>

                  {/* Quantity Stepper */}
                  <div className="flex items-center justify-between pt-2">
                    <div className="flex items-center border border-neutral-200 rounded-full px-2 py-0.5 bg-neutral-50">
                      <button
                        onClick={() => updateQuantity(item.id, item.quantity - 1)}
                        className="p-1 text-neutral-600 hover:text-neutral-950 transition-colors"
                        aria-label="Decrease quantity"
                      >
                        <Minus className="w-3 h-3" />
                      </button>
                      <span className="w-8 text-center text-xs font-semibold text-neutral-900">
                        {item.quantity}
                      </span>
                      <button
                        onClick={() => updateQuantity(item.id, item.quantity + 1)}
                        disabled={item.quantity >= item.maxStock}
                        className="p-1 text-neutral-600 hover:text-neutral-950 transition-colors disabled:opacity-40"
                        aria-label="Increase quantity"
                      >
                        <Plus className="w-3 h-3" />
                      </button>
                    </div>

                    <span className="text-xs font-bold text-neutral-900">
                      {formatPrice(item.price * item.quantity)}
                    </span>
                  </div>
                </div>
              </div>
            ))}
          </div>
        </div>
      )}
    </Drawer>
  );
};

