"use client";

import React, { useState } from "react";
import Image from "next/image";
import Link from "next/link";
import { Plus, Equal, Check, ShoppingBag, Sparkles, ArrowRight } from "lucide-react";
import { FEATURED_OUTFIT_BUNDLE, OutfitItem } from "@/lib/mock-data/outfits";
import { formatPrice } from "@/lib/utils";
import { useCartStore } from "@/stores/cart-store";
import { toast } from "@/components/ui/toast";
import { Modal } from "@/components/ui/modal";

export const OutfitBuilder: React.FC = () => {
  const { addItem, openCart } = useCartStore();
  const [selectedItems, setSelectedItems] = useState<string[]>(
    FEATURED_OUTFIT_BUNDLE.items.map((i) => i.id)
  );
  const [isBuilderModalOpen, setIsBuilderModalOpen] = useState(false);
  const [itemSizes, setItemSizes] = useState<Record<string, string>>({
    "prod-6": "M",
    "prod-3": "32",
    "prod-7": "L",
    "prod-8": "42",
  });

  const toggleItemSelection = (id: string) => {
    if (selectedItems.includes(id)) {
      if (selectedItems.length <= 1) {
        toast.info("At least one item must remain in your look.");
        return;
      }
      setSelectedItems(selectedItems.filter((i) => i !== id));
    } else {
      setSelectedItems([...selectedItems, id]);
    }
  };

  const activeItems = FEATURED_OUTFIT_BUNDLE.items.filter((item) =>
    selectedItems.includes(item.id)
  );

  const subtotal = activeItems.reduce((acc, curr) => acc + curr.price, 0);

  const handleAddLookToBag = () => {
    activeItems.forEach((item) => {
      addItem({
        productId: item.id,
        slug: item.slug,
        name: item.name,
        price: item.price,
        image: item.image,
        selectedColor: item.color,
        selectedSize: itemSizes[item.id] || "M",
        quantity: 1,
        maxStock: 25,
      });
    });

    setIsBuilderModalOpen(false);
    toast.success("Complete Look Added!", `${activeItems.length} outfit essentials added to your bag.`);
    openCart();
  };

  return (
    <section className="my-14 sm:my-20">
      <div className="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8">
        <div className="rounded-3xl bg-neutral-100/80 border border-neutral-200/80 p-5 sm:p-8">
          <div className="grid grid-cols-1 lg:grid-cols-12 gap-6 items-center">
            {/* Left Header Box */}
            <div className="lg:col-span-3 space-y-3">
              <span className="text-[11px] font-bold uppercase tracking-wider text-neutral-500">
                Curated Styling
              </span>
              <h2 className="text-2xl sm:text-3xl font-black text-neutral-900 tracking-tight leading-tight">
                {FEATURED_OUTFIT_BUNDLE.tagline}
              </h2>
              <p className="text-xs sm:text-sm text-neutral-600 leading-relaxed">
                {FEATURED_OUTFIT_BUNDLE.subtitle}
              </p>
              <div className="pt-2">
                <button
                  onClick={() => setIsBuilderModalOpen(true)}
                  className="px-6 py-3 bg-neutral-900 hover:bg-neutral-800 text-white text-xs font-bold rounded-full transition-all shadow-sm active:scale-98 cursor-pointer"
                >
                  Start Building
                </button>
              </div>
            </div>

            {/* Right Interactive Formula Grid (Matching photo) */}
            <div className="lg:col-span-9 overflow-x-auto no-scrollbar py-2">
              <div className="flex items-center gap-3 sm:gap-4 min-w-[700px]">
                {FEATURED_OUTFIT_BUNDLE.items.map((item, idx) => (
                  <React.Fragment key={item.id}>
                    <div className="flex-1 bg-white rounded-2xl p-3 border border-neutral-200/70 shadow-xs flex flex-col items-center text-center group">
                      <div className="relative w-24 h-32 rounded-xl overflow-hidden bg-neutral-50 mb-2">
                        <Image
                          src={item.image}
                          alt={item.name}
                          fill
                          className="object-cover group-hover:scale-105 transition-transform duration-300"
                        />
                      </div>
                      <h4 className="text-xs font-bold text-neutral-900 truncate w-full">
                        {item.name}
                      </h4>
                      <span className="text-xs font-semibold text-neutral-500 mt-0.5">
                        {formatPrice(item.price)}
                      </span>
                    </div>

                    {idx < FEATURED_OUTFIT_BUNDLE.items.length - 1 ? (
                      <span className="text-neutral-400 font-light text-base shrink-0">+</span>
                    ) : (
                      <span className="text-neutral-400 font-light text-base shrink-0">=</span>
                    )}
                  </React.Fragment>
                ))}

                {/* Final Composite Look Tile */}
                <div className="flex-1 bg-white rounded-2xl p-3 border-2 border-neutral-900/10 shadow-sm flex flex-col items-center text-center">
                  <div className="relative w-24 h-32 rounded-xl overflow-hidden bg-neutral-100 mb-2">
                    <Image
                      src="https://images.unsplash.com/photo-1507679799987-c73779587ccf?w=400&auto=format&fit=crop&q=80"
                      alt="Your Look"
                      fill
                      className="object-cover"
                    />
                  </div>
                  <h4 className="text-xs font-black text-neutral-900">Your Look</h4>
                  <span className="text-xs font-bold text-neutral-900 mt-0.5">
                    {formatPrice(subtotal)}
                  </span>
                </div>
              </div>
            </div>
          </div>
        </div>
      </div>

      {/* Outfit Customizer Modal */}
      <Modal
        isOpen={isBuilderModalOpen}
        onClose={() => setIsBuilderModalOpen(false)}
        maxWidth="3xl"
        title="Customize Your Everyday Look"
        description="Select your preferred size for each garment, or toggle items off before adding the bundle."
      >
        <div className="space-y-6 pt-2">
          <div className="grid grid-cols-1 sm:grid-cols-2 gap-4">
            {FEATURED_OUTFIT_BUNDLE.items.map((item) => {
              const isSelected = selectedItems.includes(item.id);

              return (
                <div
                  key={item.id}
                  className={`p-4 rounded-2xl border transition-all ${
                    isSelected
                      ? "border-neutral-900 bg-white shadow-xs"
                      : "border-neutral-200 bg-neutral-50 opacity-60"
                  }`}
                >
                  <div className="flex items-start gap-3">
                    <div className="relative w-16 h-20 rounded-xl overflow-hidden bg-neutral-100 shrink-0">
                      <Image src={item.image} alt={item.name} fill className="object-cover" />
                    </div>
                    <div className="flex-1 min-w-0">
                      <div className="flex items-center justify-between">
                        <h4 className="text-xs font-bold text-neutral-900">{item.name}</h4>
                        <input
                          type="checkbox"
                          checked={isSelected}
                          onChange={() => toggleItemSelection(item.id)}
                          className="w-4 h-4 rounded text-neutral-900 cursor-pointer accent-neutral-900"
                        />
                      </div>
                      <p className="text-xs text-neutral-500 mt-0.5">{item.color}</p>
                      <p className="text-xs font-bold text-neutral-900 mt-1">
                        {formatPrice(item.price)}
                      </p>

                      {isSelected && (
                        <div className="mt-2 flex items-center gap-1.5">
                          <span className="text-[10px] font-semibold text-neutral-400 uppercase">
                            Size:
                          </span>
                          {["S", "M", "L", "XL", "32", "42"].map((sz) => (
                            <button
                              key={sz}
                              type="button"
                              onClick={() => setItemSizes({ ...itemSizes, [item.id]: sz })}
                              className={`px-2 py-0.5 text-[10px] font-semibold rounded-md border ${
                                itemSizes[item.id] === sz
                                  ? "bg-neutral-900 text-white border-neutral-900"
                                  : "bg-white text-neutral-700 border-neutral-200 hover:border-neutral-400"
                              }`}
                            >
                              {sz}
                            </button>
                          ))}
                        </div>
                      )}
                    </div>
                  </div>
                </div>
              );
            })}
          </div>

          <div className="pt-4 border-t border-neutral-100 flex flex-col sm:flex-row items-center justify-between gap-4">
            <div>
              <span className="text-xs text-neutral-500">Selected ({activeItems.length} items):</span>
              <div className="text-xl font-black text-neutral-900">
                {formatPrice(subtotal)}
              </div>
            </div>

            <button
              onClick={handleAddLookToBag}
              className="w-full sm:w-auto px-8 py-3.5 bg-neutral-900 hover:bg-neutral-800 text-white rounded-full text-xs font-bold shadow-md flex items-center justify-center gap-2 cursor-pointer transition-all active:scale-98"
            >
              <ShoppingBag className="w-4 h-4" /> Add All {activeItems.length} Items to Bag
            </button>
          </div>
        </div>
      </Modal>
    </section>
  );
};

