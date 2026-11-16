import React from "react";
import { cn } from "@/lib/utils";
import { Loader2 } from "lucide-react";

export interface ButtonProps extends React.ButtonHTMLAttributes<HTMLButtonElement> {
  variant?: "primary" | "secondary" | "outline" | "ghost" | "danger" | "dark";
  size?: "sm" | "md" | "lg" | "icon";
  isLoading?: boolean;
}

export const Button = React.forwardRef<HTMLButtonElement, ButtonProps>(
  ({ className, variant = "primary", size = "md", isLoading = false, children, disabled, ...props }, ref) => {
    const baseStyles =
      "inline-flex items-center justify-center font-medium transition-all duration-200 focus-visible:outline-none focus-visible:ring-2 focus-visible:ring-neutral-900 focus-visible:ring-offset-2 disabled:pointer-events-none disabled:opacity-50 select-none cursor-pointer active:scale-[0.98]";

    const variants = {
      primary: "bg-neutral-900 text-white hover:bg-neutral-800 shadow-sm",
      dark: "bg-neutral-950 text-white hover:bg-neutral-900",
      secondary: "bg-neutral-100 text-neutral-900 hover:bg-neutral-200 border border-neutral-200/80",
      outline: "border border-neutral-300 bg-transparent text-neutral-900 hover:bg-neutral-50",
      ghost: "text-neutral-700 hover:bg-neutral-100 hover:text-neutral-900",
      danger: "bg-rose-600 text-white hover:bg-rose-700",
    };

    const sizes = {
      sm: "h-9 px-3.5 text-xs rounded-full gap-1.5",
      md: "h-11 px-5 text-sm rounded-full gap-2",
      lg: "h-13 px-7 text-base rounded-full gap-2.5",
      icon: "h-10 w-10 rounded-full p-0 flex items-center justify-center",
    };

    return (
      <button
        ref={ref}
        disabled={disabled || isLoading}
        className={cn(baseStyles, variants[variant], sizes[size], className)}
        {...props}
      >
        {isLoading && <Loader2 className="w-4 h-4 animate-spin shrink-0" />}
        {children}
      </button>
    );
  }
);
Button.displayName = "Button";

