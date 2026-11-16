"use client";

import React, { useEffect, useState } from "react";
import { CheckCircle2, AlertCircle, Info, X } from "lucide-react";
import { cn } from "@/lib/utils";

export type ToastType = "success" | "error" | "info";

export interface ToastMessage {
  id: string;
  type: ToastType;
  title: string;
  message?: string;
  duration?: number;
}

// Global emitter for toasts
type ToastListener = (toasts: ToastMessage[]) => void;
let toasts: ToastMessage[] = [];
let listeners: ToastListener[] = [];

function notify() {
  listeners.forEach((l) => l([...toasts]));
}

export const toast = {
  show: (type: ToastType, title: string, message?: string, duration = 4000) => {
    const id = `toast-${Date.now()}-${Math.random()}`;
    toasts = [...toasts, { id, type, title, message, duration }];
    notify();

    setTimeout(() => {
      toast.dismiss(id);
    }, duration);
  },
  success: (title: string, message?: string) => toast.show("success", title, message),
  error: (title: string, message?: string) => toast.show("error", title, message),
  info: (title: string, message?: string) => toast.show("info", title, message),
  dismiss: (id: string) => {
    toasts = toasts.filter((t) => t.id !== id);
    notify();
  },
};

export const ToastContainer: React.FC = () => {
  const [activeToasts, setActiveToasts] = useState<ToastMessage[]>([]);

  useEffect(() => {
    listeners.push(setActiveToasts);
    return () => {
      listeners = listeners.filter((l) => l !== setActiveToasts);
    };
  }, []);

  if (activeToasts.length === 0) return null;

  return (
    <div className="fixed bottom-5 right-5 z-50 flex flex-col gap-2.5 max-w-sm w-full pointer-events-none">
      {activeToasts.map((t) => (
        <div
          key={t.id}
          className={cn(
            "pointer-events-auto flex items-start gap-3 p-4 rounded-2xl bg-white shadow-xl border border-neutral-100 animate-in slide-in-from-bottom-5 duration-200 transition-all",
            t.type === "success" && "border-l-4 border-l-emerald-600",
            t.type === "error" && "border-l-4 border-l-rose-600",
            t.type === "info" && "border-l-4 border-l-blue-600"
          )}
        >
          <div className="shrink-0 mt-0.5">
            {t.type === "success" && <CheckCircle2 className="w-5 h-5 text-emerald-600" />}
            {t.type === "error" && <AlertCircle className="w-5 h-5 text-rose-600" />}
            {t.type === "info" && <Info className="w-5 h-5 text-blue-600" />}
          </div>
          <div className="flex-1 min-w-0">
            <h4 className="text-sm font-semibold text-neutral-900">{t.title}</h4>
            {t.message && <p className="text-xs text-neutral-600 mt-0.5 leading-relaxed">{t.message}</p>}
          </div>
          <button
            onClick={() => toast.dismiss(t.id)}
            className="text-neutral-400 hover:text-neutral-700 p-1"
          >
            <X className="w-4 h-4" />
          </button>
        </div>
      ))}
    </div>
  );
};

