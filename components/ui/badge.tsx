import React from "react";
import { cn } from "@/lib/utils";

export interface BadgeProps extends React.HTMLAttributes<HTMLSpanElement> {
  variant?: "default" | "secondary" | "outline" | "sale" | "success" | "warning" | "danger";
  size?: "sm" | "md";
}

export const Badge: React.FC<BadgeProps> = ({
  className,
  variant = "default",
  size = "md",
  children,
  ...props
}) => {
  const variants = {
    default: "bg-neutral-900 text-white",
    secondary: "bg-neutral-100 text-neutral-800 border border-neutral-200",
    outline: "border border-neutral-300 text-neutral-800 bg-white/80",
    sale: "bg-rose-600 text-white font-semibold",
    success: "bg-emerald-100 text-emerald-800 border border-emerald-200",
    warning: "bg-amber-100 text-amber-800 border border-amber-200",
    danger: "bg-rose-100 text-rose-800 border border-rose-200",
  };

  const sizes = {
    sm: "px-2 py-0.5 text-[10px] uppercase tracking-wider font-semibold rounded-md",
    md: "px-2.5 py-1 text-xs font-medium rounded-full",
  };

  return (
    <span className={cn("inline-flex items-center gap-1", variants[variant], sizes[size], className)} {...props}>
      {children}
    </span>
  );
};

