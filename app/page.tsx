import { HeroBanner } from "@/components/home/hero-banner";
import { CategoryStrip } from "@/components/home/category-strip";
import { PromoBannerCards } from "@/components/home/promo-banner-cards";
import { OutfitBuilder } from "@/components/product/outfit-builder";
import { NewArrivalsSection } from "@/components/home/new-arrivals-section";
import { StylingGuidesSection } from "@/components/home/styling-guides-section";
import { CustomerFavoritesTrustBar } from "@/components/home/customer-favorites-trust-bar";
import { RecommendedSection } from "@/components/home/recommended-section";
import { PromotionalBanner } from "@/components/home/promotional-banner";
import { CustomerTestimonials } from "@/components/home/customer-testimonials";

export default function HomePage() {
  return (
    <div className="space-y-6">
      <HeroBanner />
      <CategoryStrip />
      <PromoBannerCards />
      <OutfitBuilder />
      <NewArrivalsSection />
      <StylingGuidesSection />
      <CustomerFavoritesTrustBar />
      <RecommendedSection />
      <PromotionalBanner />
      <CustomerTestimonials />
    </div>
  );
}
