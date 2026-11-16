"use client";

import React, { useState, useEffect, use } from "react";
import Image from "next/image";
import Link from "next/link";
import { notFound } from "next/navigation";
import {
  Heart,
  ShoppingBag,
  Star,
  Truck,
  RotateCcw,
  ShieldCheck,
  Check,
  Ruler,
  Maximize2,
  ChevronRight,
  Plus,
  Minus,
  Loader2,
} from "lucide-react";
import { ProductsService } from "@/services/products.service";
import { Product } from "@/types/product";
import { formatPrice } from "@/lib/utils";
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
  const isSelectedSizeInStock = sizeObj ? sizeObj.inStock : true;
  const currentStockCount = sizeObj?.stockCount || 15;

  const handleAddToCart = () => {
    if (!isSelectedSizeInStock) {
      toast.error("Size Out of Stock", "Please select another size.");
      return;
    }

    addItem({
      productId: product.id,
      slug: product.slug,
      name: product.name,
      price: product.price,
      originalPrice: product.compareAtPrice,
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
              <div className="flex items-center text-amber-400">
                {Array.from({ length: 5 }).map((_, i) => (
                  <Star key={i} className="w-3.5 h-3.5 fill-current" />
                ))}
              </div>
              <span className="font-bold text-neutral-900">{product.rating}</span>
              <span className="text-neutral-400">•</span>
              <a href="#reviews" onClick={() => setActiveTab("reviews")} className="underline hover:text-neutral-900">
                {product.reviewCount} customer reviews
              </a>
            </div>
          </div>

          {/* Pricing & Sale Discount */}
          <div className="flex items-baseline gap-3 pb-4 border-b border-neutral-100">
            <span className="text-2xl font-black text-neutral-900">
              {formatPrice(product.price)}
            </span>
            {product.compareAtPrice && product.compareAtPrice > product.price && (
              <>
                <span className="text-sm text-neutral-400 line-through">
                  {formatPrice(product.compareAtPrice)}
                </span>
                <span className="text-xs font-bold text-rose-600 bg-rose-50 px-2 py-0.5 rounded-md border border-rose-200">
                  Save {Math.round(((product.compareAtPrice - product.price) / product.compareAtPrice) * 100)}%
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
                  onClick={() => setSelectedColorIdx(idx)}
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
                <span>Add to Bag • {formatPrice(product.price * quantity)}</span>
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
              <div className="flex items-center gap-6 p-6 rounded-2xl bg-neutral-50 border border-neutral-100 max-w-xl">
                <div className="text-center">
                  <div className="text-3xl font-black text-neutral-900">{product.rating}</div>
                  <div className="flex items-center text-amber-400 justify-center mt-1">
                    {Array.from({ length: 5 }).map((_, i) => (
                      <Star key={i} className="w-3.5 h-3.5 fill-current" />
                    ))}
                  </div>
                  <div className="text-[10px] text-neutral-400 mt-0.5">Based on {product.reviewCount} reviews</div>
                </div>
                <div className="flex-1 space-y-1.5 text-[11px] text-neutral-500">
                  <div className="flex items-center gap-2">
                    <span>5 ★</span>
                    <div className="h-1.5 flex-1 bg-neutral-200 rounded-full overflow-hidden">
                      <div className="h-full bg-neutral-900 w-[85%]" />
                    </div>
                    <span>85%</span>
                  </div>
                  <div className="flex items-center gap-2">
                    <span>4 ★</span>
                    <div className="h-1.5 flex-1 bg-neutral-200 rounded-full overflow-hidden">
                      <div className="h-full bg-neutral-900 w-[12%]" />
                    </div>
                    <span>12%</span>
                  </div>
                  <div className="flex items-center gap-2">
                    <span>3 ★</span>
                    <div className="h-1.5 flex-1 bg-neutral-200 rounded-full overflow-hidden">
                      <div className="h-full bg-neutral-900 w-[3%]" />
                    </div>
                    <span>3%</span>
                  </div>
                </div>
              </div>
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
    </div>
  );
}

