"use client";

import React, { useState } from "react";
import Image from "next/image";
import Link from "next/link";
import { useRouter } from "next/navigation";
import { Breadcrumb } from "@/components/ui/breadcrumb";
import { useCartStore } from "@/stores/cart-store";
import { useAuthStore } from "@/stores/auth-store";
import { formatPrice } from "@/lib/utils";
import { Input } from "@/components/ui/input";
import { Button } from "@/components/ui/button";
import { toast } from "@/components/ui/toast";
import { SHIPPING_METHODS } from "@/constants";
import { CheckoutService } from "@/services/checkout.service";
import { ShieldCheck, Lock, CreditCard, CheckCircle2, User, Truck, Check, AlertCircle } from "lucide-react";

export default function CheckoutPage() {
  const router = useRouter();
  const { items, getSubtotal, getDiscount, coupon, clearCart } = useCartStore();
  const { user, isAuthenticated, login, register } = useAuthStore();

  const [authMode, setAuthMode] = useState<"logged_in" | "new_account" | "sign_in">(
    isAuthenticated ? "logged_in" : "new_account"
  );

  // Auth fields for inline registration
  const [authEmail, setAuthEmail] = useState("");
  const [authName, setAuthName] = useState("");
  const [authPassword, setAuthPassword] = useState("");

  // Shipping Address
  const [shippingAddress, setShippingAddress] = useState({
    firstName: user?.name.split(" ")[0] || "Alex",
    lastName: user?.name.split(" ")[1] || "Morgan",
    street: user?.addresses[0]?.street || "742 Evergreen Terrace",
    apartment: user?.addresses[0]?.apartment || "Apt 4B",
    city: user?.addresses[0]?.city || "Brooklyn",
    state: user?.addresses[0]?.state || "NY",
    zipCode: user?.addresses[0]?.zipCode || "11201",
    country: "United States",
    phone: user?.phone || "+1 (555) 019-2834",
  });

  const [selectedShippingMethod, setSelectedShippingMethod] = useState("standard");
  const [paymentType, setPaymentType] = useState<"card" | "apple_pay" | "paypal">("card");
  const [cardDetails, setCardDetails] = useState({
    cardNumber: "4242 •••• •••• 4242",
    exp: "12/28",
    cvv: "382",
    nameOnCard: "Alex Morgan",
  });

  const [isSubmitting, setIsSubmitting] = useState(false);

  const subtotal = getSubtotal();
  const discount = getDiscount();
  const shippingMethod = SHIPPING_METHODS.find((s) => s.id === selectedShippingMethod) || SHIPPING_METHODS[0];
  const shippingCost = subtotal >= 50 && selectedShippingMethod === "standard" ? 0 : shippingMethod.price;
  const tax = (subtotal - discount) * 0.08;
  const grandTotal = Math.max(0, subtotal - discount + shippingCost + tax);

  if (items.length === 0) {
    return (
      <div className="max-w-xl mx-auto px-4 py-20 text-center space-y-4">
        <h2 className="text-xl font-bold text-neutral-900">Your bag is empty</h2>
        <p className="text-xs text-neutral-500">Add pieces to your bag before checking out.</p>
        <Link
          href="/shop"
          className="inline-block px-6 py-2.5 bg-neutral-900 text-white rounded-full text-xs font-semibold"
        >
          Return to Shop
        </Link>
      </div>
    );
  }

  const handlePlaceOrder = async (e: React.FormEvent) => {
    e.preventDefault();

    // Enforce No Guest Checkout rule
    if (!isAuthenticated) {
      if (authMode === "new_account") {
        if (!authEmail || !authName) {
          toast.error("Account Required", "Guest checkout is unavailable. Please enter your name and email to create your account.");
          return;
        }
        // Auto create account
        register(authName, authEmail);
      } else if (authMode === "sign_in") {
        if (!authEmail) {
          toast.error("Sign In Required", "Please enter your email to proceed.");
          return;
        }
        login(authEmail);
      }
    }

    try {
      setIsSubmitting(true);
      const newOrder = await CheckoutService.processOrder({
        items,
        shippingAddress: {
          id: "addr-checkout",
          ...shippingAddress,
        },
        billingAddress: {
          id: "addr-checkout",
          ...shippingAddress,
        },
        shippingMethodId: selectedShippingMethod,
        paymentMethod: {
          type: paymentType,
          cardNumber: cardDetails.cardNumber,
        },
        subtotal,
        discount,
        shipping: shippingCost,
        tax,
        total: grandTotal,
      });

      clearCart();
      toast.success("Order Placed Successfully!", `Order #${newOrder.orderNumber} confirmed.`);
      router.push(`/order-confirmation/${newOrder.id}`);
    } catch (err: any) {
      toast.error("Checkout Failed", err.message || "Unable to place order.");
    } finally {
      setIsSubmitting(false);
    }
  };

  return (
    <div className="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8 py-8 sm:py-12 space-y-8">
      <Breadcrumb
        items={[
          { label: "Home", href: "/" },
          { label: "Shopping Bag", href: "/cart" },
          { label: "Secure Checkout" },
        ]}
      />

      <div className="border-b border-neutral-200 pb-4 flex items-center justify-between">
        <div>
          <h1 className="text-2xl sm:text-3xl font-black text-neutral-900 tracking-tight">
            Checkout
          </h1>
          <p className="text-xs text-neutral-500 mt-0.5">
            Complete your order details below.
          </p>
        </div>
        <div className="flex items-center gap-2 text-xs font-semibold text-emerald-700 bg-emerald-50 px-3 py-1.5 rounded-full border border-emerald-200">
          <Lock className="w-3.5 h-3.5" />
          <span>256-Bit SSL Encrypted</span>
        </div>
      </div>

      <form onSubmit={handlePlaceOrder} className="grid grid-cols-1 lg:grid-cols-12 gap-10 items-start">
        {/* Left Form: Authentication, Shipping Address, Shipping Method, Payment */}
        <div className="lg:col-span-7 space-y-8">
          {/* 1. Account Section (Guest checkout disabled) */}
          <div className="bg-white rounded-3xl border border-neutral-200 p-6 space-y-4 shadow-xs">
            <div className="flex items-center justify-between">
              <div className="flex items-center gap-2">
                <span className="w-6 h-6 rounded-full bg-neutral-900 text-white text-xs font-bold flex items-center justify-center">
                  1
                </span>
                <h3 className="text-sm font-bold text-neutral-900">Customer Account</h3>
              </div>
              <span className="text-[11px] font-semibold text-neutral-400">
                Guest checkout not permitted
              </span>
            </div>

            {isAuthenticated ? (
              <div className="p-3.5 rounded-2xl bg-neutral-50 border border-neutral-200 flex items-center justify-between text-xs">
                <div className="flex items-center gap-2.5">
                  <User className="w-4 h-4 text-neutral-600" />
                  <div>
                    <p className="font-bold text-neutral-900">{user?.name}</p>
                    <p className="text-neutral-500">{user?.email}</p>
                  </div>
                </div>
                <span className="text-emerald-700 font-semibold flex items-center gap-1">
                  <CheckCircle2 className="w-3.5 h-3.5" /> Verified
                </span>
              </div>
            ) : (
              <div className="space-y-4 pt-1">
                <div className="flex gap-2">
                  <button
                    type="button"
                    onClick={() => setAuthMode("new_account")}
                    className={`flex-1 py-2 text-xs font-bold rounded-xl border transition-colors ${
                      authMode === "new_account"
                        ? "bg-neutral-900 text-white border-neutral-900"
                        : "bg-white text-neutral-700 border-neutral-200"
                    }`}
                  >
                    Create Account Instantly
                  </button>
                  <button
                    type="button"
                    onClick={() => setAuthMode("sign_in")}
                    className={`flex-1 py-2 text-xs font-bold rounded-xl border transition-colors ${
                      authMode === "sign_in"
                        ? "bg-neutral-900 text-white border-neutral-900"
                        : "bg-white text-neutral-700 border-neutral-200"
                    }`}
                  >
                    I Have an Account
                  </button>
                </div>

                <div className="grid grid-cols-1 sm:grid-cols-2 gap-3">
                  {authMode === "new_account" && (
                    <Input
                      label="Full Name *"
                      placeholder="Alex Morgan"
                      value={authName}
                      onChange={(e) => setAuthName(e.target.value)}
                      required
                    />
                  )}
                  <Input
                    label="Email Address *"
                    type="email"
                    placeholder="alex@example.com"
                    value={authEmail}
                    onChange={(e) => setAuthEmail(e.target.value)}
                    required
                  />
                </div>
                <p className="text-[11px] text-neutral-500">
                  A secure setup link will be delivered to configure your dashboard password.
                </p>
              </div>
            )}
          </div>

          {/* 2. Shipping Address */}
          <div className="bg-white rounded-3xl border border-neutral-200 p-6 space-y-4 shadow-xs">
            <div className="flex items-center gap-2">
              <span className="w-6 h-6 rounded-full bg-neutral-900 text-white text-xs font-bold flex items-center justify-center">
                2
              </span>
              <h3 className="text-sm font-bold text-neutral-900">Shipping Address</h3>
            </div>

            <div className="grid grid-cols-1 sm:grid-cols-2 gap-4">
              <Input
                label="First Name *"
                value={shippingAddress.firstName}
                onChange={(e) => setShippingAddress({ ...shippingAddress, firstName: e.target.value })}
                required
              />
              <Input
                label="Last Name *"
                value={shippingAddress.lastName}
                onChange={(e) => setShippingAddress({ ...shippingAddress, lastName: e.target.value })}
                required
              />
            </div>

            <Input
              label="Street Address *"
              placeholder="742 Evergreen Terrace"
              value={shippingAddress.street}
              onChange={(e) => setShippingAddress({ ...shippingAddress, street: e.target.value })}
              required
            />

            <div className="grid grid-cols-1 sm:grid-cols-3 gap-4">
              <Input
                label="Apartment / Suite"
                placeholder="Apt 4B"
                value={shippingAddress.apartment}
                onChange={(e) => setShippingAddress({ ...shippingAddress, apartment: e.target.value })}
              />
              <Input
                label="City *"
                value={shippingAddress.city}
                onChange={(e) => setShippingAddress({ ...shippingAddress, city: e.target.value })}
                required
              />
              <Input
                label="State *"
                value={shippingAddress.state}
                onChange={(e) => setShippingAddress({ ...shippingAddress, state: e.target.value })}
                required
              />
            </div>

            <div className="grid grid-cols-1 sm:grid-cols-2 gap-4">
              <Input
                label="Postal / Zip Code *"
                value={shippingAddress.zipCode}
                onChange={(e) => setShippingAddress({ ...shippingAddress, zipCode: e.target.value })}
                required
              />
              <Input
                label="Contact Phone Number *"
                value={shippingAddress.phone}
                onChange={(e) => setShippingAddress({ ...shippingAddress, phone: e.target.value })}
                required
              />
            </div>
          </div>

          {/* 3. Shipping Method */}
          <div className="bg-white rounded-3xl border border-neutral-200 p-6 space-y-4 shadow-xs">
            <div className="flex items-center gap-2">
              <span className="w-6 h-6 rounded-full bg-neutral-900 text-white text-xs font-bold flex items-center justify-center">
                3
              </span>
              <h3 className="text-sm font-bold text-neutral-900">Delivery Speed</h3>
            </div>

            <div className="space-y-2.5">
              {SHIPPING_METHODS.map((method) => {
                const isSelected = selectedShippingMethod === method.id;
                const cost = subtotal >= 50 && method.id === "standard" ? 0 : method.price;

                return (
                  <label
                    key={method.id}
                    className={`flex items-center justify-between p-4 rounded-2xl border transition-all cursor-pointer ${
                      isSelected
                        ? "border-neutral-900 bg-neutral-50 shadow-xs"
                        : "border-neutral-200 hover:border-neutral-300"
                    }`}
                  >
                    <div className="flex items-center gap-3">
                      <input
                        type="radio"
                        name="shipping_method"
                        checked={isSelected}
                        onChange={() => setSelectedShippingMethod(method.id)}
                        className="accent-neutral-900"
                      />
                      <div>
                        <p className="text-xs font-bold text-neutral-900">{method.name}</p>
                        <p className="text-[11px] text-neutral-500">{method.estimatedDays} • {method.description}</p>
                      </div>
                    </div>
                    <span className="text-xs font-bold text-neutral-900">
                      {cost === 0 ? "FREE" : formatPrice(cost)}
                    </span>
                  </label>
                );
              })}
            </div>
          </div>

          {/* 4. Payment Method */}
          <div className="bg-white rounded-3xl border border-neutral-200 p-6 space-y-4 shadow-xs">
            <div className="flex items-center gap-2">
              <span className="w-6 h-6 rounded-full bg-neutral-900 text-white text-xs font-bold flex items-center justify-center">
                4
              </span>
              <h3 className="text-sm font-bold text-neutral-900">Payment Information</h3>
            </div>

            <div className="flex gap-2">
              {[
                { id: "card", label: "Credit Card", icon: CreditCard },
                { id: "apple_pay", label: "Apple Pay", icon: Lock },
                { id: "paypal", label: "PayPal", icon: Lock },
              ].map((p) => (
                <button
                  key={p.id}
                  type="button"
                  onClick={() => setPaymentType(p.id as any)}
                  className={`flex-1 py-2.5 text-xs font-bold rounded-xl border flex items-center justify-center gap-1.5 transition-colors cursor-pointer ${
                    paymentType === p.id
                      ? "bg-neutral-900 text-white border-neutral-900"
                      : "bg-white text-neutral-700 border-neutral-200"
                  }`}
                >
                  <p.icon className="w-3.5 h-3.5" />
                  <span>{p.label}</span>
                </button>
              ))}
            </div>

            {paymentType === "card" && (
              <div className="space-y-3 pt-2">
                <Input
                  label="Name on Card *"
                  value={cardDetails.nameOnCard}
                  onChange={(e) => setCardDetails({ ...cardDetails, nameOnCard: e.target.value })}
                  required
                />
                <Input
                  label="Card Number *"
                  value={cardDetails.cardNumber}
                  onChange={(e) => setCardDetails({ ...cardDetails, cardNumber: e.target.value })}
                  required
                />
                <div className="grid grid-cols-2 gap-3">
                  <Input
                    label="Expiration Date *"
                    placeholder="MM/YY"
                    value={cardDetails.exp}
                    onChange={(e) => setCardDetails({ ...cardDetails, exp: e.target.value })}
                    required
                  />
                  <Input
                    label="Security Code (CVV) *"
                    placeholder="123"
                    value={cardDetails.cvv}
                    onChange={(e) => setCardDetails({ ...cardDetails, cvv: e.target.value })}
                    required
                  />
                </div>
              </div>
            )}
          </div>
        </div>

        {/* Right Summary & Place Order */}
        <div className="lg:col-span-5 bg-white rounded-3xl border border-neutral-200 p-6 space-y-6 shadow-xs sticky top-24">
          <h3 className="text-base font-black text-neutral-900 pb-3 border-b border-neutral-100">
            Order Review ({items.length})
          </h3>

          {/* Items Summary Preview */}
          <div className="divide-y divide-neutral-100 max-h-64 overflow-y-auto">
            {items.map((item) => (
              <div key={item.id} className="py-3 flex items-center gap-3">
                <div className="relative w-12 h-16 rounded-xl overflow-hidden bg-neutral-100 shrink-0">
                  <Image src={item.image} alt={item.name} fill className="object-cover" />
                </div>
                <div className="flex-1 min-w-0">
                  <h4 className="text-xs font-bold text-neutral-900 truncate">{item.name}</h4>
                  <p className="text-[10px] text-neutral-500">
                    {item.selectedColor} • {item.selectedSize} • Qty {item.quantity}
                  </p>
                </div>
                <span className="text-xs font-bold text-neutral-900">
                  {formatPrice(item.price * item.quantity)}
                </span>
              </div>
            ))}
          </div>

          {/* Pricing Totals */}
          <div className="space-y-2 text-xs text-neutral-600 pt-3 border-t border-neutral-100">
            <div className="flex justify-between">
              <span>Subtotal</span>
              <span className="font-semibold text-neutral-900">{formatPrice(subtotal)}</span>
            </div>
            {discount > 0 && (
              <div className="flex justify-between text-emerald-700 font-semibold">
                <span>Coupon ({coupon?.code})</span>
                <span>-{formatPrice(discount)}</span>
              </div>
            )}
            <div className="flex justify-between">
              <span>Shipping ({shippingMethod.name})</span>
              <span className="font-semibold text-neutral-900">
                {shippingCost === 0 ? "FREE" : formatPrice(shippingCost)}
              </span>
            </div>
            <div className="flex justify-between">
              <span>Estimated Sales Tax</span>
              <span className="font-semibold text-neutral-900">{formatPrice(tax)}</span>
            </div>
            <div className="flex justify-between text-base font-black text-neutral-900 pt-3 border-t border-neutral-200">
              <span>Grand Total</span>
              <span>{formatPrice(grandTotal)}</span>
            </div>
          </div>

          <Button
            type="submit"
            isLoading={isSubmitting}
            className="w-full h-13 rounded-full text-xs font-bold uppercase tracking-wider bg-neutral-950 text-white hover:bg-neutral-800 shadow-md"
          >
            Authorize & Place Order • {formatPrice(grandTotal)}
          </Button>

          <p className="text-[11px] text-neutral-400 text-center leading-relaxed">
            By clicking place order, you agree to our Terms & Conditions and 30-day Return Policy.
          </p>
        </div>
      </form>
    </div>
  );
}

