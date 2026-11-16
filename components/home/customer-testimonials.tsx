import React from "react";
import Image from "next/image";
import { Star, CheckCircle } from "lucide-react";

export const CustomerTestimonials: React.FC = () => {
  const testimonials = [
    {
      name: "Daniel Hayes",
      city: "Austin, TX",
      role: "Verified Buyer",
      rating: 5,
      review:
        "The Easy Chino Pants are hands down the best fitting everyday trousers I have ever owned. Tailored in the front, but that discrete elastic back waist makes 8-hour workdays completely effortless.",
      avatar: "https://images.unsplash.com/photo-1507003211169-0a1dd7228f2d?w=200&auto=format&fit=crop&q=80",
      item: "Easy Chino Pants (British Khaki)",
    },
    {
      name: "Claire Moreau",
      city: "Seattle, WA",
      role: "Verified Buyer",
      rating: 5,
      review:
        "The Linen Blend Shirt has the most beautiful natural drape. The slub texture is breathable and washes like a dream without losing structural form. WearDay is my new wardrobe staple.",
      avatar: "https://images.unsplash.com/photo-1534528741775-53994a69daeb?w=200&auto=format&fit=crop&q=80",
      item: "Linen Blend Shirt (Sky Striped)",
    },
    {
      name: "Marcus Thorne",
      city: "Brooklyn, NY",
      role: "Verified Buyer",
      rating: 5,
      review:
        "Heavyweight, boxy drop shoulder, and no ugly branding anywhere. The Relaxed Fit Tee punches way above its price point compared to luxury fashion houses charging $150.",
      avatar: "https://images.unsplash.com/photo-1500648767791-00dcc994a43e?w=200&auto=format&fit=crop&q=80",
      item: "Relaxed Fit Tee (Oatmeal Heather)",
    },
  ];

  return (
    <section className="py-14 sm:py-20 bg-neutral-100/50">
      <div className="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8">
        <div className="text-center max-w-xl mx-auto mb-12">
          <span className="text-[11px] font-bold uppercase tracking-wider text-neutral-500">
            Real Reviews From Real Wearers
          </span>
          <h2 className="text-2xl sm:text-3xl font-black text-neutral-900 tracking-tight mt-1">
            Loved By Thousands Every Day
          </h2>
          <p className="text-xs sm:text-sm text-neutral-600 mt-2">
            Average customer rating of <strong>4.9 / 5.0</strong> based on 1,400+ verified customer reviews.
          </p>
        </div>

        <div className="grid grid-cols-1 md:grid-cols-3 gap-6">
          {testimonials.map((t, i) => (
            <div
              key={i}
              className="bg-white rounded-3xl p-6 sm:p-7 border border-neutral-200/80 shadow-xs flex flex-col justify-between"
            >
              <div className="space-y-4">
                <div className="flex items-center gap-1">
                  {Array.from({ length: t.rating }).map((_, j) => (
                    <Star key={j} className="w-4 h-4 fill-amber-400 text-amber-400" />
                  ))}
                </div>
                <p className="text-xs sm:text-sm text-neutral-700 leading-relaxed italic">
                  "{t.review}"
                </p>
              </div>

              <div className="pt-6 mt-4 border-t border-neutral-100 flex items-center justify-between">
                <div className="flex items-center gap-3">
                  <div className="relative w-10 h-10 rounded-full overflow-hidden bg-neutral-100 shrink-0">
                    <Image src={t.avatar} alt={t.name} fill className="object-cover" />
                  </div>
                  <div>
                    <div className="flex items-center gap-1.5">
                      <h4 className="text-xs font-bold text-neutral-900">{t.name}</h4>
                      <CheckCircle className="w-3.5 h-3.5 text-emerald-600" />
                    </div>
                    <span className="text-[10px] text-neutral-400 font-medium">
                      {t.city} • {t.role}
                    </span>
                  </div>
                </div>
              </div>
            </div>
          ))}
        </div>
      </div>
    </section>
  );
};

