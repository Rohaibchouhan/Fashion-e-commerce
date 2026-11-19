"use client";

import React, { useState, useEffect, use } from "react";
import Image from "next/image";
import Link from "next/link";
import { notFound, useRouter } from "next/navigation";
import {
  Heart,
  ShoppingBag,
  Star,
  Truck,
  RotateCcw,
  ShieldCheck,
  Check,
  CheckCircle2,
  Ruler,
  Maximize2,
  ChevronRight,
  Plus,
  Minus,
  Loader2,
  PenLine,
  Clock,
} from "lucide-react";
import { ProductsService } from "@/services/products.service";
import { ReviewsService, UserReviewData, UserEligibilityData } from "@/services/reviews.service";
import { ReviewModal } from "@/components/product/review-modal";
import { ApiClient } from "@/services/api-client";
import { Product } from "@/types/product";
import { formatPrice, cn } from "@/lib/utils";
import { useCartStore } from "@/stores/cart-store";
import { useWishlistStore } from "@/stores/wishlist-store";
import { toast } from "@/components/ui/toast";
import { Breadcrumb } from "@/components/ui/breadcrumb";
import { Modal } from "@/components/ui/modal";
import { ProductCard } from "@/components/product/product-card";
import { APP_CONFIG } from "@/constants";

export default function ProductDetailPage({
  params,
}: {
  params: Promise<{ slug: string }>;
}) {
  const resolvedParams = use(params);
  const slug = resolvedParams.slug;

  const [product, setProduct] = useState<Product | null>(null);
  const [relatedProducts, setRelatedProducts] = useState<Product[]>([]);
  const [isLoading, setIsLoading] = useState(true);
  const [error, setError] = useState<string | null>(null);

  const { addItem, openCart } = useCartStore();
  const { toggleItem, hasItem } = useWishlistStore();

  const [selectedColorIdx, setSelectedColorIdx] = useState(0);
  const [selectedSize, setSelectedSize] = useState<string>("M");
  const [activeImageIdx, setActiveImageIdx] = useState(0);
  const [quantity, setQuantity] = useState(1);
  const [isZoomOpen, setIsZoomOpen] = useState(false);
  const [isSizeGuideOpen, setIsSizeGuideOpen] = useState(false);
  const [activeTab, setActiveTab] = useState<"details" | "care" | "reviews">("details");

  const router = useRouter();
  const [isReviewModalOpen, setIsReviewModalOpen] = useState(false);
  const [userReview, setUserReview] = useState<UserReviewData | null>(null);
  const [userEligibility, setUserEligibility] = useState<UserEligibilityData | null>(null);

  const loadReviewDetails = async (identifier: string) => {
    try {
      const res = await ReviewsService.getReviews(identifier);
      if (res.userReview !== undefined) setUserReview(res.userReview);
      if (res.userEligibility !== undefined) setUserEligibility(res.userEligibility);
      if (res.stats) {
        setProduct((prev) =>
          prev
            ? {
                ...prev,
                rating: res.stats!.averageRating,
                reviewCount: res.stats!.totalCount,
                ratingStats: res.stats,
                reviews: res.data,
              }
            : prev
        );
      }
    } catch (err) {
      console.warn("[loadReviewDetails] Error loading review details:", err);
    }
  };

  const handleWriteReviewClick = () => {
    const token = ApiClient.getAuthToken();
    if (!token) {
      toast.error("Please sign in to your WearDay account to write a review.");
      router.push(`/auth/login?redirect=/products/${slug}#reviews`);
      return;
    }
    setIsReviewModalOpen(true);
  };

  useEffect(() => {
    let isMounted = true;
    setIsLoading(true);
    setError(null);

    Promise.all([
      ProductsService.getBySlug(slug),
      ProductsService.getRelated(slug),
    ])
      .then(([prod, related]) => {
        if (!isMounted) return;
        if (!prod) {
          setError("Product not found");
          setIsLoading(false);
          return;
        }
        setProduct(prod);
        setRelatedProducts(related);
        loadReviewDetails(slug);
        const defaultSize = prod.sizes?.find((s) => s.inStock)?.name || prod.sizes?.[0]?.name || "M";
        setSelectedSize(defaultSize);
        setIsLoading(false);
      })
      .catch((err) => {
        if (!isMounted) return;
        console.error("Failed to load product:", err);
        setError("Failed to load product details.");
        setIsLoading(false);
      });

    return () => {
      isMounted = false;
    };
  }, [slug]);

  if (isLoading) {
    return (
      <div className="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8 py-12 space-y-12 animate-pulse">
        <div className="h-4 w-48 bg-neutral-200 rounded" />
        <div className="grid grid-cols-1 lg:grid-cols-12 gap-10">
          <div className="lg:col-span-7 h-[500px] bg-neutral-100 rounded-3xl" />
          <div className="lg:col-span-5 space-y-6">
            <div className="h-6 w-32 bg-neutral-200 rounded" />
            <div className="h-10 w-3/4 bg-neutral-200 rounded" />
            <div className="h-6 w-24 bg-neutral-200 rounded" />
            <div className="h-24 w-full bg-neutral-100 rounded-2xl" />
            <div className="h-12 w-full bg-neutral-200 rounded-full" />
          </div>
        </div>
      </div>
    );
  }

  if (error || !product) {
    return (
      <div className="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8 py-20 text-center">
        <div className="w-16 h-16 rounded-full bg-neutral-100 border border-neutral-200 flex items-center justify-center mx-auto text-neutral-400 mb-4">
          <ShoppingBag className="w-8 h-8" />
        </div>
        <h2 className="text-xl font-black text-neutral-900 tracking-tight">Product Not Found</h2>
        <p className="text-xs text-neutral-500 mt-2 max-w-sm mx-auto">
          The requested garment is unavailable or may have been retired from our current collection.
        </p>
        <div className="mt-6">
          <Link
            href="/shop"
            className="inline-flex items-center px-6 py-3 rounded-full bg-neutral-900 text-white text-xs font-bold hover:bg-neutral-800 transition"
          >
            Browse All Clothing
          </Link>
        </div>
      </div>
    );
  }

  const isWishlisted = hasItem(product.id);
  const activeColor = product.colors[selectedColorIdx] || product.colors[0] || { name: "Default", hex: "#000" };
  const sizeObj = product.sizes.find((s) => s.name === selectedSize);

  // Match authoritative variant from backend database
  const matchedVariant =
    product.variants?.find(
      (v) =>
        v.size?.toLowerCase() === selectedSize.toLowerCase() &&
        (!v.color || v.color.toLowerCase() === activeColor.name.toLowerCase())
    ) ||
    product.variants?.find((v) => v.size?.toLowerCase() === selectedSize.toLowerCase()) ||
    product.variants?.[0];

  const currentPrice = matchedVariant?.price ?? product.price;
  const currentCompareAtPrice = matchedVariant?.compareAtPrice ?? product.compareAtPrice;
  const currentStockCount = matchedVariant?.stock ?? sizeObj?.stockCount ?? 15;
  const isSelectedSizeInStock = matchedVariant
    ? matchedVariant.stock > 0
    : sizeObj
    ? sizeObj.inStock
    : true;

  const handleAddToCart = () => {
    if (!isSelectedSizeInStock) {
      toast.error("Size Out of Stock", "Please select another size.");
      return;
    }

    addItem({
      productId: product.id,
      variantId: matchedVariant?.id ? String(matchedVariant.id) : undefined,
      slug: product.slug,
      name: product.name,
      price: currentPrice,
      originalPrice: currentCompareAtPrice,
      image: product.images[activeImageIdx] || product.images[0],
      selectedColor: activeColor.name,
      selectedSize,
      quantity,
      maxStock: currentStockCount,
    });

    toast.success("Added to Bag", `${product.name} (${selectedSize}, ${activeColor.name}) added.`);
    openCart();
  };

  const handleWishlistToggle = () => {
    const added = toggleItem(product.id);
    if (added) {
      toast.success("Added to Wishlist", `${product.name} saved to your wishlist.`);
    } else {
      toast.info("Removed from Wishlist", `${product.name} removed from your wishlist.`);
    }
  };

  const handleColorSelect = (idx: number) => {
    setSelectedColorIdx(idx);
    if (!product) return;

    const color = product.colors[idx];
    if (!color) return;

    // Synchronize main showcase photo with the selected color swatch
    const variantWithImage = product.variants?.find(
      (v) => v.color?.toLowerCase() === color.name.toLowerCase() && v.image
    );
    const targetImage = color.image || variantWithImage?.image;

    if (targetImage) {
      const foundIdx = product.images.findIndex((img) => img === targetImage);
      if (foundIdx !== -1) {
        setActiveImageIdx(foundIdx);
        return;
      }
    }

    // Direct fallback: match by swatch index if gallery contains corresponding photo
    if (product.images[idx]) {
      setActiveImageIdx(idx);
    }
  };

  return (
    <div className="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8 py-8 sm:py-12 space-y-16">
      {/* Breadcrumb */}
      <Breadcrumb
        items={[
          { label: "Home", href: "/" },
          { label: "Shop", href: "/shop" },
          { label: product.category, href: `/shop?category=${product.categorySlug}` },
          { label: product.name },
        ]}
      />

      {/* Main Product Layout */}
      <div className="grid grid-cols-1 lg:grid-cols-12 gap-10 lg:gap-14 items-start">
        {/* Left: Product Gallery (with zoom modal trigger) */}
        <div className="lg:col-span-7 flex flex-col-reverse md:flex-row gap-4">
          {/* Thumbnails */}
          {product.images.length > 1 && (
            <div className="flex md:flex-col gap-3 overflow-x-auto no-scrollbar md:w-20 shrink-0">
              {product.images.map((img, i) => (
                <button
                  key={i}
                  onClick={() => setActiveImageIdx(i)}
                  className={`relative w-18 h-24 md:w-20 md:h-26 rounded-2xl overflow-hidden border-2 transition-all shrink-0 cursor-pointer ${
                    activeImageIdx === i
                      ? "border-neutral-900 shadow-sm"
                      : "border-neutral-200/80 opacity-70 hover:opacity-100"
                  }`}
                >
                  <Image src={img} alt="Thumbnail" fill className="object-cover" />
                </button>
              ))}
            </div>
          )}

          {/* Main Showcase Image */}
          <div className="relative flex-1 aspect-3/4 rounded-3xl overflow-hidden bg-neutral-100 border border-neutral-200/70 shadow-xs group">
            <Image
              src={product.images[activeImageIdx] || product.images[0]}
              alt={product.name}
              fill
              priority
              className="object-cover cursor-zoom-in"
              onClick={() => setIsZoomOpen(true)}
            />

            {/* Sale Badge */}
            {product.isSale && (
              <span className="absolute top-4 left-4 px-3 py-1 text-xs font-bold uppercase tracking-wider bg-rose-600 text-white rounded-md shadow-xs">
                Sale
              </span>
            )}

            {/* Zoom Button */}
            <button
              onClick={() => setIsZoomOpen(true)}
              className="absolute bottom-4 right-4 p-2.5 rounded-full bg-white/90 text-neutral-800 shadow-md hover:bg-neutral-900 hover:text-white transition-all cursor-pointer"
              title="Click to Zoom Image"
            >
              <Maximize2 className="w-4 h-4" />
            </button>
          </div>
        </div>

        {/* Right: Dynamic Selection & Purchase Panel */}
        <div className="lg:col-span-5 space-y-6">
          <div>
            <span className="text-[11px] font-bold uppercase tracking-widest text-neutral-400">
              {product.gender.toUpperCase()} • {product.category}
            </span>
            <h1 className="text-2xl sm:text-3xl font-black text-neutral-900 tracking-tight mt-1">
              {product.name}
            </h1>
            <p className="text-xs text-neutral-500 mt-1">{product.subtitle}</p>

            {/* Rating & Review Count */}
            <div className="flex items-center gap-2 mt-3 text-xs text-neutral-600">
              {product.reviewCount > 0 ? (
                <>
                  <div className="flex items-center text-amber-400">
                    {Array.from({ length: 5 }).map((_, i) => (
                      <Star
                        key={i}
                        className={`w-3.5 h-3.5 ${
                          i < Math.round(product.rating) ? "fill-current" : "text-neutral-200"
                        }`}
                      />
                    ))}
                  </div>
                  <span className="font-bold text-neutral-900">{product.rating.toFixed(1)}</span>
                  <span className="text-neutral-400">•</span>
                  <a
                    href="#reviews"
                    onClick={() => setActiveTab("reviews")}
                    className="underline hover:text-neutral-900 cursor-pointer"
                  >
                    {product.reviewCount} customer {product.reviewCount === 1 ? "review" : "reviews"}
                  </a>
                </>
              ) : (
                <>
                  <div className="flex items-center text-neutral-300">
                    {Array.from({ length: 5 }).map((_, i) => (
                      <Star key={i} className="w-3.5 h-3.5 text-neutral-200" />
                    ))}
                  </div>
                  <span className="text-neutral-400">0.0 (0 reviews)</span>
                  <span className="text-neutral-300">•</span>
                  <a
                    href="#reviews"
                    onClick={() => setActiveTab("reviews")}
                    className="text-neutral-500 underline hover:text-neutral-900 cursor-pointer"
                  >
                    Be the first to review
                  </a>
                </>
              )}
            </div>
          </div>

          {/* Pricing & Sale Discount */}
          <div className="flex items-baseline gap-3 pb-4 border-b border-neutral-100">
            <span className="text-2xl font-black text-neutral-900">
              {formatPrice(currentPrice)}
            </span>
            {currentCompareAtPrice && currentCompareAtPrice > currentPrice && (
              <>
                <span className="text-sm text-neutral-400 line-through">
                  {formatPrice(currentCompareAtPrice)}
                </span>
                <span className="text-xs font-bold text-rose-600 bg-rose-50 px-2 py-0.5 rounded-md border border-rose-200">
                  Save {Math.round(((currentCompareAtPrice - currentPrice) / currentCompareAtPrice) * 100)}%
                </span>
              </>
            )}
          </div>

          {/* Color Selector */}
          <div className="space-y-2.5">
            <div className="flex items-center justify-between text-xs">
              <span className="font-bold text-neutral-900">
                Color: <span className="font-normal text-neutral-600">{activeColor.name}</span>
              </span>
            </div>
            <div className="flex items-center gap-2.5">
              {product.colors.map((color, idx) => (
                <button
                  key={color.name}
                  onClick={() => handleColorSelect(idx)}
                  className={`w-8 h-8 rounded-full border transition-all cursor-pointer ${
                    selectedColorIdx === idx
                      ? "ring-2 ring-neutral-900 ring-offset-2 border-white scale-105"
                      : "border-black/20 hover:scale-105"
                  }`}
                  style={{ backgroundColor: color.hex }}
                  title={color.name}
                />
              ))}
            </div>
          </div>

          {/* Size Selector with Size Guide */}
          <div className="space-y-2.5">
            <div className="flex items-center justify-between text-xs">
              <span className="font-bold text-neutral-900">
                Size: <span className="font-normal text-neutral-600">{selectedSize}</span>
              </span>
              <button
                type="button"
                onClick={() => setIsSizeGuideOpen(true)}
                className="inline-flex items-center gap-1 text-[11px] font-semibold text-neutral-600 hover:text-neutral-950 underline cursor-pointer"
              >
                <Ruler className="w-3.5 h-3.5" /> Size Guide
              </button>
            </div>
            <div className="flex flex-wrap gap-2">
              {product.sizes.map((sz) => (
                <button
                  key={sz.name}
                  disabled={!sz.inStock}
                  onClick={() => setSelectedSize(sz.name)}
                  className={`min-w-12 h-11 px-3 text-xs font-bold rounded-xl border transition-all cursor-pointer ${
                    !sz.inStock
                      ? "opacity-30 border-dashed line-through cursor-not-allowed bg-neutral-50 text-neutral-400"
                      : selectedSize === sz.name
                      ? "bg-neutral-900 text-white border-neutral-900 shadow-xs"
                      : "bg-white text-neutral-800 border-neutral-200 hover:border-neutral-400"
                  }`}
                >
                  {sz.name}
                </button>
              ))}
            </div>
          </div>

          {/* Stock Indicator */}
          <div className="text-xs">
            {isSelectedSizeInStock ? (
              <div className="flex items-center gap-2 text-emerald-700 font-semibold">
                <span className="w-2 h-2 rounded-full bg-emerald-500 animate-pulse" />
                <span>In Stock — Ready to dispatch in 24 hours</span>
              </div>
            ) : (
              <div className="flex items-center gap-2 text-rose-600 font-semibold">
                <span className="w-2 h-2 rounded-full bg-rose-500" />
                <span>Out of stock in {selectedSize}. Select another size.</span>
              </div>
            )}
          </div>

          {/* Quantity & Add to Cart */}
          <div className="space-y-3 pt-2">
            <div className="flex items-center gap-3">
              {/* Quantity Stepper */}
              <div className="flex items-center border border-neutral-200 rounded-full px-3 h-12 bg-white">
                <button
                  onClick={() => setQuantity(Math.max(1, quantity - 1))}
                  className="p-1 text-neutral-600 hover:text-neutral-950 transition-colors cursor-pointer"
                  aria-label="Decrease quantity"
                >
                  <Minus className="w-3.5 h-3.5" />
                </button>
                <span className="w-10 text-center text-xs font-bold text-neutral-900">
                  {quantity}
                </span>
                <button
                  onClick={() => setQuantity(Math.min(currentStockCount, quantity + 1))}
                  className="p-1 text-neutral-600 hover:text-neutral-950 transition-colors cursor-pointer"
                  aria-label="Increase quantity"
                >
                  <Plus className="w-3.5 h-3.5" />
                </button>
              </div>

              {/* Add to Bag Button */}
              <button
                onClick={handleAddToCart}
                disabled={!isSelectedSizeInStock}
                className="flex-1 h-12 bg-neutral-950 hover:bg-neutral-800 text-white rounded-full font-bold text-xs sm:text-sm flex items-center justify-center gap-2 shadow-md transition-all active:scale-98 cursor-pointer disabled:opacity-50"
              >
                <ShoppingBag className="w-4 h-4" />
                <span>Add to Bag • {formatPrice(currentPrice * quantity)}</span>
              </button>

              {/* Wishlist Button */}
              <button
                onClick={handleWishlistToggle}
                className={`w-12 h-12 rounded-full border flex items-center justify-center transition-all cursor-pointer ${
                  isWishlisted
                    ? "bg-rose-50 border-rose-200 text-rose-600"
                    : "border-neutral-200 hover:bg-neutral-50 text-neutral-700"
                }`}
                aria-label="Wishlist"
              >
                <Heart className={`w-5 h-5 ${isWishlisted ? "fill-rose-600" : ""}`} />
              </button>
            </div>
          </div>

          {/* Value Props Strip */}
          <div className="p-4 rounded-2xl bg-neutral-50 border border-neutral-100 divide-y divide-neutral-100 text-xs text-neutral-600 space-y-2">
            <div className="flex items-center gap-2.5 pb-2">
              <Truck className="w-4 h-4 text-neutral-700 shrink-0" />
              <span>Complimentary shipping on orders above ${APP_CONFIG.freeShippingThreshold}.</span>
            </div>
            <div className="flex items-center gap-2.5 pt-2">
              <RotateCcw className="w-4 h-4 text-neutral-700 shrink-0" />
              <span>Hassle-free 30-day returns and exchanges.</span>
            </div>
            <div className="flex items-center gap-2.5 pt-2">
              <ShieldCheck className="w-4 h-4 text-neutral-700 shrink-0" />
              <span>Pre-washed organic fibers guaranteed against post-wash shrinkage.</span>
            </div>
          </div>
        </div>
      </div>

      {/* Tabs: Description, Details, Care, Reviews */}
      <div className="pt-10 border-t border-neutral-200" id="reviews">
        <div className="flex border-b border-neutral-200 gap-8 pb-3">
          {[
            { id: "details", label: "Product Specifications" },
            { id: "care", label: "Fabric & Sustainable Care" },
            { id: "reviews", label: `Customer Reviews (${product.reviewCount})` },
          ].map((tab) => (
            <button
              key={tab.id}
              onClick={() => setActiveTab(tab.id as any)}
              className={`text-sm font-bold pb-2 transition-colors relative cursor-pointer ${
                activeTab === tab.id ? "text-neutral-900" : "text-neutral-400 hover:text-neutral-700"
              }`}
            >
              {tab.label}
              {activeTab === tab.id && (
                <span className="absolute bottom-0 left-0 right-0 h-0.5 bg-neutral-900 rounded-full" />
              )}
            </button>
          ))}
        </div>

        <div className="py-6">
          {activeTab === "details" && (
            <div className="grid grid-cols-1 md:grid-cols-2 gap-8 text-xs text-neutral-700 leading-relaxed">
              <div className="space-y-4">
                <h3 className="font-bold text-sm text-neutral-900">Design Overview</h3>
                <p>{product.description}</p>
                <div className="pt-2">
                  <span className="font-bold text-neutral-900">Silhouette & Fit:</span>
                  <p className="mt-1 text-neutral-600">{product.fit}</p>
                </div>
              </div>
              <div className="space-y-3">
                <h3 className="font-bold text-sm text-neutral-900">Key Features</h3>
                <ul className="space-y-2">
                  {product.details.map((detail, idx) => (
                    <li key={idx} className="flex items-start gap-2">
                      <Check className="w-4 h-4 text-emerald-600 shrink-0 mt-0.5" />
                      <span>{detail}</span>
                    </li>
                  ))}
                </ul>
              </div>
            </div>
          )}

          {activeTab === "care" && (
            <div className="space-y-4 text-xs text-neutral-700 max-w-2xl leading-relaxed">
              <h3 className="font-bold text-sm text-neutral-900">Fabric Composition</h3>
              <p className="font-semibold text-neutral-900">{product.fabric}</p>
              <h3 className="font-bold text-sm text-neutral-900 pt-2">Recommended Washing Instructions</h3>
              <ul className="space-y-2 list-disc pl-4 text-neutral-600">
                {product.care.map((c, idx) => (
                  <li key={idx}>{c}</li>
                ))}
              </ul>
            </div>
          )}

          {activeTab === "reviews" && (
            <div className="space-y-6">
              {/* Customer's Own Review Status Banner (if already submitted) */}
              {userReview && (
                <div
                  className={cn(
                    "p-5 rounded-2xl border text-xs space-y-2.5 max-w-2xl animate-in fade-in",
                    userReview.isApproved
                      ? "bg-emerald-50/70 border-emerald-200/80 text-emerald-950"
                      : "bg-amber-50/70 border-amber-200/80 text-amber-950"
                  )}
                >
                  <div className="flex flex-wrap items-center justify-between gap-2">
                    <span className="font-bold flex items-center gap-1.5">
                      <Clock
                        className={cn(
                          "w-4 h-4",
                          userReview.isApproved ? "text-emerald-600" : "text-amber-600"
                        )}
                      />
                      {userReview.isApproved
                        ? "Your Review is Published"
                        : "Your Review is Pending Moderation"}
                    </span>
                    <div className="flex items-center gap-2">
                      <span
                        className={cn(
                          "text-[10px] uppercase font-bold px-2 py-0.5 rounded-full border",
                          userReview.isApproved
                            ? "bg-emerald-100 text-emerald-800 border-emerald-300"
                            : "bg-amber-100 text-amber-800 border-amber-300"
                        )}
                      >
                        {userReview.isApproved ? "Published" : "Pending Approval"}
                      </span>
                      <button
                        onClick={handleWriteReviewClick}
                        className="text-[11px] font-semibold underline hover:text-neutral-900 cursor-pointer"
                      >
                        Edit Your Review
                      </button>
                    </div>
                  </div>

                  <div className="flex items-center gap-1 text-amber-400">
                    {Array.from({ length: 5 }).map((_, i) => (
                      <Star
                        key={i}
                        className={`w-3.5 h-3.5 ${
                          i < userReview.rating ? "fill-current" : "text-neutral-200"
                        }`}
                      />
                    ))}
                  </div>

                  <div>
                    <h5 className="font-bold text-neutral-900">{userReview.title}</h5>
                    <p className="text-neutral-700 leading-relaxed mt-0.5">{userReview.comment}</p>
                  </div>

                  {!userReview.isApproved && (
                    <p className="text-[11px] text-amber-800/80 italic pt-1 border-t border-amber-200/60">
                      Your review is undergoing routine moderation for customer authenticity and will be publicly visible upon approval.
                    </p>
                  )}
                </div>
              )}

              {product.reviewCount > 0 ? (
                <>
                  <div className="flex flex-col lg:flex-row items-stretch lg:items-center justify-between gap-6 p-6 rounded-3xl bg-neutral-50 border border-neutral-200/70">
                    {/* Rating Breakdown */}
                    <div className="flex flex-col sm:flex-row items-center gap-6 flex-1">
                      <div className="text-center shrink-0">
                        <div className="text-4xl font-black text-neutral-900 tracking-tight">
                          {product.rating.toFixed(1)}
                        </div>
                        <div className="flex items-center text-amber-400 justify-center mt-1">
                          {Array.from({ length: 5 }).map((_, i) => (
                            <Star
                              key={i}
                              className={`w-3.5 h-3.5 ${
                                i < Math.round(product.rating) ? "fill-current" : "text-neutral-200"
                              }`}
                            />
                          ))}
                        </div>
                        <div className="text-[10px] text-neutral-400 mt-1 font-medium">
                          Based on {product.reviewCount} {product.reviewCount === 1 ? "review" : "reviews"}
                        </div>
                      </div>

                      <div className="w-full flex-1 space-y-1.5 text-[11px] text-neutral-500">
                        {(product.ratingStats?.breakdown || [
                          { star: 5, percentage: product.ratingStats?.percentages?.["5"] ?? 0, count: product.ratingStats?.counts?.["5"] ?? 0 },
                          { star: 4, percentage: product.ratingStats?.percentages?.["4"] ?? 0, count: product.ratingStats?.counts?.["4"] ?? 0 },
                          { star: 3, percentage: product.ratingStats?.percentages?.["3"] ?? 0, count: product.ratingStats?.counts?.["3"] ?? 0 },
                          { star: 2, percentage: product.ratingStats?.percentages?.["2"] ?? 0, count: product.ratingStats?.counts?.["2"] ?? 0 },
                          { star: 1, percentage: product.ratingStats?.percentages?.["1"] ?? 0, count: product.ratingStats?.counts?.["1"] ?? 0 },
                        ]).map((item) => (
                          <div key={item.star} className="flex items-center gap-2">
                            <span className="w-5 text-right font-medium">{item.star} ★</span>
                            <div className="h-1.5 flex-1 bg-neutral-200 rounded-full overflow-hidden">
                              <div
                                className="h-full bg-neutral-900 transition-all duration-300"
                                style={{ width: `${item.percentage}%` }}
                              />
                            </div>
                            <span className="w-10 text-right text-neutral-400 font-mono text-[10px]">
                              {item.percentage}%
                            </span>
                          </div>
                        ))}
                      </div>
                    </div>

                    {/* Write a Review Button Card */}
                    <div className="lg:border-l lg:border-neutral-200 lg:pl-8 flex flex-col items-center lg:items-end justify-center gap-2 shrink-0">
                      <button
                        onClick={handleWriteReviewClick}
                        className="w-full sm:w-auto px-6 py-3 bg-neutral-900 hover:bg-neutral-800 text-white text-xs font-bold rounded-full transition-all flex items-center justify-center gap-2 shadow-xs cursor-pointer active:scale-98"
                      >
                        <PenLine className="w-4 h-4" />
                        <span>Write a Review</span>
                      </button>
                      <p className="text-[11px] text-neutral-400 text-center lg:text-right">
                        Share your fit and fabric experience
                      </p>
                    </div>
                  </div>

                  {/* Individual Verified Reviews List */}
                  <div className="pt-4 space-y-4">
                    <div className="flex items-center justify-between">
                      <h4 className="text-xs font-bold uppercase tracking-wider text-neutral-400">
                        Verified Customer Reviews ({product.reviews?.length || 0})
                      </h4>
                      <button
                        onClick={handleWriteReviewClick}
                        className="text-xs font-bold text-neutral-900 underline hover:text-neutral-700 cursor-pointer sm:hidden"
                      >
                        Write a Review
                      </button>
                    </div>

                    {product.reviews && product.reviews.length > 0 ? (
                      <div className="grid grid-cols-1 md:grid-cols-2 gap-4">
                        {product.reviews.map((rev) => (
                          <div
                            key={rev.id}
                            className="p-5 rounded-2xl bg-white border border-neutral-200/80 shadow-xs space-y-3"
                          >
                            <div className="flex items-center justify-between">
                              <div className="flex items-center gap-1 text-amber-400">
                                {Array.from({ length: 5 }).map((_, i) => (
                                  <Star
                                    key={i}
                                    className={`w-3.5 h-3.5 ${
                                      i < rev.rating ? "fill-current" : "text-neutral-200"
                                    }`}
                                  />
                                ))}
                              </div>
                              <span className="text-[11px] text-neutral-400 font-mono">{rev.date}</span>
                            </div>

                            <div>
                              <h5 className="text-xs font-bold text-neutral-900">{rev.title}</h5>
                              <p className="text-xs text-neutral-600 mt-1 leading-relaxed">{rev.comment}</p>
                            </div>

                            <div className="flex items-center gap-2 pt-1 border-t border-neutral-100 text-[11px] text-neutral-500">
                              <span className="font-semibold text-neutral-800">{rev.author}</span>
                              {rev.verified && (
                                <span className="inline-flex items-center gap-0.5 text-[10px] font-bold text-emerald-700 bg-emerald-50 px-1.5 py-0.5 rounded-full">
                                  <CheckCircle2 className="w-3 h-3 text-emerald-600" /> Verified Buyer
                                </span>
                              )}
                            </div>
                          </div>
                        ))}
                      </div>
                    ) : (
                      <div className="p-8 rounded-2xl bg-neutral-50 border border-neutral-200/70 text-center space-y-2">
                        <p className="text-xs font-semibold text-neutral-700">No review comments available yet.</p>
                      </div>
                    )}
                  </div>
                </>
              ) : (
                <div className="p-8 sm:p-10 rounded-3xl bg-neutral-50 border border-neutral-200/70 text-center space-y-4 max-w-xl">
                  <div className="flex items-center justify-center gap-1 text-neutral-300">
                    {Array.from({ length: 5 }).map((_, i) => (
                      <Star key={i} className="w-5 h-5 text-neutral-300" />
                    ))}
                  </div>
                  <div>
                    <h4 className="text-sm font-bold text-neutral-900">No customer reviews yet</h4>
                    <p className="text-xs text-neutral-500 max-w-sm mx-auto mt-1">
                      Be the first to review your experience with this piece after purchasing.
                    </p>
                  </div>
                  <div>
                    <button
                      onClick={handleWriteReviewClick}
                      className="px-6 py-2.5 bg-neutral-900 hover:bg-neutral-800 text-white text-xs font-bold rounded-full transition-all inline-flex items-center gap-2 shadow-xs cursor-pointer active:scale-98"
                    >
                      <PenLine className="w-4 h-4" />
                      <span>Write the First Review</span>
                    </button>
                  </div>
                </div>
              )}
            </div>
          )}
        </div>
      </div>

      {/* Related Products */}
      <div className="pt-10 border-t border-neutral-200">
        <h2 className="text-xl font-black text-neutral-900 tracking-tight mb-6">
          Pair It With / Related Styles
        </h2>
        <div className="grid grid-cols-2 sm:grid-cols-4 gap-4 sm:gap-6">
          {relatedProducts.map((p) => (
            <ProductCard key={p.id} product={p} />
          ))}
        </div>
      </div>

      {/* Fullscreen Image Zoom Modal */}
      <Modal isOpen={isZoomOpen} onClose={() => setIsZoomOpen(false)} maxWidth="4xl">
        <div className="relative aspect-4/5 w-full rounded-2xl overflow-hidden bg-neutral-100">
          <Image
            src={product.images[activeImageIdx] || product.images[0]}
            alt={product.name}
            fill
            className="object-contain"
          />
        </div>
      </Modal>

      {/* Sizing Guide Modal */}
      <Modal
        isOpen={isSizeGuideOpen}
        onClose={() => setIsSizeGuideOpen(false)}
        maxWidth="lg"
        title="Garment Sizing Guide"
        description="All measurements are in inches. Measured flat across the garment."
      >
        <div className="overflow-x-auto text-xs pt-2">
          <table className="w-full text-left border-collapse">
            <thead>
              <tr className="border-b border-neutral-200 font-bold text-neutral-900">
                <th className="py-2">Size</th>
                <th className="py-2">Chest / Waist</th>
                <th className="py-2">Length</th>
                <th className="py-2">Sleeve</th>
              </tr>
            </thead>
            <tbody className="divide-y divide-neutral-100 text-neutral-600">
              <tr>
                <td className="py-2.5 font-bold text-neutral-900">S</td>
                <td>36 - 38 in</td>
                <td>27.5 in</td>
                <td>33.5 in</td>
              </tr>
              <tr>
                <td className="py-2.5 font-bold text-neutral-900">M</td>
                <td>39 - 41 in</td>
                <td>28.5 in</td>
                <td>34.5 in</td>
              </tr>
              <tr>
                <td className="py-2.5 font-bold text-neutral-900">L</td>
                <td>42 - 44 in</td>
                <td>29.5 in</td>
                <td>35.5 in</td>
              </tr>
              <tr>
                <td className="py-2.5 font-bold text-neutral-900">XL</td>
                <td>45 - 47 in</td>
                <td>30.5 in</td>
                <td>36.5 in</td>
              </tr>
            </tbody>
          </table>
          <p className="text-[11px] text-neutral-500 mt-4 leading-relaxed">
            Need fit advice? Our stylists are available 7 days a week at care@wearday.com.
          </p>
        </div>
      </Modal>
      {/* Customer Review Modal */}
      {product && (
        <ReviewModal
          isOpen={isReviewModalOpen}
          onClose={() => setIsReviewModalOpen(false)}
          product={product}
          hasPurchased={userEligibility?.hasPurchased ?? false}
          onSuccess={() => loadReviewDetails(product.slug || product.id)}
        />
      )}
    </div>
  );
}

