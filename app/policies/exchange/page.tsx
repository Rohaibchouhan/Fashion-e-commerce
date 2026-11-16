import React from "react";
import { PolicyWrapper } from "@/components/layout/policy-wrapper";

export default function ExchangePolicyPage() {
  return (
    <PolicyWrapper
      title="Exchange Policy"
      subtitle="Seamless sizing and colorway replacement guidelines"
      lastUpdated="November 1, 2026"
    >
      <section className="space-y-3">
        <h2 className="text-base font-bold text-neutral-900">1. Instant Exchanges</h2>
        <p>
          We want your everyday wardrobe to fit flawlessly. If you need a different size or alternative colorway, our self-service portal allows instant exchange reservations so your replacement piece doesn't sell out while your return package is in transit.
        </p>
      </section>

      <section className="space-y-3">
        <h2 className="text-base font-bold text-neutral-900">2. Zero Additional Shipping Costs</h2>
        <p>
          All exchanges within the 48 contiguous United States are completely free of charge. We provide the prepaid return label and dispatch your new size with standard ground tracking.
        </p>
      </section>

      <section className="space-y-3">
        <h2 className="text-base font-bold text-neutral-900">3. Price Adjustments on Exchanges</h2>
        <p>
          If you exchange an item for a different size of the exact same piece, you will never be charged if the promotion ended in the interim. Your original promotional pricing is locked.
        </p>
      </section>
    </PolicyWrapper>
  );
}

