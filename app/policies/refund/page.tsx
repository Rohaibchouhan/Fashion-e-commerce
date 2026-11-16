import React from "react";
import { PolicyWrapper } from "@/components/layout/policy-wrapper";

export default function RefundPolicyPage() {
  return (
    <PolicyWrapper
      title="Refund Policy"
      subtitle="Reimbursement timelines, payment provider processing, and fee schedules"
      lastUpdated="November 1, 2026"
    >
      <section className="space-y-3">
        <h2 className="text-base font-bold text-neutral-900">1. Refund Processing Time</h2>
        <p>
          Once your returned package arrives at our facility, our quality team inspects the items within 48 business hours. Approved refunds are credited back to your original payment method within 2 to 3 business days.
        </p>
      </section>

      <section className="space-y-3">
        <h2 className="text-base font-bold text-neutral-900">2. Payment Methods Credited</h2>
        <p>
          Refunds are automatically issued to the credit card, Apple Pay, or PayPal account originally used for the transaction. If you utilized a gift card or store credit, the credit is restored to your balance immediately.
        </p>
      </section>

      <section className="space-y-3">
        <h2 className="text-base font-bold text-neutral-900">3. Non-Refundable Shipping Charges</h2>
        <p>
          Original expedited or overnight air shipping fees are non-refundable unless the return is due to a fulfillment error or verified manufacturing defect.
        </p>
      </section>
    </PolicyWrapper>
  );
}

