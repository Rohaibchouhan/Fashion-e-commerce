import React from "react";
import { PolicyWrapper } from "@/components/layout/policy-wrapper";

export default function CancellationPolicyPage() {
  return (
    <PolicyWrapper
      title="Cancellation Policy"
      subtitle="Procedures for order modifications and pre-dispatch cancellation requests"
      lastUpdated="November 1, 2026"
    >
      <section className="space-y-3">
        <h2 className="text-base font-bold text-neutral-900">1. Cancellation Window</h2>
        <p>
          Because our warehouse team begins automated order routing quickly after payment authorization, orders can be cancelled or altered within a 45-minute window following placement.
        </p>
      </section>

      <section className="space-y-3">
        <h2 className="text-base font-bold text-neutral-900">2. How to Request Cancellation</h2>
        <p>
          You can request an instant cancellation directly from your order details screen in your Customer Dashboard, or by immediately contacting care@wearday.com with your Order Number in the subject line.
        </p>
      </section>

      <section className="space-y-3">
        <h2 className="text-base font-bold text-neutral-900">3. Orders Already Dispatched</h2>
        <p>
          If your shipment has already transitioned to "Packed" or "Dispatched", we are unable to intercept the parcel in transit. In this case, please utilize our free prepaid returns portal upon delivery for a complete refund.
        </p>
      </section>
    </PolicyWrapper>
  );
}

