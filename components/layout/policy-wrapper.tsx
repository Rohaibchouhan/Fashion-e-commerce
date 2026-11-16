import React from "react";
import Link from "next/link";
import { Breadcrumb } from "@/components/ui/breadcrumb";
import { APP_CONFIG } from "@/constants";

interface PolicyWrapperProps {
  title: string;
  subtitle: string;
  lastUpdated: string;
  children: React.ReactNode;
}

export const PolicyWrapper: React.FC<PolicyWrapperProps> = ({
  title,
  subtitle,
  lastUpdated,
  children,
}) => {
  const policyNav = [
    { label: "Privacy Policy", href: "/policies/privacy" },
    { label: "Terms & Conditions", href: "/policies/terms" },
    { label: "Return Policy", href: "/policies/returns" },
    { label: "Refund Policy", href: "/policies/refund" },
    { label: "Shipping Policy", href: "/policies/shipping" },
    { label: "Cancellation Policy", href: "/policies/cancellation" },
    { label: "Exchange Policy", href: "/policies/exchange" },
    { label: "Payment Policy", href: "/policies/payment" },
    { label: "Cookie Policy", href: "/policies/cookies" },
  ];

  return (
    <div className="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8 py-8 sm:py-14 space-y-10">
      <Breadcrumb items={[{ label: "Home", href: "/" }, { label: "Policies" }, { label: title }]} />

      <div className="grid grid-cols-1 lg:grid-cols-12 gap-10 items-start">
        {/* Policy Quick Nav Sidebar */}
        <aside className="lg:col-span-3 bg-white rounded-3xl border border-neutral-200 p-5 shadow-xs space-y-3 sticky top-24">
          <h4 className="text-xs font-bold uppercase tracking-wider text-neutral-400 px-2">
            Legal & Customer Policies
          </h4>
          <nav className="space-y-1">
            {policyNav.map((item) => (
              <Link
                key={item.href}
                href={item.href}
                className="block px-3 py-2 rounded-xl text-xs font-medium text-neutral-600 hover:text-neutral-950 hover:bg-neutral-50 transition-colors"
              >
                {item.label}
              </Link>
            ))}
          </nav>
        </aside>

        {/* Policy Document Body */}
        <div className="lg:col-span-9 bg-white rounded-3xl border border-neutral-200 p-6 sm:p-10 shadow-xs space-y-8">
          <div className="border-b border-neutral-100 pb-5">
            <span className="text-[11px] font-bold uppercase tracking-wider text-neutral-400">
              Official Store Policy
            </span>
            <h1 className="text-2xl sm:text-3xl font-black text-neutral-900 tracking-tight mt-1">
              {title}
            </h1>
            <p className="text-xs text-neutral-500 mt-1">{subtitle}</p>
            <p className="text-[10px] text-neutral-400 mt-2 font-mono">Last Updated: {lastUpdated}</p>
          </div>

          <div className="prose prose-neutral max-w-none text-xs sm:text-sm text-neutral-700 leading-relaxed space-y-6">
            {children}
          </div>
        </div>
      </div>
    </div>
  );
};

