"use client";

import React, { useState, useEffect } from "react";
import Image from "next/image";
import Link from "next/link";
import { Breadcrumb } from "@/components/ui/breadcrumb";
import { useCartStore } from "@/stores/cart-store";
import { formatPrice } from "@/lib/utils";
import { Plus, Minus, Trash2, ArrowRight, ShoppingBag, Sparkles, Tag, ShieldCheck, ArrowLeft } from "lucide-react";
import { APP_CONFIG } from "@/constants";
import { CartService } from "@/services/cart.service";
import { toast } from "@/components/ui/toast";

export default function CartPage() {
  const {
    items,
    removeItem,
    updateQuantity,
    clearCart,
    syncCart,
    getSubtotal,
    getDiscount,
    getShipping,
    getTax,
    getTotal,
    coupon,
    applyCoupon,
    removeCoupon,
    orderNotes,
    setOrderNotes,
  } = useCartStore();

  const [couponInput, setCouponInput] = useState("");
  const [loadingCoupon, setLoadingCoupon] = useState(false);

  useEffect(() => {
    syncCart();
  }, [syncCart]);

  const subtotal = getSubtotal();
  const discount = getDiscount();
  const shipping = getShipping();
  const tax = getTax();
  const total = getTotal();

  const freeShippingThreshold = APP_CONFIG.freeShippingThreshold;
  const freeShippingProgress = Math.min(100, (subtotal / freeShippingThreshold) * 100);
  const remainingForFreeShipping = Math.max(0, freeShippingThreshold - subtotal);

  const handleApplyPromo = async (e: React.FormEvent) => {
    e.preventDefault();
    if (!couponInput.trim()) return;

    try {
      setLoadingCoupon(true);
      const res = await CartService.applyCoupon(couponInput);
      applyCoupon(res.coupon);
      toast.success("Promo Code Applied", res.coupon.description);
      setCouponInput("");
    } catch (err: any) {
      toast.error("Invalid Code", err.message || "Please check the code and try again.");
    } finally {
      setLoadingCoupon(false);
    }
  };

  return (
    <div className="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8 py-8 sm:py-14 space-y-10">
      <Breadcrumb items={[{ label: "Home", href: "/" }, { label: "Shopping Bag" }]} />

      <div className="flex flex-col sm:flex-row sm:items-center justify-between gap-4 border-b border-neutral-200 pb-4">
        <div>
          <h1 className="text-2xl sm:text-4xl font-black text-neutral-900 tracking-tight">
            Shopping Bag
          </h1>
          <p className="text-xs text-neutral-500 mt-1">
            Review your selected wardrobe essentials before proceeding to checkout.
          </p>
        </div>
        <div className="flex items-center gap-3">
          {items.length > 0 && (
            <button
              onClick={() => {
                if (confirm("Are you sure you want to clear your shopping bag?")) {
                  clearCart();
                  toast.info("Bag Cleared", "All items were removed from your bag.");
                }
              }}
              className="text-xs text-rose-600 hover:text-rose-700 font-semibold px-3 py-1.5 rounded-full hover:bg-rose-50 transition border border-rose-200"
            >
              Clear Bag
            </button>
          )}
          <span className="text-xs font-semibold text-neutral-600 bg-neutral-100 px-3 py-1.5 rounded-full">
            {items.length} {items.length === 1 ? "Item" : "Items"}
          </span>
        </div>
      </div>

      {items.length === 0 ? (
        <div className="py-20 text-center rounded-3xl bg-white border border-neutral-200 p-8 space-y-4">
          <div className="w-16 h-16 rounded-full bg-neutral-100 flex items-center justify-center mx-auto text-neutral-400">
            <ShoppingBag className="w-8 h-8" />
          </div>
          <h3 className="text-lg font-bold text-neutral-900">Your bag is currently empty</h3>
          <p className="text-xs text-neutral-500 max-w-sm mx-auto">
            Explore our curated organic cotton tees, linen shirts, and comfortable everyday chinos.
          </p>
          <Link
            href="/shop"
            className="inline-flex items-center gap-2 px-8 py-3 rounded-full bg-neutral-900 text-white text-xs font-bold hover:bg-neutral-800 transition-colors shadow-sm"
          >
            Start Shopping
          </Link>
        </div>
      ) : (
        <div className="grid grid-cols-1 lg:grid-cols-12 gap-10 items-start">
          {/* Items Table List */}
          <div className="lg:col-span-8 space-y-6">
            {/* Free Shipping Meter */}
            <div className="p-4 rounded-2xl bg-neutral-100/70 border border-neutral-200/80 space-y-2">
              <div className="flex items-center justify-between text-xs">
                <span className="font-semibold text-neutral-900 flex items-center gap-1.5">
                  <Sparkles className="w-4 h-4 text-amber-500" />
                  {remainingForFreeShipping === 0 ? (
                    <span className="text-emerald-700 font-bold">
                      You qualify for complimentary Standard Ground Shipping!
                    </span>
                  ) : (
                    <span>
                      Add <strong>{formatPrice(remainingForFreeShipping)}</strong> more to unlock
                      FREE shipping.
                    </span>
                  )}
                </span>
                <span className="text-xs font-mono font-bold text-neutral-700">
                  {Math.round(freeShippingProgress)}%
                </span>
              </div>
              <div className="h-2 w-full bg-neutral-200 rounded-full overflow-hidden">
                <div
                  className="h-full bg-neutral-900 rounded-full transition-all duration-300"
                  style={{ width: `${freeShippingProgress}%` }}
                />
              </div>
            </div>

            {/* Items Card List */}
            <div className="bg-white rounded-3xl border border-neutral-200 divide-y divide-neutral-100 overflow-hidden shadow-xs">
              {items.map((item) => (
                <div key={item.id} className="p-5 sm:p-6 flex flex-col sm:flex-row gap-5 items-start sm:items-center">
                  <div className="relative w-20 h-26 sm:w-24 sm:h-30 rounded-2xl overflow-hidden bg-neutral-100 shrink-0 border border-neutral-100">
                    <Image src={item.image} alt={item.name} fill className="object-cover" />
                  </div>

                  <div className="flex-1 min-w-0 space-y-1">
                    <Link
                      href={`/products/${item.slug}`}
                      className="text-sm font-bold text-neutral-900 hover:underline block truncate"
                    >
                      {item.name}
                    </Link>
                    <div className="text-xs text-neutral-500 flex flex-wrap items-center gap-2">
                      <span>Color: <strong>{item.selectedColor}</strong></span>
                      <span>•</span>
                      <span>Size: <strong>{item.selectedSize}</strong></span>
                      {item.sku && (
                        <>
                          <span>•</span>
                          <span className="font-mono text-[11px] text-neutral-400">SKU: {item.sku}</span>
                        </>
                      )}
                    </div>
                    <div className="text-xs font-semibold text-neutral-900 pt-1">
                      {formatPrice(item.price)} each
                    </div>
                  </div>

                  {/* Quantity and Row Actions */}
                  <div className="flex items-center justify-between sm:justify-end gap-6 w-full sm:w-auto pt-2 sm:pt-0 border-t sm:border-t-0 border-neutral-100">
                    <div className="flex items-center border border-neutral-200 rounded-full px-2 py-1 bg-neutral-50">
                      <button
                        onClick={() => updateQuantity(item.id, item.quantity - 1)}
                        className="p-1 text-neutral-600 hover:text-neutral-950 transition-colors"
                        aria-label="Decrease"
                      >
                        <Minus className="w-3.5 h-3.5" />
                      </button>
                      <span className="w-8 text-center text-xs font-bold text-neutral-900">
                        {item.quantity}
                      </span>
                      <button
                        onClick={() => updateQuantity(item.id, item.quantity + 1)}
                        disabled={item.quantity >= item.maxStock}
                        className="p-1 text-neutral-600 hover:text-neutral-950 transition-colors disabled:opacity-40"
                        aria-label="Increase"
                      >
                        <Plus className="w-3.5 h-3.5" />
                      </button>
                    </div>

                    <div className="text-sm font-black text-neutral-900 w-20 text-right">
                      {formatPrice((item.subtotal && item.subtotal > 0) ? item.subtotal : item.price * item.quantity)}
                    </div>

                    <button
                      onClick={() => removeItem(item.id)}
                      className="p-2 text-neutral-400 hover:text-rose-600 transition-colors rounded-full hover:bg-neutral-100"
                      aria-label="Remove item"
                    >
                      <Trash2 className="w-4 h-4" />
                    </button>
                  </div>
                </div>
              ))}
            </div>

            {/* Bottom Actions */}
            <div className="flex items-center justify-between pt-2">
              <Link
                href="/shop"
                className="inline-flex items-center gap-2 text-xs font-bold text-neutral-700 hover:text-neutral-950 transition underline underline-offset-4"
              >
                <ArrowLeft className="w-3.5 h-3.5" />
                <span>Continue Shopping</span>
              </Link>
            </div>

            {/* Special Instructions / Notes */}
            <div className="bg-white rounded-3xl border border-neutral-200 p-5 space-y-2">
              <label className="text-xs font-bold uppercase tracking-wider text-neutral-700 block">
                Special Delivery Instructions or Gift Note
              </label>
              <textarea
                rows={2}
                placeholder="e.g. Please leave package behind front pillar, or gift wrap with card..."
                value={orderNotes}
                onChange={(e) => setOrderNotes(e.target.value)}
                className="w-full p-3 text-xs text-neutral-900 bg-neutral-50 border border-neutral-200 rounded-xl focus:outline-none focus:ring-1 focus:ring-neutral-900"
              />
            </div>
          </div>

          {/* Order Summary Column */}
          <div className="lg:col-span-4 bg-white rounded-3xl border border-neutral-200 p-6 space-y-6 shadow-xs sticky top-24">
            <h3 className="text-base font-black text-neutral-900 pb-3 border-b border-neutral-100">
              Order Summary
            </h3>

            {/* Promo Code Form */}
            {coupon ? (
              <div className="p-3 rounded-2xl bg-emerald-50 border border-emerald-200 flex items-center justify-between text-xs">
                <div className="flex items-center gap-2 text-emerald-800 font-semibold">
                  <Tag className="w-4 h-4" />
                  <span>{coupon.code} applied (-{formatPrice(discount)})</span>
                </div>
                <button
                  onClick={removeCoupon}
                  className="text-emerald-700 hover:text-emerald-900 font-bold underline text-[11px]"
                >
                  Remove
                </button>
              </div>
            ) : (
              <form onSubmit={handleApplyPromo} className="flex gap-2">
                <input
                  type="text"
                  placeholder="Promo Code"
                  value={couponInput}
                  onChange={(e) => setCouponInput(e.target.value)}
                  className="flex-1 text-xs px-3.5 py-2.5 bg-neutral-50 border border-neutral-200 rounded-xl uppercase tracking-wider focus:outline-none focus:ring-1 focus:ring-neutral-900"
                />
                <button
                  type="submit"
                  disabled={loadingCoupon || !couponInput.trim()}
                  className="px-4 py-2.5 bg-neutral-900 text-white text-xs font-bold rounded-xl hover:bg-neutral-800 disabled:opacity-50 transition-colors"
                >
                  {loadingCoupon ? "..." : "Apply"}
                </button>
              </form>
            )}

            {/* Line Item Prices */}
            <div className="space-y-3 text-xs text-neutral-600 pb-4 border-b border-neutral-100">
              <div className="flex justify-between">
                <span>Subtotal ({items.length} items)</span>
                <span className="font-semibold text-neutral-900">{formatPrice(subtotal)}</span>
              </div>
              {discount > 0 && (
                <div className="flex justify-between text-emerald-700 font-semibold">
                  <span>Coupon Discount</span>
                  <span>-{formatPrice(discount)}</span>
                </div>
              )}
              <div className="flex justify-between">
                <span>Estimated Shipping</span>
                <span className="font-semibold text-neutral-900">
                  {shipping === 0 ? "FREE" : formatPrice(shipping)}
                </span>
              </div>
              <div className="flex justify-between">
                <span>Estimated Sales Tax (8%)</span>
                <span className="font-semibold text-neutral-900">
                  {formatPrice(tax)}
                </span>
              </div>
            </div>

            {/* Total */}
            <div className="flex items-baseline justify-between text-lg font-black text-neutral-900">
              <span>Total</span>
              <span>{formatPrice(total)}</span>
            </div>

            {/* Checkout Action Button */}
            <Link
              href="/checkout"
              className="w-full flex items-center justify-center gap-2 h-13 bg-neutral-950 text-white rounded-full font-bold text-xs uppercase tracking-wider hover:bg-neutral-800 transition-all shadow-md active:scale-98"
            >
              <span>Proceed to Checkout</span>
              <ArrowRight className="w-4 h-4" />
            </Link>

            <div className="flex items-center justify-center gap-2 text-[11px] text-neutral-400">
              <ShieldCheck className="w-3.5 h-3.5 text-emerald-600" />
              <span>256-bit encrypted checkout guaranteed</span>
            </div>
          </div>
        </div>
      )}
    </div>
  );
}
