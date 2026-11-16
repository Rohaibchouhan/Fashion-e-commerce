import React from "react";
import { cn } from "@/lib/utils";

interface TabsProps {
  tabs: { id: string; label: string; count?: number }[];
  activeTab: string;
  onChange: (id: string) => void;
  variant?: "pill" | "underline";
  className?: string;
}

export const Tabs: React.FC<TabsProps> = ({
  tabs,
  activeTab,
  onChange,
  variant = "pill",
  className,
}) => {
  if (variant === "underline") {
    return (
      <div className={cn("flex border-b border-neutral-200 gap-6 overflow-x-auto no-scrollbar", className)}>
        {tabs.map((tab) => {
          const isActive = tab.id === activeTab;
          return (
            <button
              key={tab.id}
              onClick={() => onChange(tab.id)}
              className={cn(
                "pb-3 text-sm font-medium transition-all relative whitespace-nowrap cursor-pointer",
                isActive
                  ? "text-neutral-900 font-semibold"
                  : "text-neutral-500 hover:text-neutral-800"
              )}
            >
              {tab.label}
              {tab.count !== undefined && (
                <span
                  className={cn(
                    "ml-1.5 px-2 py-0.5 text-xs rounded-full",
                    isActive ? "bg-neutral-900 text-white" : "bg-neutral-100 text-neutral-600"
                  )}
                >
                  {tab.count}
                </span>
              )}
              {isActive && (
                <span className="absolute bottom-0 left-0 right-0 h-0.5 bg-neutral-900 rounded-full" />
              )}
            </button>
          );
        })}
      </div>
    );
  }

  return (
    <div className={cn("inline-flex p-1 bg-neutral-100 rounded-full gap-1", className)}>
      {tabs.map((tab) => {
        const isActive = tab.id === activeTab;
        return (
          <button
            key={tab.id}
            onClick={() => onChange(tab.id)}
            className={cn(
              "px-4 py-1.5 text-xs font-semibold rounded-full transition-all cursor-pointer",
              isActive
                ? "bg-white text-neutral-900 shadow-xs"
                : "text-neutral-600 hover:text-neutral-900"
            )}
          >
            {tab.label}
            {tab.count !== undefined && (
              <span className="ml-1.5 opacity-75">({tab.count})</span>
            )}
          </button>
        );
      })}
    </div>
  );
};

