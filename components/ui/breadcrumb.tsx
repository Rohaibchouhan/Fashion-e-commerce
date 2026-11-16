import React from "react";
import Link from "next/link";
import { ChevronRight } from "lucide-react";
import { cn } from "@/lib/utils";

interface BreadcrumbItem {
  label: string;
  href?: string;
}

export const Breadcrumb: React.FC<{ items: BreadcrumbItem[]; className?: string }> = ({
  items,
  className,
}) => {
  return (
    <nav className={cn("flex items-center space-x-1.5 text-xs text-neutral-500", className)}>
      {items.map((item, idx) => {
        const isLast = idx === items.length - 1;
        return (
          <React.Fragment key={idx}>
            {item.href && !isLast ? (
              <Link href={item.href} className="hover:text-neutral-900 transition-colors">
                {item.label}
              </Link>
            ) : (
              <span className={isLast ? "text-neutral-900 font-semibold" : ""}>{item.label}</span>
            )}
            {!isLast && <ChevronRight className="w-3.5 h-3.5 text-neutral-400 shrink-0" />}
          </React.Fragment>
        );
      })}
    </nav>
  );
};

