import React from "react";
import { PolicyWrapper } from "@/components/layout/policy-wrapper";

export default function CookiePolicyPage() {
  return (
    <PolicyWrapper
      title="Cookie Policy"
      subtitle="How we utilize local storage, session cookies, and performance caching"
      lastUpdated="November 1, 2026"
    >
      <section className="space-y-3">
        <h2 className="text-base font-bold text-neutral-900">1. Essential Cookies & Local State</h2>
        <p>
          We utilize essential session tokens and browser local storage to maintain your synchronized shopping bag, wishlist, and authenticated customer session without requiring page refreshes across navigation.
        </p>
      </section>

      <section className="space-y-3">
        <h2 className="text-base font-bold text-neutral-900">2. Analytics & Performance</h2>
        <p>
          Aggregated, anonymized performance metrics help us understand page load speeds and identify filter interactions to refine your browsing experience.
        </p>
      </section>

      <section className="space-y-3">
        <h2 className="text-base font-bold text-neutral-900">3. Managing Cookie Preferences</h2>
        <p>
          You may disable non-essential cookies via your browser settings at any time. Note that disabling essential session storage may prevent shopping bag synchronization across tabs.
        </p>
      </section>
    </PolicyWrapper>
  );
}

