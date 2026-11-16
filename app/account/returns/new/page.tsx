"use client";

import React, { useState, Suspense } from "react";
import Image from "next/image";
import Link from "next/link";
import { useRouter, useSearchParams } from "next/navigation";
import { MOCK_ORDERS } from "@/lib/mock-data/orders";
import { RETURN_REASONS } from "@/constants";
import { ReturnsService } from "@/services/returns.service";
import { Input } from "@/components/ui/input";
import { Button } from "@/components/ui/button";
import { toast } from "@/components/ui/toast";
import { formatPrice } from "@/lib/utils";
import {
  ArrowLeft,
  RotateCcw,
  Upload,
  CheckCircle2,
  RefreshCw,
  CreditCard,
} from "lucide-react";

function ReturnFormContent() {
  const router = useRouter();
  const searchParams = useSearchParams();
  const preselectedOrderId = searchParams.get("orderId") || MOCK_ORDERS[0]?.id;

  const [selectedOrderId, setSelectedOrderId] = useState(preselectedOrderId);
  const currentOrder = MOCK_ORDERS.find((o) => o.id === selectedOrderId) || MOCK_ORDERS[0];

  const [selectedItems, setSelectedItems] = useState<string[]>(
    currentOrder.items.length > 0 ? [currentOrder.items[0].id] : []
  );
  const [returnReasons, setReturnReasons] = useState<Record<string, string>>({});
  const [returnComments, setReturnComments] = useState<Record<string, string>>({});
  const [resolution, setResolution] = useState<"refund" | "exchange">("refund");
  const [exchangeDetails, setExchangeDetails] = useState("");
  const [uploadedFileName, setUploadedFileName] = useState<string | null>(null);
  const [isSubmitting, setIsSubmitting] = useState(false);

  const toggleItem = (itemId: string) => {
    if (selectedItems.includes(itemId)) {
      setSelectedItems(selectedItems.filter((id) => id !== itemId));
    } else {
      setSelectedItems([...selectedItems, itemId]);
    }
  };

  const handleFileUpload = (e: React.ChangeEvent<HTMLInputElement>) => {
    if (e.target.files && e.target.files[0]) {
      setUploadedFileName(e.target.files[0].name);
      toast.success("Image Uploaded", "Photo attachment recorded.");
    }
  };

  const handleSubmit = async (e: React.FormEvent) => {
    e.preventDefault();
    if (selectedItems.length === 0) {
      toast.error("Selection Required", "Please select at least one garment to return.");
      return;
    }

    try {
      setIsSubmitting(true);
      const itemsToReturn = currentOrder.items
        .filter((item) => selectedItems.includes(item.id))
        .map((item) => ({
          orderItemId: item.id,
          productId: item.productId,
          name: item.name,
          color: item.color,
          size: item.size,
          image: item.image,
          price: item.price,
          quantity: item.quantity,
          reason: (returnReasons[item.id] || "wrong_size") as any,
          comment: returnComments[item.id] || "Standard return request",
        }));

      const newReturn = await ReturnsService.createReturn({
        orderId: currentOrder.id,
        orderNumber: currentOrder.orderNumber,
        items: itemsToReturn,
        resolution,
        exchangeDetails: resolution === "exchange" ? exchangeDetails : undefined,
      });

      toast.success("Return Authorized", `RMA #${newReturn.rmaNumber} created.`);
      router.push(`/account/returns/${newReturn.id}`);
    } catch (err: any) {
      toast.error("Error", err.message || "Failed to process return.");
    } finally {
      setIsSubmitting(false);
    }
  };

  return (
    <div className="space-y-6">
      <div className="flex items-center justify-between">
        <Link
          href="/account/returns"
          className="inline-flex items-center gap-1.5 text-xs font-semibold text-neutral-600 hover:text-neutral-950 transition-colors"
        >
          <ArrowLeft className="w-4 h-4" /> Back to Returns Portal
        </Link>
      </div>

      <div className="bg-white rounded-3xl border border-neutral-200 p-6 sm:p-8 shadow-xs">
        <div className="border-b border-neutral-100 pb-4 mb-6">
          <h1 className="text-xl sm:text-2xl font-black text-neutral-900 tracking-tight">
            Start a Return or Exchange
          </h1>
          <p className="text-xs text-neutral-500 mt-1">
            Select your order, choose items to return, and receive an instant prepaid shipping label.
          </p>
        </div>

        <form onSubmit={handleSubmit} className="space-y-8">
          {/* 1. Order Picker */}
          <div className="space-y-2">
            <label className="text-xs font-bold uppercase tracking-wider text-neutral-900 block">
              1. Select Order
            </label>
            <select
              value={selectedOrderId}
              onChange={(e) => {
                setSelectedOrderId(e.target.value);
                setSelectedItems([]);
              }}
              className="w-full sm:w-80 h-11 px-4 text-xs font-semibold bg-neutral-50 border border-neutral-200 rounded-xl focus:outline-none focus:ring-1 focus:ring-neutral-900"
            >
              {MOCK_ORDERS.map((o) => (
                <option key={o.id} value={o.id}>
                  Order #{o.orderNumber} ({formatPrice(o.total)})
                </option>
              ))}
            </select>
          </div>

          {/* 2. Items Selection */}
          <div className="space-y-3">
            <label className="text-xs font-bold uppercase tracking-wider text-neutral-900 block">
              2. Select Garments to Return
            </label>
            <div className="divide-y divide-neutral-100 border border-neutral-200 rounded-2xl overflow-hidden">
              {currentOrder.items.map((item) => {
                const isSelected = selectedItems.includes(item.id);

                return (
                  <div
                    key={item.id}
                    className={`p-4 transition-colors ${
                      isSelected ? "bg-neutral-50/80" : "bg-white"
                    }`}
                  >
                    <div className="flex items-start gap-4">
                      <input
                        type="checkbox"
                        checked={isSelected}
                        onChange={() => toggleItem(item.id)}
                        className="mt-1 w-4 h-4 rounded text-neutral-900 accent-neutral-900 cursor-pointer"
                      />
                      <div className="relative w-14 h-18 rounded-xl overflow-hidden bg-neutral-100 shrink-0">
                        <Image src={item.image} alt={item.name} fill className="object-cover" />
                      </div>
                      <div className="flex-1 min-w-0">
                        <h4 className="text-xs font-bold text-neutral-900">{item.name}</h4>
                        <p className="text-[11px] text-neutral-500">
                          {item.color} • Size {item.size} • Qty {item.quantity}
                        </p>
                        <p className="text-xs font-bold text-neutral-900 mt-1">
                          {formatPrice(item.price)}
                        </p>

                        {/* If checked, show reason dropdown and notes */}
                        {isSelected && (
                          <div className="grid grid-cols-1 sm:grid-cols-2 gap-3 mt-3 pt-3 border-t border-neutral-200">
                            <div className="space-y-1">
                              <label className="text-[10px] font-bold text-neutral-600 uppercase">
                                Reason for Return *
                              </label>
                              <select
                                value={returnReasons[item.id] || "wrong_size"}
                                onChange={(e) =>
                                  setReturnReasons({ ...returnReasons, [item.id]: e.target.value })
                                }
                                className="w-full h-9 px-3 text-xs bg-white border border-neutral-300 rounded-lg focus:outline-none"
                              >
                                {RETURN_REASONS.map((r) => (
                                  <option key={r.value} value={r.value}>
                                    {r.label}
                                  </option>
                                ))}
                              </select>
                            </div>

                            <div className="space-y-1">
                              <label className="text-[10px] font-bold text-neutral-600 uppercase">
                                Specific Notes / Fit Feedback
                              </label>
                              <input
                                type="text"
                                placeholder="e.g. Sleeves were 1 inch too long"
                                value={returnComments[item.id] || ""}
                                onChange={(e) =>
                                  setReturnComments({ ...returnComments, [item.id]: e.target.value })
                                }
                                className="w-full h-9 px-3 text-xs bg-white border border-neutral-300 rounded-lg focus:outline-none"
                              />
                            </div>
                          </div>
                        )}
                      </div>
                    </div>
                  </div>
                );
              })}
            </div>
          </div>

          {/* 3. Resolution (Refund vs Exchange) */}
          <div className="space-y-3">
            <label className="text-xs font-bold uppercase tracking-wider text-neutral-900 block">
              3. Desired Resolution
            </label>
            <div className="grid grid-cols-1 sm:grid-cols-2 gap-3">
              <label
                className={`p-4 rounded-2xl border flex items-center gap-3 cursor-pointer transition-all ${
                  resolution === "refund"
                    ? "border-neutral-900 bg-neutral-50 shadow-xs"
                    : "border-neutral-200 hover:border-neutral-300"
                }`}
              >
                <input
                  type="radio"
                  name="resolution"
                  checked={resolution === "refund"}
                  onChange={() => setResolution("refund")}
                  className="accent-neutral-900"
                />
                <div>
                  <p className="text-xs font-bold text-neutral-900">Original Payment Refund</p>
                  <p className="text-[11px] text-neutral-500">Refund credited back to your original payment method.</p>
                </div>
              </label>

              <label
                className={`p-4 rounded-2xl border flex items-center gap-3 cursor-pointer transition-all ${
                  resolution === "exchange"
                    ? "border-neutral-900 bg-neutral-50 shadow-xs"
                    : "border-neutral-200 hover:border-neutral-300"
                }`}
              >
                <input
                  type="radio"
                  name="resolution"
                  checked={resolution === "exchange"}
                  onChange={() => setResolution("exchange")}
                  className="accent-neutral-900"
                />
                <div>
                  <p className="text-xs font-bold text-neutral-900">Exchange for Different Size / Color</p>
                  <p className="text-[11px] text-neutral-500">We send your replacement piece immediately at no charge.</p>
                </div>
              </label>
            </div>

            {resolution === "exchange" && (
              <div className="pt-2">
                <Input
                  label="Replacement Variant Desired *"
                  placeholder="e.g. Size L in Oatmeal Heather instead of Size M"
                  value={exchangeDetails}
                  onChange={(e) => setExchangeDetails(e.target.value)}
                  required
                />
              </div>
            )}
          </div>

          {/* 4. Optional Photo Upload */}
          <div className="space-y-2">
            <label className="text-xs font-bold uppercase tracking-wider text-neutral-900 block">
              4. Photo Verification (Optional)
            </label>
            <div className="p-4 border-2 border-dashed border-neutral-200 rounded-2xl text-center space-y-2 hover:border-neutral-400 transition-colors">
              <Upload className="w-5 h-5 text-neutral-400 mx-auto" />
              <div className="text-xs text-neutral-600">
                <label className="font-bold text-neutral-900 underline cursor-pointer">
                  <span>Upload a photo</span>
                  <input type="file" onChange={handleFileUpload} className="hidden" accept="image/*" />
                </label>
                <span> or drag and drop</span>
              </div>
              <p className="text-[10px] text-neutral-400">PNG, JPG up to 10MB</p>
              {uploadedFileName && (
                <p className="text-xs text-emerald-700 font-semibold mt-1">
                  Attached: {uploadedFileName}
                </p>
              )}
            </div>
          </div>

          {/* Submit Action */}
          <div className="pt-4 border-t border-neutral-100 flex items-center justify-end gap-3">
            <Link
              href="/account/returns"
              className="px-5 py-2.5 border border-neutral-300 rounded-full text-xs font-medium text-neutral-700 hover:bg-neutral-50"
            >
              Cancel
            </Link>
            <Button
              type="submit"
              isLoading={isSubmitting}
              className="px-8 h-12 rounded-full font-bold text-xs"
            >
              Generate RMA & Prepaid Return Label
            </Button>
          </div>
        </form>
      </div>
    </div>
  );
}

export default function NewReturnPage() {
  return (
    <Suspense fallback={<div className="p-8 text-center text-xs text-neutral-400">Loading return form...</div>}>
      <ReturnFormContent />
    </Suspense>
  );
}

