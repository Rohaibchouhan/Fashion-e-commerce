"use client";

import React, { use, useEffect, useState } from "react";
import Link from "next/link";
import Image from "next/image";
import { ReturnsService } from "@/services/returns.service";
import { ReturnRequest } from "@/types/return";
import { RETURN_STATUS_DETAILS } from "@/constants";
import { formatPrice, formatDate } from "@/lib/utils";
import {
  ArrowLeft,
  RotateCcw,
  FileText,
  Truck,
  CheckCircle2,
  Clock,
  ShieldCheck,
  AlertCircle,
} from "lucide-react";

export default function ReturnDetailsPage({
  params,
}: {
  params: Promise<{ id: string }>;
}) {
  const resolvedParams = use(params);
  const returnId = resolvedParams.id;

  const [returnReq, setReturnReq] = useState<ReturnRequest | null>(null);
  const [isLoading, setIsLoading] = useState(true);

  useEffect(() => {
    let isMounted = true;
    async function loadReturn() {
      try {
        const data = await ReturnsService.getById(returnId);
        if (isMounted) setReturnReq(data);
      } catch (err) {
        console.error("Failed to load return details:", err);
      } finally {
        if (isMounted) setIsLoading(false);
      }
    }
    loadReturn();
    return () => {
      isMounted = false;
    };
  }, [returnId]);

  if (isLoading) {
    return (
      <div className="py-24 text-center bg-white rounded-3xl border border-neutral-200">
        <p className="text-xs text-neutral-400">Loading RMA details...</p>
      </div>
    );
  }

  if (!returnReq) {
    return (
      <div className="py-24 text-center bg-white rounded-3xl border border-neutral-200 space-y-4">
        <h2 className="text-lg font-bold text-neutral-900">Return Request Not Found</h2>
        <p className="text-xs text-neutral-500">Could not locate RMA #{returnId}.</p>
        <Link
          href="/account/returns"
          className="inline-block px-5 py-2.5 bg-neutral-900 text-white text-xs font-bold rounded-full hover:bg-neutral-800 transition-colors"
        >
          Back to Returns
        </Link>
      </div>
    );
  }

  return (
    <div className="space-y-6">
      {/* Top Navigation */}
      <div className="flex items-center justify-between">
        <Link
          href="/account/returns"
          className="inline-flex items-center gap-1.5 text-xs font-semibold text-neutral-600 hover:text-neutral-950 transition-colors"
        >
          <ArrowLeft className="w-4 h-4" /> Back to All Returns
        </Link>

        {returnReq.labelUrl && (
          <a
            href={returnReq.labelUrl}
            target="_blank"
            rel="noreferrer"
            className="px-4 py-2 bg-neutral-900 hover:bg-neutral-800 text-white text-xs font-bold rounded-full transition-colors flex items-center gap-1.5 shadow-xs"
          >
            <FileText className="w-3.5 h-3.5" /> Download Prepaid Return Label
          </a>
        )}
      </div>

      {/* Main RMA Summary Card */}
      <div className="bg-white rounded-3xl border border-neutral-200 overflow-hidden shadow-xs divide-y divide-neutral-100">
        <div className="p-6 sm:p-8 bg-neutral-50/70 flex flex-wrap items-center justify-between gap-4">
          <div>
            <div className="flex items-center gap-3">
              <h1 className="text-xl sm:text-2xl font-black text-neutral-900 tracking-tight">
                RMA #{returnReq.rmaNumber}
              </h1>
              <span
                className={`px-3 py-1 text-xs font-bold rounded-full border ${
                  RETURN_STATUS_DETAILS[returnReq.status]?.color || "bg-neutral-100 text-neutral-800"
                }`}
              >
                {RETURN_STATUS_DETAILS[returnReq.status]?.label || returnReq.status}
              </span>
            </div>
            <p className="text-xs text-neutral-500 mt-1">
              Associated Order: <strong>#{returnReq.orderNumber}</strong> • Requested on {formatDate(returnReq.createdAt)}
            </p>
          </div>

          <div className="text-right">
            <span className="text-[10px] uppercase font-bold text-neutral-400 tracking-wider">
              {returnReq.resolution === "refund" ? "Estimated Refund" : "Resolution"}
            </span>
            <p className="text-2xl font-black text-neutral-900">
              {returnReq.resolution === "refund" ? formatPrice(returnReq.refundAmount) : "Product Exchange"}
            </p>
          </div>
        </div>

        {/* Returned Items */}
        <div className="p-6 sm:p-8 space-y-4">
          <h3 className="text-xs font-bold uppercase tracking-wider text-neutral-400">
            Items Included in RMA ({returnReq.items?.length || 0})
          </h3>
          <div className="divide-y divide-neutral-100">
            {returnReq.items?.map((item) => (
              <div key={item.orderItemId || item.productId} className="py-4 flex items-center justify-between gap-4">
                <div className="flex items-center gap-4">
                  <div className="relative w-16 h-20 rounded-xl overflow-hidden bg-neutral-100 shrink-0">
                    {item.image ? (
                      <Image src={item.image} alt={item.name} fill className="object-cover" />
                    ) : (
                      <div className="w-full h-full bg-neutral-200 flex items-center justify-center text-xs text-neutral-400">
                        WD
                      </div>
                    )}
                  </div>
                  <div>
                    <h4 className="text-sm font-bold text-neutral-900">{item.name}</h4>
                    <p className="text-xs text-neutral-500 mt-0.5">
                      {item.color} • {item.size} • Qty {item.quantity}
                    </p>
                    <p className="text-xs text-neutral-400 mt-1">
                      Reason: <strong>{item.reason.replace(/_/g, " ")}</strong>
                    </p>
                    {item.comment && (
                      <p className="text-xs text-neutral-600 italic mt-0.5">"{item.comment}"</p>
                    )}
                  </div>
                </div>

                <div className="text-right">
                  <p className="text-sm font-bold text-neutral-900">{formatPrice(item.price)}</p>
                </div>
              </div>
            ))}
          </div>
        </div>

        {/* Step Timeline */}
        {returnReq.timeline && returnReq.timeline.length > 0 && (
          <div className="p-6 sm:p-8 space-y-4">
            <h3 className="text-xs font-bold uppercase tracking-wider text-neutral-400">
              Return Progress Timeline
            </h3>
            <div className="space-y-4">
              {returnReq.timeline.map((step, idx) => (
                <div key={idx} className="flex items-start gap-4">
                  <div
                    className={`w-6 h-6 rounded-full flex items-center justify-center shrink-0 mt-0.5 ${
                      step.completed
                        ? "bg-emerald-600 text-white"
                        : "bg-neutral-200 text-neutral-500"
                    }`}
                  >
                    {step.completed ? (
                      <CheckCircle2 className="w-4 h-4" />
                    ) : (
                      <span className="text-[10px] font-bold">{idx + 1}</span>
                    )}
                  </div>
                  <div>
                    <p className={`text-xs font-bold ${step.completed ? "text-neutral-900" : "text-neutral-500"}`}>
                      {step.label}
                    </p>
                    {step.date && <p className="text-[10px] text-neutral-400">{formatDate(step.date)}</p>}
                  </div>
                </div>
              ))}
            </div>
          </div>
        )}
      </div>
    </div>
  );
}
