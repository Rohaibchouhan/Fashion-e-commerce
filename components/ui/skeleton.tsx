import React from "react";
import { cn } from "@/lib/utils";

export const Skeleton: React.FC<React.HTMLAttributes<HTMLDivElement>> = ({ className, ...props }) => {
  return <div className={cn("animate-pulse rounded-xl bg-neutral-200/70", className)} {...props} />;
};

export const ProductCardSkeleton: React.FC = () => {
  return (
    <div className="space-y-3">
      <Skeleton className="aspect-3/4 w-full rounded-2xl" />
      <Skeleton className="h-4 w-3/4" />
      <Skeleton className="h-3 w-1/2" />
      <div className="flex gap-1.5 pt-1">
        <Skeleton className="h-3.5 w-3.5 rounded-full" />
        <Skeleton className="h-3.5 w-3.5 rounded-full" />
        <Skeleton className="h-3.5 w-3.5 rounded-full" />
      </div>
    </div>
  );
};

