import React from "react";
import { PolicyWrapper } from "@/components/layout/policy-wrapper";

export default function ReturnPolicyPage() {
  return (
    <PolicyWrapper
      title="Return Policy"
      subtitle="Comprehensive 30-day effortless return guidelines"
      lastUpdated="November 1, 2026"
    >
      <section className="space-y-3">
        <h2 className="text-base font-bold text-neutral-900">1. 30-Day Return Window</h2>
        <p>
          We stand by the craftsmanship of every garment. If a piece does not suit your style or fit requirements, you may initiate a return within 30 days of shipment delivery.
        </p>
      </section>

      <section className="space-y-3">
        <h2 className="text-base font-bold text-neutral-900">2. Item Condition Requirements</h2>
        <p>
          To qualify for a full refund or exchange:
        </p>
        <ul className="list-disc pl-5 space-y-1">
          <li>Garments must be unworn, unwashed, and in original condition.</li>
          <li>All care tags, branded paper tags, and spare buttons must remain attached.</li>
          <li>Footwear must be tested indoors only and returned with original shoe box.</li>
        </ul>
      </section>

      <section className="space-y-3">
        <h2 className="text-base font-bold text-neutral-900">3. Prepaid Return Labels</h2>
        <p>
          U.S. returns enjoy complimentary prepaid return shipping labels generated directly from your customer dashboard. Simply affix the label and deposit at any UPS access point.
        </p>
      </section>
    </PolicyWrapper>
  );
}

