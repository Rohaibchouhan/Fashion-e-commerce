"use client";

import React, { useState, useEffect, useMemo, useCallback } from "react";
import Image from "next/image";
import Link from "next/link";
import { useRouter } from "next/navigation";
import { Breadcrumb } from "@/components/ui/breadcrumb";
import { useCartStore } from "@/stores/cart-store";
import { useAuthStore } from "@/stores/auth-store";
import { AuthService } from "@/services/auth.service";
import { formatPrice } from "@/lib/utils";
import { Input } from "@/components/ui/input";
import { Button } from "@/components/ui/button";
import { toast } from "@/components/ui/toast";
import { SHIPPING_METHODS } from "@/constants";
import { CheckoutService, CheckoutQuote } from "@/services/checkout.service";
import {
  ShieldCheck,
  Lock,
  CreditCard,
  CheckCircle2,
  User,
  Truck,
  AlertCircle,
  Tag,
  PlusCircle,
  MapPin,
  Check,
  Loader2,
} from "lucide-react";

export default function CheckoutPage() {
  const router = useRouter();
  const [mounted, setMounted] = useState(false);
  const { items, clearCart } = useCartStore();
  const { user, isAuthenticated, login } = useAuthStore();

  useEffect(() => {
    setMounted(true);
  }, []);

  // Authentication mode for unauthenticated visitors
  const [authMode, setAuthMode] = useState<"new_account" | "sign_in">("new_account");
  const [loginEmail, setLoginEmail] = useState("");
  const [loginPassword, setLoginPassword] = useState("");
  const [isLoggingIn, setIsLoggingIn] = useState(false);
  const [loginError, setLoginError] = useState<string | null>(null);

  // New account registration fields
  const [authEmail, setAuthEmail] = useState("");
  const [authName, setAuthName] = useState("");
  const [authPhone, setAuthPhone] = useState("");

  // Address selection
  const savedAddresses = user?.addresses || [];
  const defaultAddress = savedAddresses.find((a) => a.isDefaultShipping) || savedAddresses[0];

  const [selectedAddressMode, setSelectedAddressMode] = useState<"saved" | "new">(
    savedAddresses.length > 0 ? "saved" : "new"
  );
  const [selectedAddressId, setSelectedAddressId] = useState<string | null>(
    defaultAddress ? String(defaultAddress.id) : null
  );

  // New shipping address form state
  const [newShippingAddress, setNewShippingAddress] = useState({
    firstName: user?.firstName || (user?.name ? user.name.split(" ")[0] : ""),
    lastName: user?.lastName || (user?.name ? user.name.split(" ").slice(1).join(" ") : ""),
    street: "",
    apartment: "",
    city: "",
    state: "",
    zipCode: "",
    country: "United States",
    phone: user?.phone || "",
  });

  const [deliveryNotes, setDeliveryNotes] = useState("");

  // Billing address state
  const [billingSameAsShipping, setBillingSameAsShipping] = useState(true);
  const [selectedBillingAddressId, setSelectedBillingAddressId] = useState<string | null>(
    defaultAddress ? String(defaultAddress.id) : null
  );
  const [newBillingAddress, setNewBillingAddress] = useState({
    firstName: "",
    lastName: "",
    street: "",
    apartment: "",
    city: "",
    state: "",
    zipCode: "",
    country: "United States",
    phone: "",
  });

  // Shipping Method & Payment
  const [selectedShippingMethod, setSelectedShippingMethod] = useState("standard");
  const [paymentType, setPaymentType] = useState<"card" | "apple_pay" | "paypal">("card");
  const [cardDetails, setCardDetails] = useState({
    cardNumber: "4242 •••• •••• 4242",
    exp: "12/28",
    cvv: "382",
    nameOnCard: user?.name || "Cardholder",
  });

  // Coupon state
  const [couponInput, setCouponInput] = useState("");
  const [appliedCoupon, setAppliedCoupon] = useState<string | null>(null);
  const [couponError, setCouponError] = useState<string | null>(null);

  // Server quote calculation state
  const [quote, setQuote] = useState<CheckoutQuote | null>(null);
  const [isQuoteLoading, setIsQuoteLoading] = useState(false);
  const [quoteError, setQuoteError] = useState<string | null>(null);

  // Form submission state
  const [isSubmitting, setIsSubmitting] = useState(false);
  const [checkoutError, setCheckoutError] = useState<string | null>(null);

  // Idempotency key per checkout attempt
  const [idempotencyKey, setIdempotencyKey] = useState<string>("");
  const [hasRestoredStorage, setHasRestoredStorage] = useState(false);

  useEffect(() => {
    if (typeof crypto !== "undefined" && crypto.randomUUID) {
      setIdempotencyKey(crypto.randomUUID());
    } else {
      setIdempotencyKey(`idemp-${Date.now()}-${Math.random().toString(36).slice(2, 9)}`);
    }
  }, []);

  // Restore non-sensitive guest fields across page reloads (strictly no CVV/card info)
  useEffect(() => {
    if (typeof window === "undefined") return;
    try {
      const saved = sessionStorage.getItem("wearday_checkout_guest_fields");
      if (saved) {
        const parsed = JSON.parse(saved);
        if (parsed.authEmail) setAuthEmail(parsed.authEmail);
        if (parsed.authName) setAuthName(parsed.authName);
        if (parsed.authPhone) setAuthPhone(parsed.authPhone);
        if (parsed.selectedAddressMode) setSelectedAddressMode(parsed.selectedAddressMode);
        if (parsed.selectedAddressId) setSelectedAddressId(parsed.selectedAddressId);
        if (parsed.newShippingAddress) {
          setNewShippingAddress((prev) => ({ ...prev, ...parsed.newShippingAddress }));
        }
        if (parsed.deliveryNotes) setDeliveryNotes(parsed.deliveryNotes);
        if (parsed.billingSameAsShipping !== undefined) {
          setBillingSameAsShipping(parsed.billingSameAsShipping);
        }
        if (parsed.selectedBillingAddressId) {
          setSelectedBillingAddressId(parsed.selectedBillingAddressId);
        }
        if (parsed.newBillingAddress) {
          setNewBillingAddress((prev) => ({ ...prev, ...parsed.newBillingAddress }));
        }
        if (parsed.selectedShippingMethod) {
          setSelectedShippingMethod(parsed.selectedShippingMethod);
        }
      }
    } catch (e) {
      console.warn("Failed to restore checkout guest fields:", e);
    } finally {
      setHasRestoredStorage(true);
    }
  }, []);

  // Persist non-sensitive guest fields to sessionStorage
  useEffect(() => {
    if (typeof window === "undefined" || !hasRestoredStorage) return;
    try {
      const payload = {
        authEmail,
        authName,
        authPhone,
        selectedAddressMode,
        selectedAddressId,
        newShippingAddress,
        deliveryNotes,
        billingSameAsShipping,
        selectedBillingAddressId,
        newBillingAddress,
        selectedShippingMethod,
      };
      sessionStorage.setItem("wearday_checkout_guest_fields", JSON.stringify(payload));
    } catch (e) {
      console.warn("Failed to persist checkout fields:", e);
    }
  }, [
    hasRestoredStorage,
    authEmail,
    authName,
    authPhone,
    selectedAddressMode,
    selectedAddressId,
    newShippingAddress,
    deliveryNotes,
    billingSameAsShipping,
    selectedBillingAddressId,
    newBillingAddress,
    selectedShippingMethod,
  ]);

  // Update address selection when user logs in or addresses change (respecting guest custom address)
  useEffect(() => {
    if (!hasRestoredStorage) return;
    const saved = typeof window !== "undefined" ? sessionStorage.getItem("wearday_checkout_guest_fields") : null;
    if (savedAddresses.length > 0 && selectedAddressMode === "new" && !newShippingAddress.street && !saved) {
      setSelectedAddressMode("saved");
      setSelectedAddressId(String(defaultAddress?.id || savedAddresses[0].id));
    }
  }, [savedAddresses.length, hasRestoredStorage]);

  // Fetch authoritative quote from Laravel whenever items, shipping method, or coupon changes
  const fetchLiveQuote = useCallback(async () => {
    if (items.length === 0) return;

    try {
      setIsQuoteLoading(true);
      setQuoteError(null);

      const activeShippingAddress =
        selectedAddressMode === "saved"
          ? savedAddresses.find((a) => String(a.id) === String(selectedAddressId))
          : newShippingAddress;

      const serverQuote = await CheckoutService.getQuote({
        items,
        shippingMethodId: selectedShippingMethod,
        couponCode: appliedCoupon || undefined,
        shippingAddress: activeShippingAddress || undefined,
      });

      setQuote(serverQuote);
      if (appliedCoupon && !serverQuote.couponCode) {
        setAppliedCoupon(null);
      }
    } catch (err: any) {
      const msg = err?.response?.data?.message || err?.message || "Failed to update totals.";
      setQuoteError(msg);
      if (appliedCoupon) {
        setCouponError(msg);
        setAppliedCoupon(null);
      }
    } finally {
      setIsQuoteLoading(false);
    }
  }, [items, selectedShippingMethod, appliedCoupon, selectedAddressMode, selectedAddressId, newShippingAddress]);

  useEffect(() => {
    fetchLiveQuote();
  }, [fetchLiveQuote]);

  // Handle inline sign-in
  const handleInlineLogin = async (e: React.FormEvent) => {
    e.preventDefault();
    if (!loginEmail || !loginPassword) {
      setLoginError("Please enter both email and password.");
      return;
    }

    try {
      setIsLoggingIn(true);
      setLoginError(null);
      const res = await AuthService.login(loginEmail, loginPassword);
      login(res.user, res.token);
      toast.success("Welcome back!", `Signed in as ${res.user.name}`);
    } catch (err: any) {
      setLoginError(err?.message || "Invalid credentials. Please try again.");
    } finally {
      setIsLoggingIn(false);
    }
  };

  // Apply Coupon code
  const handleApplyCoupon = async (e: React.FormEvent) => {
    e.preventDefault();
    if (!couponInput.trim()) return;

    try {
      setCouponError(null);
      setIsQuoteLoading(true);

      const activeShippingAddress =
        selectedAddressMode === "saved"
          ? savedAddresses.find((a) => String(a.id) === String(selectedAddressId))
          : newShippingAddress;

      const serverQuote = await CheckoutService.getQuote({
        items,
        shippingMethodId: selectedShippingMethod,
        couponCode: couponInput.trim().toUpperCase(),
        shippingAddress: activeShippingAddress || undefined,
      });

      if (serverQuote.discount > 0) {
        setAppliedCoupon(couponInput.trim().toUpperCase());
        setQuote(serverQuote);
        toast.success("Coupon Applied!", `Saved ${formatPrice(serverQuote.discount)}`);
      } else {
        setCouponError("This coupon does not provide a discount for the current items.");
      }
    } catch (err: any) {
      const msg = err?.response?.data?.message || err?.message || "Invalid coupon code.";
      setCouponError(msg);
      toast.error("Coupon Rejected", msg);
    } finally {
      setIsQuoteLoading(false);
    }
  };

  const handleRemoveCoupon = () => {
    setAppliedCoupon(null);
    setCouponInput("");
    setCouponError(null);
  };

  // Submit and Place Order
  const handlePlaceOrder = async (e: React.FormEvent) => {
    e.preventDefault();
    setCheckoutError(null);

    // Authentication enforcement
    if (!isAuthenticated) {
      if (authMode === "sign_in") {
        setCheckoutError("Please sign in with your email and password above to complete your order.");
        toast.error("Authentication Required", "Please sign in before placing your order.");
        return;
      }

      if (authMode === "new_account") {
        if (!authEmail || !authName) {
          setCheckoutError("Please provide your full name and email to create your customer account.");
          toast.error("Account Required", "Customer name and email are required.");
          return;
        }
      }
    }

    // Shipping address validation
    if (selectedAddressMode === "new") {
      if (
        !newShippingAddress.firstName ||
        !newShippingAddress.lastName ||
        !newShippingAddress.street ||
        !newShippingAddress.city ||
        !newShippingAddress.state ||
        !newShippingAddress.zipCode
      ) {
        setCheckoutError("Please fill in all required shipping address fields.");
        toast.error("Address Incomplete", "Please complete all required shipping address fields.");
        return;
      }
    } else if (selectedAddressMode === "saved" && !selectedAddressId) {
      setCheckoutError("Please select a saved shipping address.");
      return;
    }

    try {
      setIsSubmitting(true);

      const payload: any = {
        items,
        shippingMethodId: selectedShippingMethod,
        paymentMethod: {
          type: paymentType,
          cardNumber: cardDetails.cardNumber,
          nameOnCard: cardDetails.nameOnCard,
          expDate: cardDetails.exp,
          cvv: cardDetails.cvv,
        },
        couponCode: appliedCoupon || undefined,
        notes: deliveryNotes.trim() || undefined,
        idempotencyKey,
      };

      // Address payload
      if (selectedAddressMode === "saved" && selectedAddressId) {
        payload.shippingAddressId = selectedAddressId;
      } else {
        payload.shippingAddress = {
          id: "new-addr",
          ...newShippingAddress,
        };
      }

      // Billing address payload
      payload.billingSameAsShipping = billingSameAsShipping;
      if (!billingSameAsShipping) {
        if (selectedBillingAddressId) {
          payload.billingAddressId = selectedBillingAddressId;
        } else {
          payload.billingAddress = {
            id: "new-bill-addr",
            ...newBillingAddress,
          };
        }
      }

      // Guest / Auto-register payload
      if (!isAuthenticated && authMode === "new_account") {
        payload.autoRegister = {
          name: authName.trim(),
          email: authEmail.trim(),
          phone: authPhone.trim() || undefined,
        };
      }

      const order = await CheckoutService.processOrder(payload);

      clearCart();
      if (typeof window !== "undefined") {
        sessionStorage.removeItem("wearday_checkout_guest_fields");
      }
      toast.success("Order Placed Successfully!", `Order #${order.orderNumber} is pending payment.`);
      router.push(`/order-confirmation/${order.id}`);
    } catch (err: any) {
      const respData = err?.data || err?.response?.data;
      const isAuthRequired = respData?.requires_auth || err?.requires_auth;
      const status = err?.status || err?.response?.status;

      if (
        isAuthRequired ||
        (status === 422 && respData?.errors?.email && String(respData?.errors?.email).includes("sign in"))
      ) {
        setAuthMode("sign_in");
        if (authEmail) {
          setLoginEmail(authEmail);
        }
        setLoginError("An account with this email already exists. Please enter your password to sign in and complete checkout.");
        setCheckoutError("An account with this email already exists. Please sign in above to complete your order.");
        toast.info("Account Exists", "Please sign in with your password to complete your order.");
        if (typeof window !== "undefined") {
          window.scrollTo({ top: 0, behavior: "smooth" });
        }
        return;
      }

      if (status === 409 || respData?.error_type === "idempotency_conflict") {
        const conflictMsg =
          respData?.message ||
          "This checkout request was already submitted with different order details. Please review your cart.";
        setCheckoutError(conflictMsg);
        toast.error("Checkout Conflict", conflictMsg);
        return;
      }

      const msg =
        respData?.message ||
        err?.message ||
        "An unexpected error occurred during checkout. Please try again.";
      setCheckoutError(msg);
      toast.error("Checkout Failed", msg);
    } finally {
      setIsSubmitting(false);
    }
  };

  if (!mounted) {
    return (
      <div className="bg-neutral-50/50 min-h-screen py-10 sm:py-16">
        <div className="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8">
          <Breadcrumb
            items={[
              { label: "Home", href: "/" },
              { label: "Shopping Bag", href: "/cart" },
              { label: "Secure Checkout" },
            ]}
          />
          <div className="mt-8 flex flex-col items-center justify-center min-h-[400px]">
            <Loader2 className="w-8 h-8 animate-spin text-neutral-400 mb-4" />
            <p className="text-sm text-neutral-500 font-medium">Initializing secure checkout session...</p>
          </div>
        </div>
      </div>
    );
  }

  if (items.length === 0) {
    return (
      <div className="max-w-xl mx-auto px-4 py-24 text-center space-y-4">
        <h2 className="text-xl font-bold text-neutral-900">Your bag is empty</h2>
        <p className="text-xs text-neutral-500">Add pieces to your bag before checking out.</p>
        <Link
          href="/shop"
          className="inline-block px-6 py-2.5 bg-neutral-900 text-white rounded-full text-xs font-semibold hover:bg-neutral-800 transition-colors"
        >
          Return to Shop
        </Link>
      </div>
    );
  }

  // Authoritative totals from quote or fallback calculations
  const activeShippingMethod = SHIPPING_METHODS.find((s) => s.id === selectedShippingMethod);
  const displayShippingMethodName = activeShippingMethod?.name || quote?.shippingMethodName || "Standard Ground Delivery";
  const displaySubtotal = quote?.subtotal ?? items.reduce((acc, i) => acc + i.price * i.quantity, 0);
  const displayDiscount = quote?.discount ?? 0;
  const displayShipping =
    quote?.shipping ??
    (displaySubtotal >= 50 && selectedShippingMethod === "standard"
      ? 0
      : (activeShippingMethod?.price ?? 10));
  const displayTax = quote?.tax ?? roundToTwo((displaySubtotal - displayDiscount) * 0.08);
  const displayTotal = quote?.total ?? Math.max(0, displaySubtotal - displayDiscount + displayShipping + displayTax);

  return (
    <div className="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8 py-8 sm:py-12 space-y-8">
      <Breadcrumb
        items={[
          { label: "Home", href: "/" },
          { label: "Shopping Bag", href: "/cart" },
          { label: "Secure Checkout" },
        ]}
      />

      {/* Header */}
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

      {/* Checkout Error Banner */}
      {checkoutError && (
        <div className="p-4 rounded-2xl bg-rose-50 border border-rose-200 flex items-start gap-3 text-rose-800 text-xs">
          <AlertCircle className="w-4 h-4 text-rose-600 shrink-0 mt-0.5" />
          <div className="space-y-1">
            <p className="font-bold">Unable to complete checkout</p>
            <p className="text-rose-700">{checkoutError}</p>
          </div>
        </div>
      )}

      <form onSubmit={handlePlaceOrder} className="grid grid-cols-1 lg:grid-cols-12 gap-10 items-start">
        {/* Left Column: Account, Addresses, Delivery, Payment */}
        <div className="lg:col-span-7 space-y-8">
          {/* 1. Customer Account */}
          <div className="bg-white rounded-3xl border border-neutral-200 p-6 space-y-4 shadow-xs">
            <div className="flex items-center justify-between">
              <div className="flex items-center gap-2">
                <span className="w-6 h-6 rounded-full bg-neutral-900 text-white text-xs font-bold flex items-center justify-center">
                  1
                </span>
                <h3 className="text-sm font-bold text-neutral-900">Customer Account</h3>
              </div>
              <span className="text-[11px] font-semibold text-neutral-400">
                {isAuthenticated ? "Authenticated Customer" : "Account Required"}
              </span>
            </div>

            {isAuthenticated ? (
              <div className="p-4 rounded-2xl bg-neutral-50 border border-neutral-200 flex items-center justify-between text-xs">
                <div className="flex items-center gap-3">
                  <div className="w-9 h-9 rounded-full bg-neutral-900 text-white flex items-center justify-center font-bold text-xs">
                    {user?.name?.[0] || "U"}
                  </div>
                  <div>
                    <p className="font-bold text-neutral-900">{user?.name}</p>
                    <p className="text-neutral-500">{user?.email}</p>
                    {user?.phone && <p className="text-[11px] text-neutral-400">{user.phone}</p>}
                  </div>
                </div>
                <span className="text-emerald-700 font-semibold flex items-center gap-1 bg-emerald-50 px-2.5 py-1 rounded-full border border-emerald-200 text-[11px]">
                  <CheckCircle2 className="w-3.5 h-3.5" /> Verified
                </span>
              </div>
            ) : (
              <div className="space-y-4 pt-1">
                <div className="flex gap-2">
                  <button
                    type="button"
                    onClick={() => {
                      setAuthMode("new_account");
                      setLoginError(null);
                    }}
                    className={`flex-1 py-2 text-xs font-bold rounded-xl border transition-colors cursor-pointer ${
                      authMode === "new_account"
                        ? "bg-neutral-900 text-white border-neutral-900"
                        : "bg-white text-neutral-700 border-neutral-200 hover:border-neutral-300"
                    }`}
                  >
                    Create Account Instantly
                  </button>
                  <button
                    type="button"
                    onClick={() => {
                      setAuthMode("sign_in");
                      setLoginError(null);
                    }}
                    className={`flex-1 py-2 text-xs font-bold rounded-xl border transition-colors cursor-pointer ${
                      authMode === "sign_in"
                        ? "bg-neutral-900 text-white border-neutral-900"
                        : "bg-white text-neutral-700 border-neutral-200 hover:border-neutral-300"
                    }`}
                  >
                    I Have an Account
                  </button>
                </div>

                {authMode === "new_account" ? (
                  <div className="space-y-3">
                    <div className="grid grid-cols-1 sm:grid-cols-2 gap-3">
                      <Input
                        label="Full Name *"
                        placeholder="Alex Morgan"
                        value={authName}
                        onChange={(e) => setAuthName(e.target.value)}
                        required
                      />
                      <Input
                        label="Email Address *"
                        type="email"
                        placeholder="alex@example.com"
                        value={authEmail}
                        onChange={(e) => setAuthEmail(e.target.value)}
                        required
                      />
                    </div>
                    <Input
                      label="Contact Phone"
                      placeholder="+1 (555) 019-2834"
                      value={authPhone}
                      onChange={(e) => setAuthPhone(e.target.value)}
                    />
                    <p className="text-[11px] text-neutral-500 leading-relaxed">
                      Your customer profile will be automatically configured upon order placement. A password setup
                      link will be sent to your email.
                    </p>
                  </div>
                ) : (
                  <div className="space-y-3 p-4 rounded-2xl bg-neutral-50 border border-neutral-200">
                    <div className="grid grid-cols-1 sm:grid-cols-2 gap-3">
                      <Input
                        label="Email Address *"
                        type="email"
                        placeholder="alex@example.com"
                        value={loginEmail}
                        onChange={(e) => setLoginEmail(e.target.value)}
                        required
                      />
                      <Input
                        label="Password *"
                        type="password"
                        placeholder="••••••••"
                        value={loginPassword}
                        onChange={(e) => setLoginPassword(e.target.value)}
                        required
                      />
                    </div>
                    {loginError && <p className="text-xs text-rose-600 font-medium">{loginError}</p>}
                    <Button
                      type="button"
                      onClick={handleInlineLogin}
                      isLoading={isLoggingIn}
                      className="w-full sm:w-auto px-5 py-2 text-xs bg-neutral-900 text-white rounded-xl font-bold"
                    >
                      Sign In & Continue
                    </Button>
                  </div>
                )}
              </div>
            )}
          </div>

          {/* 2. Shipping Address */}
          <div className="bg-white rounded-3xl border border-neutral-200 p-6 space-y-4 shadow-xs">
            <div className="flex items-center justify-between">
              <div className="flex items-center gap-2">
                <span className="w-6 h-6 rounded-full bg-neutral-900 text-white text-xs font-bold flex items-center justify-center">
                  2
                </span>
                <h3 className="text-sm font-bold text-neutral-900">Shipping Address</h3>
              </div>
              {savedAddresses.length > 0 && (
                <div className="flex gap-2">
                  <button
                    type="button"
                    onClick={() => setSelectedAddressMode("saved")}
                    className={`px-3 py-1 rounded-lg text-xs font-bold border transition-colors cursor-pointer ${
                      selectedAddressMode === "saved"
                        ? "bg-neutral-900 text-white border-neutral-900"
                        : "bg-white text-neutral-600 border-neutral-200"
                    }`}
                  >
                    Saved Addresses ({savedAddresses.length})
                  </button>
                  <button
                    type="button"
                    onClick={() => setSelectedAddressMode("new")}
                    className={`px-3 py-1 rounded-lg text-xs font-bold border transition-colors cursor-pointer ${
                      selectedAddressMode === "new"
                        ? "bg-neutral-900 text-white border-neutral-900"
                        : "bg-white text-neutral-600 border-neutral-200"
                    }`}
                  >
                    New Address
                  </button>
                </div>
              )}
            </div>

            {/* Saved Address Radio Cards */}
            {selectedAddressMode === "saved" && savedAddresses.length > 0 ? (
              <div className="space-y-3">
                {savedAddresses.map((addr) => {
                  const isSelected = String(selectedAddressId) === String(addr.id);
                  return (
                    <label
                      key={addr.id}
                      className={`flex items-start gap-3 p-4 rounded-2xl border transition-all cursor-pointer ${
                        isSelected
                          ? "border-neutral-900 bg-neutral-50 shadow-xs"
                          : "border-neutral-200 hover:border-neutral-300"
                      }`}
                    >
                      <input
                        type="radio"
                        name="saved_shipping_address"
                        checked={isSelected}
                        onChange={() => setSelectedAddressId(String(addr.id))}
                        className="accent-neutral-900 mt-1"
                      />
                      <div className="flex-1 text-xs">
                        <div className="flex items-center justify-between">
                          <p className="font-bold text-neutral-900">
                            {addr.firstName} {addr.lastName}
                          </p>
                          {addr.isDefaultShipping && (
                            <span className="text-[10px] font-bold uppercase tracking-wider text-neutral-500 bg-neutral-200/60 px-2 py-0.5 rounded-md">
                              Default
                            </span>
                          )}
                        </div>
                        <p className="text-neutral-600 mt-0.5">
                          {addr.street}
                          {addr.apartment ? `, ${addr.apartment}` : ""}
                        </p>
                        <p className="text-neutral-500">
                          {addr.city}, {addr.state} {addr.zipCode} • {addr.country}
                        </p>
                        {addr.phone && <p className="text-neutral-400 text-[11px] mt-0.5">Phone: {addr.phone}</p>}
                      </div>
                    </label>
                  );
                })}
              </div>
            ) : (
              /* New Address Form */
              <div className="space-y-4">
                <div className="grid grid-cols-1 sm:grid-cols-2 gap-4">
                  <Input
                    label="First Name *"
                    value={newShippingAddress.firstName}
                    onChange={(e) =>
                      setNewShippingAddress({ ...newShippingAddress, firstName: e.target.value })
                    }
                    required
                  />
                  <Input
                    label="Last Name *"
                    value={newShippingAddress.lastName}
                    onChange={(e) =>
                      setNewShippingAddress({ ...newShippingAddress, lastName: e.target.value })
                    }
                    required
                  />
                </div>

                <Input
                  label="Street Address *"
                  placeholder="742 Evergreen Terrace"
                  value={newShippingAddress.street}
                  onChange={(e) =>
                    setNewShippingAddress({ ...newShippingAddress, street: e.target.value })
                  }
                  required
                />

                <div className="grid grid-cols-1 sm:grid-cols-3 gap-4">
                  <Input
                    label="Apartment / Suite"
                    placeholder="Apt 4B"
                    value={newShippingAddress.apartment}
                    onChange={(e) =>
                      setNewShippingAddress({ ...newShippingAddress, apartment: e.target.value })
                    }
                  />
                  <Input
                    label="City *"
                    value={newShippingAddress.city}
                    onChange={(e) =>
                      setNewShippingAddress({ ...newShippingAddress, city: e.target.value })
                    }
                    required
                  />
                  <Input
                    label="State / Province *"
                    value={newShippingAddress.state}
                    onChange={(e) =>
                      setNewShippingAddress({ ...newShippingAddress, state: e.target.value })
                    }
                    required
                  />
                </div>

                <div className="grid grid-cols-1 sm:grid-cols-2 gap-4">
                  <Input
                    label="Postal / Zip Code *"
                    value={newShippingAddress.zipCode}
                    onChange={(e) =>
                      setNewShippingAddress({ ...newShippingAddress, zipCode: e.target.value })
                    }
                    required
                  />
                  <Input
                    label="Contact Phone Number"
                    value={newShippingAddress.phone}
                    onChange={(e) =>
                      setNewShippingAddress({ ...newShippingAddress, phone: e.target.value })
                    }
                  />
                </div>
              </div>
            )}

            {/* Delivery Instructions / Notes */}
            <div className="pt-2">
              <label className="block text-xs font-bold text-neutral-800 mb-1.5">
                Delivery Instructions (Optional)
              </label>
              <textarea
                rows={2}
                placeholder="Leave with doorman, gate code #4092, ring bell..."
                value={deliveryNotes}
                onChange={(e) => setDeliveryNotes(e.target.value)}
                className="w-full text-xs p-3 rounded-2xl border border-neutral-200 focus:border-neutral-900 focus:ring-0 outline-hidden transition-colors"
              />
            </div>
          </div>

          {/* 3. Billing Address */}
          <div className="bg-white rounded-3xl border border-neutral-200 p-6 space-y-4 shadow-xs">
            <div className="flex items-center justify-between">
              <div className="flex items-center gap-2">
                <span className="w-6 h-6 rounded-full bg-neutral-900 text-white text-xs font-bold flex items-center justify-center">
                  3
                </span>
                <h3 className="text-sm font-bold text-neutral-900">Billing Address</h3>
              </div>
            </div>

            <label className="flex items-center gap-2.5 text-xs text-neutral-800 cursor-pointer select-none">
              <input
                type="checkbox"
                checked={billingSameAsShipping}
                onChange={(e) => setBillingSameAsShipping(e.target.checked)}
                className="w-4 h-4 rounded-md accent-neutral-900"
              />
              <span className="font-semibold">Billing address is identical to shipping address</span>
            </label>

            {!billingSameAsShipping && (
              <div className="space-y-4 pt-2 border-t border-neutral-100">
                <div className="grid grid-cols-1 sm:grid-cols-2 gap-4">
                  <Input
                    label="First Name *"
                    value={newBillingAddress.firstName}
                    onChange={(e) =>
                      setNewBillingAddress({ ...newBillingAddress, firstName: e.target.value })
                    }
                    required
                  />
                  <Input
                    label="Last Name *"
                    value={newBillingAddress.lastName}
                    onChange={(e) =>
                      setNewBillingAddress({ ...newBillingAddress, lastName: e.target.value })
                    }
                    required
                  />
                </div>

                <Input
                  label="Street Address *"
                  value={newBillingAddress.street}
                  onChange={(e) =>
                    setNewBillingAddress({ ...newBillingAddress, street: e.target.value })
                  }
                  required
                />

                <div className="grid grid-cols-1 sm:grid-cols-3 gap-4">
                  <Input
                    label="Apartment / Suite"
                    value={newBillingAddress.apartment}
                    onChange={(e) =>
                      setNewBillingAddress({ ...newBillingAddress, apartment: e.target.value })
                    }
                  />
                  <Input
                    label="City *"
                    value={newBillingAddress.city}
                    onChange={(e) =>
                      setNewBillingAddress({ ...newBillingAddress, city: e.target.value })
                    }
                    required
                  />
                  <Input
                    label="State / Province *"
                    value={newBillingAddress.state}
                    onChange={(e) =>
                      setNewBillingAddress({ ...newBillingAddress, state: e.target.value })
                    }
                    required
                  />
                </div>

                <div className="grid grid-cols-1 sm:grid-cols-2 gap-4">
                  <Input
                    label="Postal / Zip Code *"
                    value={newBillingAddress.zipCode}
                    onChange={(e) =>
                      setNewBillingAddress({ ...newBillingAddress, zipCode: e.target.value })
                    }
                    required
                  />
                  <Input
                    label="Billing Phone"
                    value={newBillingAddress.phone}
                    onChange={(e) =>
                      setNewBillingAddress({ ...newBillingAddress, phone: e.target.value })
                    }
                  />
                </div>
              </div>
            )}
          </div>

          {/* 4. Shipping Method */}
          <div className="bg-white rounded-3xl border border-neutral-200 p-6 space-y-4 shadow-xs">
            <div className="flex items-center gap-2">
              <span className="w-6 h-6 rounded-full bg-neutral-900 text-white text-xs font-bold flex items-center justify-center">
                4
              </span>
              <h3 className="text-sm font-bold text-neutral-900">Delivery Speed</h3>
            </div>

            <div className="space-y-2.5">
              {SHIPPING_METHODS.map((method) => {
                const isSelected = selectedShippingMethod === method.id;
                const cost = displaySubtotal >= 50 && method.id === "standard" ? 0 : method.price;

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
                        <p className="text-[11px] text-neutral-500">
                          {method.estimatedDays} • {method.description}
                        </p>
                      </div>
                    </div>
                    <span className="text-xs font-bold text-neutral-900">
                      {cost === 0 ? (
                        <span className="text-emerald-700 font-bold">FREE</span>
                      ) : (
                        formatPrice(cost)
                      )}
                    </span>
                  </label>
                );
              })}
            </div>
          </div>

          {/* 5. Payment Information */}
          <div className="bg-white rounded-3xl border border-neutral-200 p-6 space-y-4 shadow-xs">
            <div className="flex items-center justify-between">
              <div className="flex items-center gap-2">
                <span className="w-6 h-6 rounded-full bg-neutral-900 text-white text-xs font-bold flex items-center justify-center">
                  5
                </span>
                <h3 className="text-sm font-bold text-neutral-900">Payment Information</h3>
              </div>
              <span className="text-[11px] text-neutral-500 font-semibold flex items-center gap-1">
                <ShieldCheck className="w-3.5 h-3.5 text-emerald-600" /> Pending Authorization
              </span>
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
                      : "bg-white text-neutral-700 border-neutral-200 hover:border-neutral-300"
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

            <div className="p-3 rounded-2xl bg-neutral-50 border border-neutral-200 text-[11px] text-neutral-500 leading-relaxed">
              <strong>Payment Policy:</strong> Your payment method will be verified and an order placed in pending
              payment status. Settlement will take place during final dispatch.
            </div>
          </div>
        </div>

        {/* Right Column: Order Review, Coupon & Authoritative Totals */}
        <div className="lg:col-span-5 bg-white rounded-3xl border border-neutral-200 p-6 space-y-6 shadow-xs sticky top-24">
          <div className="flex items-center justify-between pb-3 border-b border-neutral-100">
            <h3 className="text-base font-black text-neutral-900">
              Order Review ({items.length})
            </h3>
            {isQuoteLoading && (
              <span className="flex items-center gap-1 text-[11px] text-neutral-400">
                <Loader2 className="w-3 h-3 animate-spin" /> Recalculating...
              </span>
            )}
          </div>

          {/* Items Summary Preview */}
          <div className="divide-y divide-neutral-100 max-h-64 overflow-y-auto pr-1">
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
                  {item.sku && <p className="text-[9px] text-neutral-400 font-mono">SKU: {item.sku}</p>}
                </div>
                <span className="text-xs font-bold text-neutral-900">
                  {formatPrice(item.price * item.quantity)}
                </span>
              </div>
            ))}
          </div>

          {/* Coupon Input Form */}
          <div className="space-y-2 pt-2 border-t border-neutral-100">
            <label className="block text-xs font-bold text-neutral-800">Promo or Voucher Code</label>
            {appliedCoupon ? (
              <div className="flex items-center justify-between p-3 rounded-2xl bg-emerald-50 border border-emerald-200 text-xs">
                <div className="flex items-center gap-2 text-emerald-800 font-bold">
                  <Tag className="w-3.5 h-3.5 text-emerald-600" />
                  <span>{appliedCoupon}</span>
                  <span className="text-emerald-600 font-normal">(-{formatPrice(displayDiscount)})</span>
                </div>
                <button
                  type="button"
                  onClick={handleRemoveCoupon}
                  className="text-[11px] font-semibold text-emerald-900 hover:underline cursor-pointer"
                >
                  Remove
                </button>
              </div>
            ) : (
              <div className="flex gap-2">
                <input
                  type="text"
                  placeholder="e.g. WELCOME10"
                  value={couponInput}
                  onChange={(e) => setCouponInput(e.target.value.toUpperCase())}
                  className="flex-1 text-xs px-3.5 py-2.5 rounded-xl border border-neutral-200 uppercase tracking-wider font-mono focus:border-neutral-900 outline-hidden"
                />
                <Button
                  type="button"
                  onClick={handleApplyCoupon}
                  disabled={!couponInput.trim() || isQuoteLoading}
                  className="px-4 py-2.5 text-xs bg-neutral-900 text-white rounded-xl font-bold cursor-pointer"
                >
                  Apply
                </Button>
              </div>
            )}
            {couponError && <p className="text-[11px] text-rose-600 font-medium">{couponError}</p>}
          </div>

          {/* Authoritative Totals Breakdown */}
          <div className="space-y-2 text-xs text-neutral-600 pt-3 border-t border-neutral-100">
            <div className="flex justify-between">
              <span>Subtotal</span>
              <span className="font-semibold text-neutral-900">{formatPrice(displaySubtotal)}</span>
            </div>
            {displayDiscount > 0 && (
              <div className="flex justify-between text-emerald-700 font-semibold">
                <span>Coupon ({appliedCoupon})</span>
                <span>-{formatPrice(displayDiscount)}</span>
              </div>
            )}
            <div className="flex justify-between">
              <span>Shipping ({displayShippingMethodName})</span>
              <span className="font-semibold text-neutral-900">
                {displayShipping === 0 ? <span className="text-emerald-700 font-bold">FREE</span> : formatPrice(displayShipping)}
              </span>
            </div>
            <div className="flex justify-between">
              <span>Estimated Sales Tax ({quote?.taxRate ?? 8}%)</span>
              <span className="font-semibold text-neutral-900">{formatPrice(displayTax)}</span>
            </div>
            <div className="flex justify-between text-base font-black text-neutral-900 pt-3 border-t border-neutral-200">
              <span>Grand Total</span>
              <span>{formatPrice(displayTotal)}</span>
            </div>
          </div>

          {/* Submit Button */}
          <Button
            type="submit"
            isLoading={isSubmitting}
            disabled={isSubmitting || isQuoteLoading}
            className="w-full h-13 rounded-full text-xs font-bold uppercase tracking-wider bg-neutral-950 text-white hover:bg-neutral-800 shadow-md cursor-pointer"
          >
            Authorize & Place Order • {formatPrice(displayTotal)}
          </Button>

          <p className="text-[11px] text-neutral-400 text-center leading-relaxed">
            By clicking place order, you agree to WearDay Terms & Conditions and 30-Day Return Policy.
          </p>
        </div>
      </form>
    </div>
  );
}

function roundToTwo(num: number): number {
  return Math.round((num + Number.EPSILON) * 100) / 100;
}
