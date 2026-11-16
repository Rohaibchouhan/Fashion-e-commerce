"use client";

import React, { use } from "react";
import Link from "next/link";
import Image from "next/image";
import { notFound } from "next/navigation";
import { MOCK_RETURNS } from "@/lib/mock-data/returns";
import { RETURN_STATUS_DETAILS } from "@/constants";
import { formatPrice, formatDate } from "@/lib/utils";
import {
  ArrowLeft,
  RotateCcw,
  CheckCircle2,
  Printer,
  Download,
  MapPin,
  Clock,
  ShieldCheck,
} from "lucide-react";

export default function ReturnDetailsPage({
  params,
}: {
  params: Promise<{ id: string }>;
}) {
  const resolvedParams = use(params);
  const returnId = resolvedParams.id;

  const returnReq = MOCK_RETURNS.find(
    (r) => r.id.toLowerCase() === returnId.toLowerCase() || r.rmaNumber.toLowerCase() === returnId.toLowerCase()
  );

  if (!returnReq) {
    notFound();
  }

  return (
    <div className="space-y-6">
      <div className="flex items-center justify-between">
        <Link
          href="/account/returns"
          className="inline-flex items-center gap-1.5 text-xs font-semibold text-neutral-600 hover:text-neutral-950 transition-colors"
        >
          <ArrowLeft className="w-4 h-4" /> Back to Returns Portal
        </Link>

        <button
          onClick={() => window.print()}
          className="px-4 py-2 bg-neutral-900 text-white rounded-full text-xs font-bold hover:bg-neutral-800 transition-colors flex items-center gap-1.5 shadow-xs"
        >
          <Printer className="w-3.5 h-3.5" /> Print Prepaid Shipping Label
        </button>
      </div>

      <div className="bg-white rounded-3xl border border-neutral-200 overflow-hidden shadow-xs divide-y divide-neutral-100">
        {/* Banner with RMA & Status */}
        <div className="p-6 bg-neutral-50/80 flex flex-wrap items-center justify-between gap-4">
          <div>
            <div className="flex items-center gap-2.5">
              <h1 className="text-lg font-black text-neutral-900">Return #{returnReq.rmaNumber}</h1>
              <span
                className={`px-3 py-1 text-xs font-bold rounded-full ${
                  RETURN_STATUS_DETAILS[returnReq.status]?.color
                }`}
              >
                {RETURN_STATUS_DETAILS[returnReq.status]?.label}
              </span>
            </div>
            <p className="text-xs text-neutral-500 mt-1">
              Order Reference #{returnReq.orderNumber} • Submitted on {formatDate(returnReq.createdAt)}
            </p>
          </div>

          <div className="text-right">
            <span className="text-[10px] uppercase font-bold text-neutral-400 tracking-wider">
              Refund Destination
            </span>
            <p className="text-xs font-bold text-neutral-900">
              {formatPrice(returnReq.refundAmount)} ({returnReq.resolution === "refund" ? "Original Payment" : "Store Exchange"})
            </p>
            <p className="text-[11px] text-neutral-500 font-mono">
              Label: {returnReq.trackingNumber}
            </p>
          </div>
        </div>

        {/* 5-Stage Return Timeline */}
        <div className="p-6 sm:p-8 space-y-6">
          <h3 className="text-xs font-bold uppercase tracking-wider text-neutral-400">
            Return Progression Timeline
          </h3>

          <div className="relative pl-6 sm:pl-8 border-l-2 border-neutral-200 space-y-6">
            {returnReq.timeline.map((step, idx) => (
              <div key={idx} className="relative group">
                <div
                  className={`absolute -left-[31px] sm:-left-[39px] top-0 w-6 h-6 rounded-full flex items-center justify-center text-[10px] font-bold ${
                    step.completed
                      ? "bg-neutral-900 text-white"
                      : "bg-white border-2 border-neutral-300 text-neutral-400"
                  }`}
                >
                  {step.completed ? <CheckCircle2 className="w-3.5 h-3.5" /> : idx + 1}
                </div>

                <div>
                  <div className="flex items-center gap-2">
                    <h4
                      className={`text-xs font-bold ${
                        step.completed ? "text-neutral-900" : "text-neutral-400"
                      }`}
                    >
                      {step.label}
                    </h4>
                    {step.date && (
                      <span className="text-[10px] text-neutral-400">
                        {formatDate(step.date)}
                      </span>
                    )}
                  </div>
                </div>
              </div>
            ))}
          </div>
        </div>

        {/* Garments in this Return */}
        <div className="p-6 space-y-4">
          <h3 className="text-xs font-bold uppercase tracking-wider text-neutral-400">
            Garments Included in Return
          </h3>
          <div className="divide-y divide-neutral-100">
            {returnReq.items.map((item) => (
              <div key={item.orderItemId} className="py-3 flex items-center justify-between gap-4">
                <div className="flex items-center gap-4">
                  <div className="relative w-14 h-18 rounded-xl overflow-hidden bg-neutral-100 shrink-0">
                    <Image src={item.image} alt={item.name} fill className="object-cover" />
                  </div>
                  <div>
                    <h4 className="text-xs font-bold text-neutral-900">{item.name}</h4>
                    <p className="text-[11px] text-neutral-500">
                      {item.color} • Size {item.size} • Qty {item.quantity}
                    </p>
                    <p className="text-[11px] text-neutral-600 mt-0.5">
                      Customer Feedback: <em>"{item.comment}"</em>
                    </p>
                  </div>
                </div>

                <span className="text-xs font-bold text-neutral-900">
                  {formatPrice(item.price * item.quantity)}
                </span>
              </div>
            ))}
          </div>
        </div>

        {/* Dropoff Instructions Box */}
        <div className="p-6 bg-neutral-50/70 space-y-3 text-xs text-neutral-600">
          <h4 className="font-bold text-neutral-900 uppercase text-[10px] tracking-wider">
            Next Steps for Drop Off
          </h4>
          <ol className="list-decimal pl-4 space-y-1.5 leading-relaxed">
            <li>Place unworn items in original packaging or any secure mailer envelope.</li>
            <li>Affix the printed prepaid label over any previous barcode stickers.</li>
            <li>Drop off the package at any authorized UPS Access Point, drop box, or store.</li>
            <li>Keep your dropoff receipt until tracking shows delivery at our facility.</li>
          </ol>
        </div>
      </div>
    </div>
  );
}

