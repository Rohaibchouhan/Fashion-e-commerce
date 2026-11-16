"use client";

import React, { useState } from "react";
import { Zap, Radio, Bell, Truck, X, Sparkles, CheckCircle2 } from "lucide-react";
import { realtime } from "@/services/realtime.service";
import { toast } from "./toast";
import { cn } from "@/lib/utils";

export const RealtimeSimulator: React.FC = () => {
  const [isOpen, setIsOpen] = useState(false);
  const [lastAction, setLastAction] = useState<string | null>(null);

  const simulateDispatch = () => {
    realtime.simulateOrderStatusChange(
      "ord-66120",
      "WD-66120",
      "processing",
      "dispatched",
      "NJ Distribution Center"
    );
    toast.success("Realtime Event Emitted: Order WD-66120 Dispatched!", "Timeline and badges updated via event bus.");
    setLastAction("Dispatched WD-66120");
  };

  const simulateTransit = () => {
    realtime.simulateOrderStatusChange(
      "ord-88219",
      "WD-88219",
      "dispatched",
      "in_transit",
      "FedEx Regional Linehaul Hub - Secaucus, NJ"
    );
    toast.success("Realtime Event Emitted: Order WD-88219 In Transit!", "FedEx tracking stage advanced in real-time.");
    setLastAction("In Transit WD-88219");
  };

  const simulateDelivered = () => {
    realtime.simulateOrderStatusChange(
      "ord-88219",
      "WD-88219",
      "in_transit",
      "delivered",
      "Delivered - Left at front door"
    );
    toast.success("Realtime Event Emitted: Order WD-88219 Delivered!", "Order marked as delivered with final timestamp.");
    setLastAction("Delivered WD-88219");
  };

  const simulatePromoNotification = () => {
    realtime.emit("user", "notification:new", {
      id: `notif-${Date.now()}`,
      type: "price_drop",
      title: "Price Drop Alert: Everyday Hoodie",
      message: "An item on your wishlist is now 20% off for the next 24 hours.",
      timestamp: new Date().toISOString(),
      read: false,
      link: "/products/everyday-hoodie",
    });
    toast.info("Realtime Notification Pushed", "Price drop alert delivered to notification bell.");
    setLastAction("Pushed Price Drop Notification");
  };

  return (
    <>
      {/* Floating Trigger Button in bottom corner */}
      <div className="fixed bottom-20 left-4 sm:bottom-6 sm:left-6 z-40">
        <button
          onClick={() => setIsOpen(!isOpen)}
          className="flex items-center gap-2 px-3.5 py-2.5 rounded-full bg-neutral-900 text-white text-xs font-semibold shadow-xl hover:bg-neutral-800 transition-all border border-neutral-700/50 hover:scale-105 active:scale-95"
          title="Simulate Laravel WebSockets / SSE events"
        >
          <span className="relative flex h-2 w-2">
            <span className="animate-ping absolute inline-flex h-full w-full rounded-full bg-emerald-400 opacity-75"></span>
            <span className="relative inline-flex rounded-full h-2 w-2 bg-emerald-500"></span>
          </span>
          <Zap className="w-3.5 h-3.5 text-amber-400 fill-amber-400" />
          <span className="hidden sm:inline">Realtime Sim</span>
        </button>
      </div>

      {/* Simulator Control Drawer */}
      {isOpen && (
        <div className="fixed bottom-24 left-4 sm:bottom-18 sm:left-6 z-40 w-80 sm:w-96 bg-white rounded-3xl shadow-2xl border border-neutral-200 p-5 animate-in slide-in-from-bottom-3 duration-200">
          <div className="flex items-center justify-between pb-3 border-b border-neutral-100">
            <div className="flex items-center gap-2">
              <Radio className="w-4 h-4 text-emerald-600 animate-pulse" />
              <div>
                <h4 className="text-sm font-bold text-neutral-900">Backend Event Simulator</h4>
                <p className="text-[11px] text-neutral-500">WebSocket / Reverb mock bus</p>
              </div>
            </div>
            <button
              onClick={() => setIsOpen(false)}
              className="p-1 rounded-full text-neutral-400 hover:text-neutral-700 hover:bg-neutral-100"
            >
              <X className="w-4 h-4" />
            </button>
          </div>

          <div className="mt-4 space-y-2.5">
            <p className="text-xs text-neutral-600 leading-relaxed">
              Test Requirement #11 without full-page reloads. Clicking below triggers instant updates across badges, visual timelines, and counters:
            </p>

            <div className="grid grid-cols-1 gap-2 pt-1">
              <button
                onClick={simulateDispatch}
                className="flex items-center justify-between w-full px-3 py-2 text-xs font-medium text-left bg-neutral-50 hover:bg-neutral-100 rounded-xl border border-neutral-200 text-neutral-800 transition-colors"
              >
                <span className="flex items-center gap-2">
                  <Truck className="w-3.5 h-3.5 text-blue-600" />
                  Order WD-66120: Mark Dispatched
                </span>
                <span className="text-[10px] bg-blue-100 text-blue-700 px-1.5 py-0.5 rounded font-mono">
                  processing → dispatched
                </span>
              </button>

              <button
                onClick={simulateTransit}
                className="flex items-center justify-between w-full px-3 py-2 text-xs font-medium text-left bg-neutral-50 hover:bg-neutral-100 rounded-xl border border-neutral-200 text-neutral-800 transition-colors"
              >
                <span className="flex items-center gap-2">
                  <Truck className="w-3.5 h-3.5 text-cyan-600" />
                  Order WD-88219: Advance to In Transit
                </span>
                <span className="text-[10px] bg-cyan-100 text-cyan-700 px-1.5 py-0.5 rounded font-mono">
                  in_transit
                </span>
              </button>

              <button
                onClick={simulateDelivered}
                className="flex items-center justify-between w-full px-3 py-2 text-xs font-medium text-left bg-neutral-50 hover:bg-neutral-100 rounded-xl border border-neutral-200 text-neutral-800 transition-colors"
              >
                <span className="flex items-center gap-2">
                  <CheckCircle2 className="w-3.5 h-3.5 text-emerald-600" />
                  Order WD-88219: Mark Delivered
                </span>
                <span className="text-[10px] bg-emerald-100 text-emerald-700 px-1.5 py-0.5 rounded font-mono">
                  delivered
                </span>
              </button>

              <button
                onClick={simulatePromoNotification}
                className="flex items-center justify-between w-full px-3 py-2 text-xs font-medium text-left bg-neutral-50 hover:bg-neutral-100 rounded-xl border border-neutral-200 text-neutral-800 transition-colors"
              >
                <span className="flex items-center gap-2">
                  <Bell className="w-3.5 h-3.5 text-amber-600" />
                  Push Live Notification
                </span>
                <span className="text-[10px] bg-amber-100 text-amber-800 px-1.5 py-0.5 rounded font-mono">
                  +1 unread
                </span>
              </button>
            </div>

            {lastAction && (
              <div className="pt-2 text-[11px] text-emerald-700 flex items-center gap-1.5">
                <Sparkles className="w-3 h-3" /> Last event: <strong>{lastAction}</strong>
              </div>
            )}
          </div>
        </div>
      )}
    </>
  );
};

