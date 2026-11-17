import React from "react";
import Image from "next/image";
import Link from "next/link";
import { BlogService } from "@/services/blog.service";
import { Breadcrumb } from "@/components/ui/breadcrumb";
import { ArrowRight, Clock } from "lucide-react";

export default async function BlogPage() {
  const blogs = await BlogService.getAll();
  const featured = blogs[0];
  const others = blogs.slice(1);

  return (
    <div className="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8 py-8 sm:py-12 space-y-12">
      <div>
        <Breadcrumb items={[{ label: "Home", href: "/" }, { label: "Styling Guides & Journal" }]} />
        <h1 className="text-2xl sm:text-4xl font-black text-neutral-900 tracking-tight mt-2">
          The WearDay Journal
        </h1>
        <p className="text-xs sm:text-sm text-neutral-500 mt-1 max-w-xl">
          Curated wardrobe formulas, fabric insights, and minimalist dressing perspectives.
        </p>
      </div>

      {/* Hero Featured Article */}
      {featured && (
        <Link
          href={`/blog/${featured.slug}`}
          className="group grid grid-cols-1 lg:grid-cols-12 gap-8 items-center rounded-3xl bg-neutral-100/70 p-6 sm:p-10 border border-neutral-200/80 transition-all hover:shadow-lg"
        >
          <div className="lg:col-span-7 relative aspect-16/10 rounded-2xl overflow-hidden bg-neutral-200">
            {featured.featuredImage ? (
              <Image
                src={featured.featuredImage}
                alt={featured.title}
                fill
                className="object-cover group-hover:scale-105 transition-transform duration-500"
              />
            ) : (
              <div className="w-full h-full bg-neutral-200 flex items-center justify-center text-neutral-400">
                WD Journal
              </div>
            )}
          </div>
          <div className="lg:col-span-5 space-y-4">
            <span className="text-[11px] font-bold uppercase tracking-wider text-neutral-500 flex items-center gap-2">
              <span>{featured.category || "Styling Guides"}</span>
              <span>•</span>
              <span className="flex items-center gap-1">
                <Clock className="w-3 h-3" /> 4 min read
              </span>
            </span>
            <h2 className="text-2xl sm:text-3xl font-black text-neutral-900 tracking-tight group-hover:text-neutral-700 transition-colors">
              {featured.title}
            </h2>
            <p className="text-xs sm:text-sm text-neutral-600 leading-relaxed line-clamp-3">
              {featured.excerpt}
            </p>
            <div className="pt-2 flex items-center gap-2 text-xs font-bold text-neutral-900">
              <span>Read Full Guide</span>
              <ArrowRight className="w-4 h-4 group-hover:translate-x-1 transition-transform" />
            </div>
          </div>
        </Link>
      )}

      {/* Grid of Other Articles */}
      <div className="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-3 gap-6">
        {others.map((post) => (
          <Link
            key={post.id}
            href={`/blog/${post.slug}`}
            className="group bg-white rounded-3xl p-5 border border-neutral-200/80 shadow-xs hover:shadow-md transition-all flex flex-col justify-between"
          >
            <div className="space-y-4">
              <div className="relative aspect-4/3 rounded-2xl overflow-hidden bg-neutral-100">
                {post.featuredImage ? (
                  <Image
                    src={post.featuredImage}
                    alt={post.title}
                    fill
                    className="object-cover group-hover:scale-105 transition-transform duration-500"
                  />
                ) : (
                  <div className="w-full h-full bg-neutral-200 flex items-center justify-center text-neutral-400">
                    WD Journal
                  </div>
                )}
              </div>
              <div>
                <span className="text-[10px] font-bold uppercase tracking-wider text-neutral-400">
                  {post.category || "Style"} • 4 min read
                </span>
                <h3 className="text-base font-bold text-neutral-900 group-hover:text-neutral-700 transition-colors mt-1">
                  {post.title}
                </h3>
                <p className="text-xs text-neutral-500 line-clamp-2 mt-1 leading-relaxed">
                  {post.excerpt}
                </p>
              </div>
            </div>

            <div className="pt-4 mt-2 flex items-center justify-between border-t border-neutral-100 text-xs font-bold text-neutral-900">
              <span>Read Guide</span>
              <ArrowRight className="w-3.5 h-3.5 text-neutral-400 group-hover:text-neutral-900 group-hover:translate-x-1 transition-all" />
            </div>
          </Link>
        ))}
      </div>
    </div>
  );
}
