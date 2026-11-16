"use client";

import React, { useState } from "react";
import { Breadcrumb } from "@/components/ui/breadcrumb";
import { ChevronDown, Search, HelpCircle, MessageCircle } from "lucide-react";
import Link from "next/link";
import { APP_CONFIG } from "@/constants";

interface FAQItem {
  question: string;
  answer: string;
  category: "shipping" | "returns" | "sizing" | "payment";
}

const FAQS: FAQItem[] = [
  {
    category: "shipping",
    question: "How long does standard delivery take?",
    answer: `Orders are packed and dispatched from our New Jersey distribution center within 24 hours on business days. Standard Ground delivery takes 3 to 5 business days. Free shipping applies automatically to all orders over $${APP_CONFIG.freeShippingThreshold}.`,
  },
  {
    category: "shipping",
    question: "Do you offer international shipping?",
    answer: "Yes, we ship to over 40 countries including Canada, the UK, Europe, Australia, and Japan. International duties and taxes are calculated at checkout so there are no unexpected fees upon arrival.",
  },
  {
    category: "returns",
    question: "What is your return policy?",
    answer: "We offer a 30-day effortless return window from the date of delivery. Items must be in unwashed, unworn condition with original tags attached. You can generate a prepaid return label directly from our self-service Returns portal.",
  },
  {
    category: "returns",
    question: "How quickly are refunds processed?",
    answer: "Once your package is received and scanned at our facility, refunds are processed back to your original payment method within 2 to 3 business days.",
  },
  {
    category: "sizing",
    question: "How do your sizes fit?",
    answer: "Most of our tees and overshirts are tailored with a modern relaxed fit with subtle drop shoulders. If you prefer a tailored, closer-to-body look, we suggest sizing down one size. Detailed flat garment measurements are provided on every product page.",
  },
  {
    category: "sizing",
    question: "Will garments shrink in the wash?",
    answer: "All our organic combed cotton and French terry fleece are pre-laundered and enzyme-washed before cutting. You can expect less than 1.5% residual shrinkage when washed cold and line-dried.",
  },
  {
    category: "payment",
    question: "Which payment methods do you accept?",
    answer: "We accept Visa, Mastercard, American Express, Apple Pay, Google Pay, and PayPal. All transactions are protected via 256-bit SSL encryption.",
  },
];

export default function FAQPage() {
  const [activeCategory, setActiveCategory] = useState<string>("all");
  const [searchQuery, setSearchQuery] = useState("");
  const [openIdx, setOpenIdx] = useState<number | null>(0);

  const filteredFaqs = FAQS.filter((faq) => {
    const matchesCategory = activeCategory === "all" || faq.category === activeCategory;
    const matchesSearch =
      faq.question.toLowerCase().includes(searchQuery.toLowerCase()) ||
      faq.answer.toLowerCase().includes(searchQuery.toLowerCase());
    return matchesCategory && matchesSearch;
  });

  return (
    <div className="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8 py-8 sm:py-14 space-y-12">
      <Breadcrumb items={[{ label: "Home", href: "/" }, { label: "Help & FAQ" }]} />

      <div className="text-center max-w-xl mx-auto space-y-3">
        <h1 className="text-3xl sm:text-4xl font-black text-neutral-900 tracking-tight">
          Frequently Asked Questions
        </h1>
        <p className="text-xs sm:text-sm text-neutral-500">
          Everything you need to know about our organic fabrics, shipping speeds, and effortless returns.
        </p>

        {/* Search */}
        <div className="relative max-w-md mx-auto pt-2">
          <Search className="w-4 h-4 text-neutral-400 absolute left-4 top-1/2 -translate-y-1/2" />
          <input
            type="text"
            placeholder="Search questions (e.g. shipping, returns, sizing)..."
            value={searchQuery}
            onChange={(e) => setSearchQuery(e.target.value)}
            className="w-full h-11 pl-11 pr-4 text-xs bg-white border border-neutral-300 rounded-full focus:outline-none focus:ring-2 focus:ring-neutral-900"
          />
        </div>
      </div>

      {/* Category Pills */}
      <div className="flex justify-center gap-2 flex-wrap">
        {[
          { id: "all", label: "All Questions" },
          { id: "shipping", label: "Shipping & Delivery" },
          { id: "returns", label: "Returns & Exchanges" },
          { id: "sizing", label: "Sizing & Care" },
          { id: "payment", label: "Payments & Security" },
        ].map((cat) => (
          <button
            key={cat.id}
            onClick={() => setActiveCategory(cat.id)}
            className={`px-4 py-2 text-xs font-semibold rounded-full border transition-all cursor-pointer ${
              activeCategory === cat.id
                ? "bg-neutral-900 text-white border-neutral-900"
                : "bg-white text-neutral-700 border-neutral-200 hover:border-neutral-400"
            }`}
          >
            {cat.label}
          </button>
        ))}
      </div>

      {/* Accordion List */}
      <div className="space-y-3">
        {filteredFaqs.length === 0 ? (
          <div className="py-12 text-center text-xs text-neutral-500">
            No questions matched your search. Contact our client concierge for instant help.
          </div>
        ) : (
          filteredFaqs.map((faq, i) => {
            const isOpen = openIdx === i;
            return (
              <div
                key={i}
                className="bg-white rounded-2xl border border-neutral-200/80 overflow-hidden shadow-2xs transition-all"
              >
                <button
                  onClick={() => setOpenIdx(isOpen ? null : i)}
                  className="w-full flex items-center justify-between p-5 text-left text-xs sm:text-sm font-bold text-neutral-900 cursor-pointer"
                >
                  <span>{faq.question}</span>
                  <ChevronDown
                    className={`w-4 h-4 text-neutral-500 transition-transform ${
                      isOpen ? "rotate-180" : ""
                    }`}
                  />
                </button>
                {isOpen && (
                  <div className="px-5 pb-5 text-xs text-neutral-600 leading-relaxed border-t border-neutral-100 pt-3">
                    {faq.answer}
                  </div>
                )}
              </div>
            );
          })
        )}
      </div>

      {/* Contact Concierge CTA */}
      <div className="p-8 rounded-3xl bg-neutral-100/80 border border-neutral-200 text-center space-y-3">
        <MessageCircle className="w-8 h-8 text-neutral-700 mx-auto" />
        <h3 className="text-base font-bold text-neutral-900">Still have questions?</h3>
        <p className="text-xs text-neutral-600 max-w-sm mx-auto">
          Our client concierge team is ready to assist with sizing advice or special orders.
        </p>
        <Link
          href="/contact"
          className="inline-flex items-center justify-center px-6 py-2.5 rounded-full bg-neutral-900 text-white text-xs font-semibold hover:bg-neutral-800 transition-colors"
        >
          Contact Customer Care
        </Link>
      </div>
    </div>
  );
}

