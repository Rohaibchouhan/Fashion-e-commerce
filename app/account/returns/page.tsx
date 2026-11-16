"use client";

import React from "react";
import Link from "next/link";
import Image from "next/image";
import { MOCK_RETURNS } from "@/lib/mock-data/returns";
import { RETURN_STATUS_DETAILS } from "@/constants";
import { formatPrice, formatDate } from "@/lib/utils";
import { RotateCcw, Plus, ArrowRight, ShieldCheck, FileText } from "lucide-react";

export default function AccountReturnsPage() {
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
      {MOCK_RETURNS.length === 0 ? (
        <div className="py-16 text-center bg-white rounded-3xl border border-neutral-200 p-8 space-y-3">
          <RotateCcw className="w-10 h-10 text-neutral-300 mx-auto" />
          <h3 className="text-sm font-bold text-neutral-900">No active returns</h3>
          <p className="text-xs text-neutral-500 max-w-sm mx-auto">
            You do not have any pending returns. Delivered orders are eligible for return within 30 days.
          </p>
        </div>
      ) : (
        <div className="space-y-4">
          {MOCK_RETURNS.map((ret) => (
            <div
              key={ret.id}
              className="bg-white rounded-3xl border border-neutral-200 overflow-hidden shadow-xs divide-y divide-neutral-100"
            >
              {/* Header Strip */}
              <div className="p-5 bg-neutral-50/70 flex flex-wrap items-center justify-between gap-3 text-xs">
                <div className="flex items-center gap-3">
                  <div>
                    <span className="text-[10px] uppercase font-bold text-neutral-400 tracking-wider">
                      RMA Authorization
                    </span>
                    <p className="font-bold text-neutral-900">{ret.rmaNumber}</p>
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

                <span
                  className={`px-3 py-1 text-[11px] font-bold rounded-full ${
                    RETURN_STATUS_DETAILS[ret.status]?.color
                  }`}
                >
                  {RETURN_STATUS_DETAILS[ret.status]?.label}
                </span>
              </div>

              {/* Items Details */}
              <div className="p-5 sm:p-6 space-y-4">
                <div className="divide-y divide-neutral-100">
                  {ret.items.map((item) => (
                    <div key={item.orderItemId} className="py-3 flex items-center justify-between gap-4">
                      <div className="flex items-center gap-3">
                        <div className="relative w-14 h-18 rounded-xl overflow-hidden bg-neutral-100 shrink-0">
                          <Image src={item.image} alt={item.name} fill className="object-cover" />
                        </div>
                        <div>
                          <h4 className="text-xs font-bold text-neutral-900">{item.name}</h4>
                          <p className="text-[11px] text-neutral-500">
                            Color: {item.color} • Size: {item.size} • Qty {item.quantity}
                          </p>
                          <p className="text-[11px] text-neutral-600 font-medium mt-1">
                            Reason: <em>{item.comment || "Fit / size mismatch"}</em>
                          </p>
                        </div>
                      </div>

                      <div className="text-right">
                        <span className="text-xs font-bold text-neutral-900">
                          Refund: {formatPrice(item.price * item.quantity)}
                        </span>
                      </div>
                    </div>
                  ))}
                </div>

                {/* Card Footer */}
                <div className="pt-4 border-t border-neutral-100 flex flex-wrap items-center justify-between gap-3 text-xs">
                  <div className="text-neutral-500">
                    Prepaid Tracking: <strong className="text-neutral-900">{ret.trackingNumber}</strong>
                  </div>

                  <Link
                    href={`/account/returns/${ret.id}`}
                    className="px-4 py-2 bg-neutral-900 text-white rounded-full text-xs font-bold hover:bg-neutral-800 transition-colors flex items-center gap-1.5 shadow-xs"
                  >
                    <span>View RMA Details & Label</span>
                    <ArrowRight className="w-3.5 h-3.5" />
                  </Link>
                </div>
              </div>
            </div>
          ))}
        </div>
      )}
    </div>
  );
}

