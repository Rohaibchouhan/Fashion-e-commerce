"use client";

import React, { useState, useEffect } from "react";
import Image from "next/image";
import Link from "next/link";
import { ArrowRight } from "lucide-react";
import { BlogService, BlogPostData } from "@/services/blog.service";

export const StylingGuidesSection: React.FC = () => {
  const [guides, setGuides] = useState<BlogPostData[]>([]);

  useEffect(() => {
    let isMounted = true;
    async function loadGuides() {
      try {
        const data = await BlogService.getAll();
        if (isMounted) setGuides(data.slice(0, 3));
      } catch (err) {
        console.error("Failed to load styling guides:", err);
      }
    }
    loadGuides();
    return () => {
      isMounted = false;
    };
  }, []);

  if (guides.length === 0) {
    return null;
  }

  return (
    <section className="py-10 sm:py-14 bg-neutral-50/60 border-y border-neutral-200/60">
      <div className="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8">
        <div className="flex items-center justify-between mb-8">
          <div>
            <h2 className="text-xl sm:text-2xl font-black text-neutral-900 tracking-tight">
              Styling Guides
            </h2>
            <p className="text-xs text-neutral-500 mt-0.5">Editorial tips from our creative studio</p>
          </div>
          <Link
            href="/blog"
            className="inline-flex items-center gap-1 text-xs font-bold text-neutral-700 hover:text-neutral-950 transition-colors"
          >
            <span>Explore all</span>
            <ArrowRight className="w-3.5 h-3.5" />
          </Link>
        </div>

        <div className="grid grid-cols-1 md:grid-cols-3 gap-6">
          {guides.map((guide) => (
            <Link
              key={guide.id}
              href={`/blog/${guide.slug}`}
              className="group bg-white rounded-3xl p-4 sm:p-5 border border-neutral-200/80 shadow-xs hover:shadow-md transition-all flex flex-col justify-between"
            >
              <div className="space-y-4">
                <div className="relative aspect-4/3 w-full rounded-2xl overflow-hidden bg-neutral-100">
                  {guide.featuredImage ? (
                    <Image
                      src={guide.featuredImage}
                      alt={guide.title}
                      fill
                      className="object-cover group-hover:scale-105 transition-transform duration-500"
                    />
                  ) : (
                    <div className="w-full h-full bg-neutral-200 flex items-center justify-center text-xs text-neutral-400">
                      WD Style
                    </div>
                  )}
                </div>
                <div>
                  <span className="text-[10px] font-bold uppercase tracking-wider text-neutral-400">
                    {guide.category || "Style"} • 4 min read
                  </span>
                  <h3 className="text-base font-bold text-neutral-900 group-hover:text-neutral-700 transition-colors mt-1">
                    {guide.title}
                  </h3>
                  <p className="text-xs text-neutral-500 line-clamp-2 mt-1 leading-relaxed">
                    {guide.excerpt}
                  </p>
                </div>
              </div>

              <div className="pt-4 mt-2 flex items-center justify-between border-t border-neutral-100 text-xs font-bold text-neutral-900">
                <span>Read Guide</span>
                <div className="w-7 h-7 rounded-full bg-neutral-100 group-hover:bg-neutral-900 group-hover:text-white transition-colors flex items-center justify-center">
                  <ArrowRight className="w-3.5 h-3.5" />
                </div>
              </div>
            </Link>
          ))}
        </div>
      </div>
    </section>
  );
};
