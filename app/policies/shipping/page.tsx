import React from "react";
import { PolicyWrapper } from "@/components/layout/policy-wrapper";
import { APP_CONFIG } from "@/constants";

export default function ShippingPolicyPage() {
  return (
    <PolicyWrapper
      title="Shipping Policy"
      subtitle="Fulfillment speeds, carrier partnerships, and international delivery rates"
      lastUpdated="November 1, 2026"
    >
      <section className="space-y-3">
        <h2 className="text-base font-bold text-neutral-900">1. Order Processing Speeds</h2>
        <p>
          All orders placed prior to 2:00 PM EST Monday through Friday are packed and dispatched on the same business day from our automated New Jersey logistics center.
        </p>
      </section>

      <section className="space-y-3">
        <h2 className="text-base font-bold text-neutral-900">2. Free Shipping Threshold</h2>
        <p>
          We offer complimentary Standard Ground Delivery on all domestic orders totaling ${APP_CONFIG.freeShippingThreshold} or more (calculated before tax and after any promotional discount codes).
        </p>
      </section>

      <section className="space-y-3">
        <h2 className="text-base font-bold text-neutral-900">3. Domestic Shipping Options</h2>
        <ul className="list-disc pl-5 space-y-1">
          <li><strong>Standard Ground (3-5 business days):</strong> $5.99 (Free on orders $50+)</li>
          <li><strong>Express Air (1-2 business days):</strong> $14.99</li>
          <li><strong>Priority Next Day (Guaranteed next day):</strong> $24.99</li>
        </ul>
      </section>
    </PolicyWrapper>
  );
}

