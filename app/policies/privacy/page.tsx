import React from "react";
import { PolicyWrapper } from "@/components/layout/policy-wrapper";

export default function PrivacyPolicyPage() {
  return (
    <PolicyWrapper
      title="Privacy Policy"
      subtitle="How WearDay collects, protects, and handles your personal information"
      lastUpdated="November 1, 2026"
    >
      <section className="space-y-3">
        <h2 className="text-base font-bold text-neutral-900">1. Information We Collect</h2>
        <p>
          We collect information you provide directly to us when creating a customer account, making a purchase, subscribing to our seasonal newsletters, or contacting our concierge team. This includes your name, shipping address, billing address, phone number, and encrypted payment tokens.
        </p>
      </section>

      <section className="space-y-3">
        <h2 className="text-base font-bold text-neutral-900">2. How We Use Your Data</h2>
        <p>
          We use personal data strictly to fulfill order deliveries, issue RMA returns, transmit real-time delivery notifications via SMS or email, and personalize your wardrobe sizing recommendations. We never sell, rent, or monetize your personal information to third-party ad brokers.
        </p>
      </section>

      <section className="space-y-3">
        <h2 className="text-base font-bold text-neutral-900">3. Data Security & Storage</h2>
        <p>
          All communications are secured with TLS 1.3 encryption. Sensitive financial tokens are processed via PCI-DSS Level 1 compliant gateway tokenization. Passwords are hash-encrypted using salted Argon2/bcrypt algorithms.
        </p>
      </section>

      <section className="space-y-3">
        <h2 className="text-base font-bold text-neutral-900">4. Your Privacy Rights</h2>
        <p>
          Under GDPR and CCPA regulations, you maintain the right to access, export, or permanently erase your customer account and order records at any time by contacting privacy@wearday.com.
        </p>
      </section>
    </PolicyWrapper>
  );
}

