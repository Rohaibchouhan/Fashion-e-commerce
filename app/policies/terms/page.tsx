import React from "react";
import { PolicyWrapper } from "@/components/layout/policy-wrapper";

export default function TermsPage() {
  return (
    <PolicyWrapper
      title="Terms and Conditions"
      subtitle="General conditions governing online orders and storefront utilization"
      lastUpdated="November 1, 2026"
    >
      <section className="space-y-3">
        <h2 className="text-base font-bold text-neutral-900">1. Acceptance of Terms</h2>
        <p>
          By browsing, creating an account, or purchasing garments from WearDay, you agree to comply with and be bound by these Terms and Conditions. If you do not agree, you should discontinue use of the platform.
        </p>
      </section>

      <section className="space-y-3">
        <h2 className="text-base font-bold text-neutral-900">2. Customer Account Requirement</h2>
        <p>
          Guest checkout is disabled to ensure order provenance, verified delivery tracking, and simplified return authorization. You are responsible for safeguarding your login credentials.
        </p>
      </section>

      <section className="space-y-3">
        <h2 className="text-base font-bold text-neutral-900">3. Pricing and Stock Availability</h2>
        <p>
          All prices are quoted in USD unless specified otherwise. We reserve the right to correct accidental typographical errors in pricing. If an item is oversold prior to warehouse allocation, you will receive an immediate full refund.
        </p>
      </section>

      <section className="space-y-3">
        <h2 className="text-base font-bold text-neutral-900">4. Intellectual Property</h2>
        <p>
          All imagery, typography, editorial guides, and brand styling are protected under international copyright laws and belong exclusively to WearDay Apparel Inc.
        </p>
      </section>
    </PolicyWrapper>
  );
}

