import React from "react";
import Image from "next/image";
import Link from "next/link";
import { notFound } from "next/navigation";
import { MOCK_BLOGS } from "@/lib/mock-data/blogs";
import { MOCK_PRODUCTS } from "@/lib/mock-data/products";
import { Breadcrumb } from "@/components/ui/breadcrumb";
import { ProductCard } from "@/components/product/product-card";
import { Clock, Calendar, ArrowLeft } from "lucide-react";

export default async function BlogPostPage({
  params,
}: {
  params: Promise<{ slug: string }>;
}) {
  const { slug } = await params;
  const post = MOCK_BLOGS.find((b) => b.slug === slug);
  if (!post) {
    notFound();
  }

  const featuredProducts = MOCK_PRODUCTS.filter((p) =>
    post.featuredProductSlugs?.includes(p.slug)
  );

  return (
    <article className="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8 py-8 sm:py-14 space-y-10">
      <Breadcrumb
        items={[
          { label: "Home", href: "/" },
          { label: "Journal", href: "/blog" },
          { label: post.title },
        ]}
      />

      {/* Header */}
      <div className="space-y-4">
        <div className="flex items-center gap-3 text-xs text-neutral-500 font-semibold uppercase tracking-wider">
          <span>{post.category}</span>
          <span>•</span>
          <span className="flex items-center gap-1">
            <Clock className="w-3.5 h-3.5" /> {post.readTime}
          </span>
          <span>•</span>
          <span>{post.date}</span>
        </div>

        <h1 className="text-3xl sm:text-5xl font-black text-neutral-900 tracking-tight leading-tight">
          {post.title}
        </h1>
        <p className="text-base sm:text-lg text-neutral-600 leading-relaxed font-normal">
          {post.subtitle}
        </p>

        {/* Author Bio */}
        <div className="flex items-center gap-3 pt-3 border-t border-neutral-100">
          <div className="relative w-11 h-11 rounded-full overflow-hidden bg-neutral-100 shrink-0">
            <Image src={post.author.avatar} alt={post.author.name} fill className="object-cover" />
          </div>
          <div>
            <h4 className="text-xs font-bold text-neutral-900">{post.author.name}</h4>
            <p className="text-[11px] text-neutral-400">{post.author.role}</p>
          </div>
        </div>
      </div>

      {/* Featured Cover Photo */}
      <div className="relative aspect-16/9 rounded-3xl overflow-hidden bg-neutral-100 shadow-md">
        <Image src={post.coverImage} alt={post.title} fill className="object-cover" priority />
      </div>

      {/* Article Content Prose */}
      <div className="space-y-6 text-sm sm:text-base text-neutral-800 leading-relaxed max-w-none">
        {post.content.map((paragraph, idx) => (
          <p key={idx} className="leading-relaxed">
            {paragraph}
          </p>
        ))}
      </div>

      {/* Tags */}
      <div className="flex flex-wrap gap-2 pt-6 border-t border-neutral-100">
        {post.tags.map((tag) => (
          <span
            key={tag}
            className="px-3 py-1 bg-neutral-100 text-neutral-700 text-xs font-medium rounded-full"
          >
            #{tag}
          </span>
        ))}
      </div>

      {/* Featured Products in this Guide */}
      {featuredProducts.length > 0 && (
        <div className="pt-10 border-t border-neutral-200 space-y-6">
          <h3 className="text-xl font-black text-neutral-900 tracking-tight">
            Featured in this Guide
          </h3>
          <div className="grid grid-cols-2 sm:grid-cols-3 gap-4 sm:gap-6">
            {featuredProducts.map((p) => (
              <ProductCard key={p.id} product={p} />
            ))}
          </div>
        </div>
      )}
    </article>
  );
}

