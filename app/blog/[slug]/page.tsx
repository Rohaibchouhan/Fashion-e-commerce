import React from "react";
import Image from "next/image";
import Link from "next/link";
import { notFound } from "next/navigation";
import { BlogService } from "@/services/blog.service";
import { ProductsService } from "@/services/products.service";
import { Breadcrumb } from "@/components/ui/breadcrumb";
import { ProductCard } from "@/components/product/product-card";
import { Clock, ArrowLeft } from "lucide-react";
import { formatDate } from "@/lib/utils";

export default async function BlogPostPage({
  params,
}: {
  params: Promise<{ slug: string }>;
}) {
  const { slug } = await params;
  const post = await BlogService.getBySlug(slug);
  if (!post) {
    notFound();
  }

  const { products } = await ProductsService.getAll({ limit: 3 });

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
          <span>{post.category || "Style Guides"}</span>
          <span>•</span>
          <span className="flex items-center gap-1">
            <Clock className="w-3.5 h-3.5" /> 4 min read
          </span>
          {post.publishedAt && (
            <>
              <span>•</span>
              <span>{formatDate(post.publishedAt)}</span>
            </>
          )}
        </div>

        <h1 className="text-3xl sm:text-5xl font-black text-neutral-900 tracking-tight leading-tight">
          {post.title}
        </h1>
        {post.excerpt && (
          <p className="text-base sm:text-lg text-neutral-600 leading-relaxed font-normal">
            {post.excerpt}
          </p>
        )}

        {/* Author */}
        <div className="flex items-center gap-3 pt-3 border-t border-neutral-100">
          <div className="w-10 h-10 rounded-full bg-neutral-900 text-white font-bold flex items-center justify-center text-xs">
            {post.author ? post.author.slice(0, 2).toUpperCase() : "WD"}
          </div>
          <div>
            <h4 className="text-xs font-bold text-neutral-900">{post.author || "WearDay Editorial"}</h4>
            <p className="text-[10px] text-neutral-400">Atelier Editorial Team</p>
          </div>
        </div>
      </div>

      {/* Hero Cover Image */}
      {post.featuredImage && (
        <div className="relative aspect-21/9 w-full rounded-3xl overflow-hidden bg-neutral-100 shadow-xs">
          <Image src={post.featuredImage} alt={post.title} fill priority className="object-cover" />
        </div>
      )}

      {/* Article Content */}
      <div className="max-w-3xl mx-auto space-y-6 text-sm sm:text-base text-neutral-700 leading-relaxed font-normal">
        {post.content ? (
          <div
            className="prose prose-neutral max-w-none space-y-4"
            dangerouslySetInnerHTML={{ __html: post.content }}
          />
        ) : (
          <p>{post.excerpt}</p>
        )}
      </div>

      {/* Featured Pieces in This Guide */}
      {products.length > 0 && (
        <div className="pt-10 border-t border-neutral-200 space-y-6">
          <div className="flex items-center justify-between">
            <h3 className="text-xl font-bold text-neutral-900">Featured In This Story</h3>
            <Link href="/shop" className="text-xs font-bold text-neutral-900 hover:underline">
              View All Pieces &rarr;
            </Link>
          </div>
          <div className="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-3 gap-6">
            {products.map((p) => (
              <ProductCard key={p.id} product={p} />
            ))}
          </div>
        </div>
      )}

      {/* Back to Journal Link */}
      <div className="pt-6">
        <Link
          href="/blog"
          className="inline-flex items-center gap-2 text-xs font-bold text-neutral-900 hover:underline"
        >
          <ArrowLeft className="w-4 h-4" /> Back to Journal & Styling Guides
        </Link>
      </div>
    </article>
  );
}
