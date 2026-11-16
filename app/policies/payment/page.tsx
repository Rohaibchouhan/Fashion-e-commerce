import React from "react";
import { PolicyWrapper } from "@/components/layout/policy-wrapper";

export default function PaymentPolicyPage() {
  return (
    <PolicyWrapper
      title="Payment Policy"
      subtitle="Accepted currency formats, encrypted processing, and anti-fraud protocols"
      lastUpdated="November 1, 2026"
    >
      <section className="space-y-3">
        <h2 className="text-base font-bold text-neutral-900">1. Accepted Payment Methods</h2>
        <p>
          WearDay accepts major international credit and debit cards: Visa, Mastercard, American Express, and Discover. We also support digital wallets including Apple Pay, Google Pay, and PayPal.
        </p>
      </section>

      <section className="space-y-3">
        <h2 className="text-base font-bold text-neutral-900">2. Security & Tokenization</h2>
        <p>
          We never store full card numbers on our servers. All sensitive payment details are encrypted directly via tokenized PCI-DSS Level 1 compliant gateway endpoints before reaching our Laravel API.
        </p>
      </section>

      <section className="space-y-3">
        <h2 className="text-base font-bold text-neutral-900">3. Sales Tax Calculation</h2>
        <p>
          Sales tax is computed automatically based on the precise municipal destination address in accordance with local state tax regulations.
        </p>
      </section>
    </PolicyWrapper>
  );
}

