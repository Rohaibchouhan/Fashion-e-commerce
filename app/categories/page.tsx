import React from "react";
import Image from "next/image";
import Link from "next/link";
import { CATEGORIES } from "@/constants";
import { Breadcrumb } from "@/components/ui/breadcrumb";
import { ArrowRight } from "lucide-react";

export default function CategoriesPage() {
  return (
    <div className="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8 py-8 sm:py-12 space-y-10">
      <div>
        <Breadcrumb items={[{ label: "Home", href: "/" }, { label: "Categories" }]} />
        <h1 className="text-2xl sm:text-4xl font-black text-neutral-900 tracking-tight mt-2">
          Shop By Category
        </h1>
        <p className="text-xs sm:text-sm text-neutral-500 mt-1 max-w-lg">
          Browse our curated departments tailored for everyday comfort and modern minimalist living.
        </p>
      </div>

      <div className="grid grid-cols-2 md:grid-cols-3 lg:grid-cols-4 gap-4 sm:gap-6">
        {CATEGORIES.map((cat) => (
          <Link
            key={cat.id}
            href={`/shop?category=${cat.slug}`}
            className="group relative rounded-3xl overflow-hidden aspect-4/5 bg-neutral-100 border border-neutral-200/80 shadow-xs flex flex-col justify-end p-5 transition-all duration-300 hover:shadow-lg"
          >
            <div className="absolute inset-0">
              <Image
                src={cat.image}
                alt={cat.name}
                fill
                className="object-cover group-hover:scale-105 transition-transform duration-500"
              />
              <div className="absolute inset-0 bg-gradient-to-t from-black/80 via-black/30 to-transparent" />
            </div>

            <div className="relative z-10 text-white flex items-end justify-between">
              <div>
                <span className="text-[10px] font-bold uppercase tracking-wider text-neutral-300">
                  {cat.itemCount} Styles
                </span>
                <h3 className="text-lg font-bold tracking-tight text-white mt-0.5">
                  {cat.name}
                </h3>
              </div>

              <div className="w-8 h-8 rounded-full bg-white/20 backdrop-blur-md flex items-center justify-center text-white group-hover:bg-white group-hover:text-neutral-900 transition-colors">
                <ArrowRight className="w-4 h-4" />
              </div>
            </div>
          </Link>
        ))}
      </div>
    </div>
  );
}

