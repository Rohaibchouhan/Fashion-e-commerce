"use client";

import React, { useState, useEffect } from "react";
import Link from "next/link";
import Image from "next/image";
import { ReturnsService } from "@/services/returns.service";
import { ReturnRequest } from "@/types/return";
import { RETURN_STATUS_DETAILS } from "@/constants";
import { formatPrice, formatDate } from "@/lib/utils";
import { RotateCcw, Plus, ArrowRight, ShieldCheck, FileText } from "lucide-react";

export default function AccountReturnsPage() {
  const [returns, setReturns] = useState<ReturnRequest[]>([]);
  const [isLoading, setIsLoading] = useState(true);

  useEffect(() => {
    let isMounted = true;
    async function loadReturns() {
      try {
        const data = await ReturnsService.getAll();
        if (isMounted) setReturns(data);
      } catch (err) {
        console.error("Failed to load return requests:", err);
      } finally {
        if (isMounted) setIsLoading(false);
      }
    }
    loadReturns();
    return () => {
      isMounted = false;
    };
  }, []);

  return (
    <div className="space-y-6">
      {/* Header */}
      <div className="bg-white rounded-3xl border border-neutral-200 p-6 sm:p-8 shadow-xs flex flex-col sm:flex-row sm:items-center justify-between gap-4">
        <div>
          <h1 className="text-xl sm:text-2xl font-black text-neutral-900 tracking-tight">
            Returns & Exchanges Portal
          </h1>
          <p className="text-xs text-neutral-500 mt-1">
            Generate prepaid return labels, track return packages, and request size exchanges.
          </p>
        </div>

        <Link
          href="/account/returns/new"
          className="px-5 py-2.5 bg-neutral-900 hover:bg-neutral-800 text-white rounded-full text-xs font-bold transition-colors flex items-center gap-1.5 shadow-xs self-start sm:self-auto"
        >
          <Plus className="w-3.5 h-3.5" /> Start New Return
        </Link>
      </div>

      {/* Returns List */}
      {isLoading ? (
        <div className="py-16 text-center bg-white rounded-3xl border border-neutral-200 p-8">
          <p className="text-xs text-neutral-400">Loading return requests...</p>
        </div>
      ) : returns.length === 0 ? (
        <div className="py-16 text-center bg-white rounded-3xl border border-neutral-200 p-8 space-y-3">
          <RotateCcw className="w-10 h-10 text-neutral-300 mx-auto" />
          <h3 className="text-sm font-bold text-neutral-900">No active returns</h3>
          <p className="text-xs text-neutral-500 max-w-sm mx-auto">
            You do not have any pending returns. Delivered orders are eligible for return within 30 days.
          </p>
        </div>
      ) : (
        <div className="space-y-4">
          {returns.map((ret) => (
            <div
              key={ret.id}
              className="bg-white rounded-3xl border border-neutral-200 overflow-hidden shadow-xs divide-y divide-neutral-100"
            >
              {/* Header Strip */}
              <div className="p-5 bg-neutral-50/70 flex flex-wrap items-center justify-between gap-3 text-xs">
                <div className="flex items-center gap-3">
                  <div>
                    <span className="text-[10px] uppercase font-bold text-neutral-400 tracking-wider">
                      RMA Tracking
                    </span>
                    <p className="font-bold text-neutral-900">#{ret.rmaNumber}</p>
                  </div>
                  <span className="text-neutral-300">•</span>
                  <div>
                    <span className="text-[10px] uppercase font-bold text-neutral-400 tracking-wider">
                      Order Reference
                    </span>
                    <p className="font-semibold text-neutral-700">#{ret.orderNumber}</p>
                  </div>
                  <span className="text-neutral-300">•</span>
                  <div>
                    <span className="text-[10px] uppercase font-bold text-neutral-400 tracking-wider">
                      Date Requested
                    </span>
                    <p className="font-semibold text-neutral-700">{formatDate(ret.createdAt)}</p>
                  </div>
                </div>

                <div className="flex items-center gap-2">
                  <span
                    className={`px-3 py-1 text-[11px] font-bold rounded-full ${
                      RETURN_STATUS_DETAILS[ret.status]?.color || "bg-neutral-100 text-neutral-800"
                    }`}
                  >
                    {RETURN_STATUS_DETAILS[ret.status]?.label || ret.status}
                  </span>
                </div>
              </div>

              {/* Items Preview */}
              <div className="p-5 sm:p-6 space-y-4">
                <div className="space-y-3">
                  {ret.items?.map((item) => (
                    <div key={item.orderItemId || item.productId} className="flex items-center justify-between text-xs">
                      <div className="flex items-center gap-3">
                        <div className="relative w-12 h-14 rounded-lg overflow-hidden bg-neutral-100 shrink-0">
                          {item.image ? (
                            <Image src={item.image} alt={item.name} fill className="object-cover" />
                          ) : (
                            <div className="w-full h-full bg-neutral-200 flex items-center justify-center text-xs text-neutral-400">
                              WD
                            </div>
                          )}
                        </div>
                        <div>
                          <p className="font-bold text-neutral-900">{item.name}</p>
                          <p className="text-[11px] text-neutral-500">
                            {item.color} • {item.size} • Qty {item.quantity}
                          </p>
                          <p className="text-[10px] text-neutral-400 mt-0.5">
                            Reason: {item.reason.replace(/_/g, " ")}
                          </p>
                        </div>
                      </div>
                      <p className="font-bold text-neutral-900">{formatPrice(item.price)}</p>
                    </div>
                  ))}
                </div>

                {/* Resolution & Label Actions */}
                <div className="pt-4 border-t border-neutral-100 flex flex-wrap items-center justify-between gap-3 text-xs">
                  <div className="space-y-0.5">
                    <p className="text-neutral-500">
                      Resolution:{" "}
                      <strong className="text-neutral-900 capitalize">{ret.resolution}</strong>
                    </p>
                    {ret.exchangeDetails && (
                      <p className="text-[11px] text-neutral-500">{ret.exchangeDetails}</p>
                    )}
                  </div>

                  <div className="flex items-center gap-2">
                    {ret.labelUrl && (
                      <a
                        href={ret.labelUrl}
                        target="_blank"
                        rel="noreferrer"
                        className="px-4 py-2 border border-neutral-200 hover:bg-neutral-50 text-neutral-800 text-xs font-semibold rounded-full transition-colors flex items-center gap-1.5"
                      >
                        <FileText className="w-3.5 h-3.5" /> Download Prepaid Label
                      </a>
                    )}
                    <Link
                      href={`/account/returns/${ret.id}`}
                      className="px-4 py-2 bg-neutral-900 hover:bg-neutral-800 text-white text-xs font-bold rounded-full transition-colors flex items-center gap-1"
                    >
                      <span>View RMA Status</span>
                      <ArrowRight className="w-3.5 h-3.5" />
                    </Link>
                  </div>
                </div>
              </div>
            </div>
          ))}
        </div>
      )}
    </div>
  );
}
