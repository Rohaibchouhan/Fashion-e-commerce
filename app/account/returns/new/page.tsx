"use client";

import React, { useState, useEffect, Suspense } from "react";
import Image from "next/image";
import Link from "next/link";
import { useRouter, useSearchParams } from "next/navigation";
import { OrdersService } from "@/services/orders.service";
import { Order } from "@/types/order";
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
  const preselectedOrderId = searchParams.get("orderId");

  const [orders, setOrders] = useState<Order[]>([]);
  const [isLoadingOrders, setIsLoadingOrders] = useState(true);
  const [selectedOrderId, setSelectedOrderId] = useState<string>("");

  useEffect(() => {
    let isMounted = true;
    async function loadOrders() {
      try {
        const data = await OrdersService.getAll();
        if (isMounted) {
          setOrders(data);
          const initialId = preselectedOrderId || data[0]?.id || "";
          setSelectedOrderId(initialId);
        }
      } catch (err) {
        console.error("Failed to load orders for return:", err);
      } finally {
        if (isMounted) setIsLoadingOrders(false);
      }
    }
    loadOrders();
    return () => {
      isMounted = false;
    };
  }, [preselectedOrderId]);

  const currentOrder = orders.find((o) => o.id === selectedOrderId) || orders[0];

  const [selectedItems, setSelectedItems] = useState<string[]>([]);
  const [returnReasons, setReturnReasons] = useState<Record<string, string>>({});
  const [returnComments, setReturnComments] = useState<Record<string, string>>({});
  const [resolution, setResolution] = useState<"refund" | "exchange">("refund");
  const [exchangeDetails, setExchangeDetails] = useState("");
  const [uploadedFileName, setUploadedFileName] = useState<string | null>(null);
  const [isSubmitting, setIsSubmitting] = useState(false);

  useEffect(() => {
    if (currentOrder && currentOrder.items && currentOrder.items.length > 0) {
      setSelectedItems([currentOrder.items[0].id]);
    } else {
      setSelectedItems([]);
    }
  }, [currentOrder?.id]);

  const toggleItem = (itemId: string) => {
    if (selectedItems.includes(itemId)) {
      if (selectedItems.length === 1) {
        toast.info("Selection Required", "Please select at least one item to return.");
        return;
      }
      setSelectedItems(selectedItems.filter((id) => id !== itemId));
    } else {
      setSelectedItems([...selectedItems, itemId]);
    }
  };

  const handleFileUpload = (e: React.ChangeEvent<HTMLInputElement>) => {
    if (e.target.files && e.target.files[0]) {
      setUploadedFileName(e.target.files[0].name);
      toast.success("Photo Attached", `Attached ${e.target.files[0].name}`);
    }
  };

  const handleSubmit = async (e: React.FormEvent) => {
    e.preventDefault();
    if (!currentOrder) return;

    if (selectedItems.length === 0) {
      toast.error("Selection Required", "Please select at least one item to return.");
      return;
    }

    // Ensure all selected items have a reason selected
    for (const itemId of selectedItems) {
      if (!returnReasons[itemId]) {
        toast.error("Reason Required", "Please specify a return reason for each selected item.");
        return;
      }
    }

    try {
      setIsSubmitting(true);
      const itemsToReturn = selectedItems.map((id) => {
        const item = currentOrder.items?.find((i) => i.id === id);
        return {
          order_item_id: item?.id || id,
          quantity: item?.quantity || 1,
          reason: returnReasons[id] || "wrong_size",
          comment: returnComments[id] || undefined,
        };
      });

      const newReturn = await ReturnsService.createReturn({
        orderId: currentOrder.id,
        orderNumber: currentOrder.orderNumber,
        items: itemsToReturn,
        resolution,
        exchangeDetails: resolution === "exchange" ? exchangeDetails : undefined,
      });

      toast.success("RMA Generated", `Return #${newReturn.rmaNumber} created successfully!`);
      router.push(`/account/returns/${newReturn.id}`);
    } catch (err: any) {
      toast.error("Submission Failed", err?.message || "Failed to submit return request.");
    } finally {
      setIsSubmitting(false);
    }
  };

  if (isLoadingOrders) {
    return (
      <div className="py-24 text-center bg-white rounded-3xl border border-neutral-200">
        <p className="text-xs text-neutral-400">Loading order details...</p>
      </div>
    );
  }

  if (orders.length === 0) {
    return (
      <div className="py-24 text-center bg-white rounded-3xl border border-neutral-200 space-y-4">
        <RotateCcw className="w-10 h-10 text-neutral-300 mx-auto" />
        <h2 className="text-lg font-bold text-neutral-900">No Orders Available</h2>
        <p className="text-xs text-neutral-500 max-w-sm mx-auto">
          You don't have any orders eligible for returns at this time.
        </p>
        <Link
          href="/shop"
          className="inline-block px-5 py-2.5 bg-neutral-900 text-white text-xs font-bold rounded-full hover:bg-neutral-800 transition-colors"
        >
          Explore Shop
        </Link>
      </div>
    );
  }

  return (
    <div className="space-y-6">
      {/* Top Header */}
      <div className="flex items-center justify-between">
        <Link
          href="/account/returns"
          className="inline-flex items-center gap-1.5 text-xs font-semibold text-neutral-600 hover:text-neutral-950 transition-colors"
        >
          <ArrowLeft className="w-4 h-4" /> Back to Returns Portal
        </Link>
      </div>

      <div className="bg-white rounded-3xl border border-neutral-200 p-6 sm:p-8 shadow-xs space-y-8">
        <div>
          <span className="text-[10px] font-bold uppercase tracking-wider text-neutral-400">
            30-Day Hassle-Free Returns
          </span>
          <h1 className="text-xl sm:text-2xl font-black text-neutral-900 tracking-tight mt-1">
            Initiate a Return or Size Exchange
          </h1>
          <p className="text-xs text-neutral-500 mt-1">
            Generate an official prepaid RMA shipping label in seconds.
          </p>
        </div>

        <form onSubmit={handleSubmit} className="space-y-8">
          {/* 1. Order Selector */}
          <div className="space-y-3">
            <label className="text-xs font-bold uppercase tracking-wider text-neutral-900 block">
              1. Select Order
            </label>
            <select
              value={selectedOrderId}
              onChange={(e) => setSelectedOrderId(e.target.value)}
              className="w-full h-11 px-3 bg-neutral-50 border border-neutral-200 rounded-xl text-xs font-semibold text-neutral-800 focus:outline-none focus:ring-1 focus:ring-neutral-900"
            >
              {orders.map((o) => (
                <option key={o.id} value={o.id}>
                  Order #{o.orderNumber} — Total: {formatPrice(o.total)} ({o.items?.length || 0} items)
                </option>
              ))}
            </select>
          </div>

          {/* 2. Items Checklist */}
          {currentOrder && currentOrder.items && currentOrder.items.length > 0 && (
            <div className="space-y-4">
              <label className="text-xs font-bold uppercase tracking-wider text-neutral-900 block">
                2. Select Items to Return
              </label>
              <div className="divide-y divide-neutral-100 border border-neutral-200 rounded-2xl overflow-hidden">
                {currentOrder.items.map((item) => {
                  const isChecked = selectedItems.includes(item.id);
                  return (
                    <div key={item.id} className="p-4 sm:p-5 space-y-3 bg-white">
                      <div className="flex items-center justify-between gap-4">
                        <label className="flex items-center gap-3 cursor-pointer select-none">
                          <input
                            type="checkbox"
                            checked={isChecked}
                            onChange={() => toggleItem(item.id)}
                            className="w-4 h-4 rounded text-neutral-900 focus:ring-neutral-900"
                          />
                          <div className="relative w-12 h-14 rounded-lg overflow-hidden bg-neutral-100 shrink-0">
                            {item.image ? (
                              <Image src={item.image} alt={item.name} fill className="object-cover" />
                            ) : (
                              <div className="w-full h-full bg-neutral-200 flex items-center justify-center text-xs text-neutral-400">
                                WD
                              </div>
                            )}
                          </div>
                          <div className="text-xs">
                            <p className="font-bold text-neutral-900">{item.name}</p>
                            <p className="text-[11px] text-neutral-500">
                              {item.color} • {item.size} • Qty {item.quantity}
                            </p>
                          </div>
                        </label>
                        <p className="text-xs font-bold text-neutral-900">{formatPrice(item.price)}</p>
                      </div>

                      {isChecked && (
                        <div className="pt-3 border-t border-neutral-100 grid grid-cols-1 sm:grid-cols-2 gap-3">
                          <div>
                            <label className="text-[10px] font-bold uppercase tracking-wider text-neutral-500 block mb-1">
                              Reason for Return *
                            </label>
                            <select
                              value={returnReasons[item.id] || ""}
                              onChange={(e) =>
                                setReturnReasons({ ...returnReasons, [item.id]: e.target.value })
                              }
                              required
                              className="w-full h-9 px-2 bg-neutral-50 border border-neutral-200 rounded-lg text-xs"
                            >
                              <option value="">Select a reason...</option>
                              {RETURN_REASONS.map((r) => (
                                <option key={r.value} value={r.value}>
                                  {r.label}
                                </option>
                              ))}
                            </select>
                          </div>
                          <div>
                            <label className="text-[10px] font-bold uppercase tracking-wider text-neutral-500 block mb-1">
                              Specific Notes (Optional)
                            </label>
                            <input
                              type="text"
                              placeholder="e.g. Sleeves are slightly too long"
                              value={returnComments[item.id] || ""}
                              onChange={(e) =>
                                setReturnComments({ ...returnComments, [item.id]: e.target.value })
                              }
                              className="w-full h-9 px-2 bg-neutral-50 border border-neutral-200 rounded-lg text-xs"
                            />
                          </div>
                        </div>
                      )}
                    </div>
                  );
                })}
              </div>
            </div>
          )}

          {/* 3. Resolution Method */}
          <div className="space-y-3">
            <label className="text-xs font-bold uppercase tracking-wider text-neutral-900 block">
              3. Preferred Resolution
            </label>
            <div className="grid grid-cols-1 sm:grid-cols-2 gap-4">
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
