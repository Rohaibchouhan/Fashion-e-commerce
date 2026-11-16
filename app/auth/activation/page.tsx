"use client";

import React, { useState } from "react";
import Link from "next/link";
import { useRouter } from "next/navigation";
import { Button } from "@/components/ui/button";
import { toast } from "@/components/ui/toast";
import { useAuthStore } from "@/stores/auth-store";
import { CheckCircle2, ShieldCheck, Mail } from "lucide-react";
import { APP_CONFIG } from "@/constants";

export default function AccountActivationPage() {
  const router = useRouter();
  const login = useAuthStore((s) => s.login);

  const [code, setCode] = useState("892144");
  const [isVerifying, setIsVerifying] = useState(false);
  const [verified, setVerified] = useState(false);

  const handleVerify = async (e: React.FormEvent) => {
    e.preventDefault();
    if (!code.trim()) return;

    setIsVerifying(true);
    await new Promise((r) => setTimeout(r, 500));
    setIsVerifying(false);
    setVerified(true);
    login("alex.morgan@example.com", "Alex Morgan");
    toast.success("Account Activated!", "Your email has been verified. Welcome aboard!");
  };

  return (
    <div className="min-h-[75vh] flex items-center justify-center px-4 py-12 sm:px-6 lg:px-8">
      <div className="w-full max-w-md space-y-8 bg-white p-8 sm:p-10 rounded-3xl border border-neutral-200 shadow-sm text-center">
        <div className="space-y-2">
          <Link href="/" className="text-2xl font-black text-neutral-900 tracking-tight">
            {APP_CONFIG.name}
          </Link>
          <h2 className="text-xl font-bold text-neutral-900">Email Verification</h2>
          <p className="text-xs text-neutral-500">
            We sent a 6-digit confirmation code to your email to verify your ownership.
          </p>
        </div>

        {verified ? (
          <div className="py-6 space-y-4">
            <div className="w-16 h-16 rounded-full bg-emerald-100 text-emerald-600 flex items-center justify-center mx-auto">
              <CheckCircle2 className="w-8 h-8" />
            </div>
            <h3 className="text-base font-bold text-neutral-900">Account Successfully Activated</h3>
            <p className="text-xs text-neutral-600 max-w-xs mx-auto">
              You are now fully authenticated and ready to explore your customer dashboard.
            </p>
            <Button
              onClick={() => router.push("/account")}
              className="w-full h-12 rounded-full font-bold text-xs"
            >
              Go to Account Dashboard
            </Button>
          </div>
        ) : (
          <form onSubmit={handleVerify} className="space-y-4">
            <div className="space-y-1.5 text-left">
              <label className="block text-xs font-semibold uppercase tracking-wider text-neutral-700">
                Verification Code
              </label>
              <input
                type="text"
                maxLength={6}
                value={code}
                onChange={(e) => setCode(e.target.value)}
                className="w-full h-14 text-center tracking-[0.5em] text-xl font-black text-neutral-900 bg-neutral-50 border border-neutral-300 rounded-2xl focus:outline-none focus:ring-2 focus:ring-neutral-900 font-mono"
                required
              />
            </div>

            <Button type="submit" isLoading={isVerifying} className="w-full h-12 rounded-full font-bold text-xs">
              Verify & Activate Account
            </Button>

            <button
              type="button"
              onClick={() => toast.info("New Code Sent", "A new 6-digit code has been dispatched.")}
              className="text-xs text-neutral-500 hover:text-neutral-900 underline block mx-auto pt-2"
            >
              Didn't receive code? Resend
            </button>
          </form>
        )}
      </div>
    </div>
  );
}

