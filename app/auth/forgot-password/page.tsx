"use client";

import React, { useState } from "react";
import Link from "next/link";
import { Input } from "@/components/ui/input";
import { Button } from "@/components/ui/button";
import { toast } from "@/components/ui/toast";
import { AuthService } from "@/services/auth.service";
import { Mail, ArrowLeft, CheckCircle2 } from "lucide-react";
import { APP_CONFIG } from "@/constants";

export default function ForgotPasswordPage() {
  const [email, setEmail] = useState("");
  const [isSubmitting, setIsSubmitting] = useState(false);
  const [submitted, setSubmitted] = useState(false);

  const handleSubmit = async (e: React.FormEvent) => {
    e.preventDefault();
    if (!email) return;

    try {
      setIsSubmitting(true);
      await AuthService.forgotPassword(email);
      setSubmitted(true);
      toast.success("Reset Link Sent", `Check your inbox at ${email}`);
    } catch (err: any) {
      toast.error("Error", err.message || "Failed to process request.");
    } finally {
      setIsSubmitting(false);
    }
  };

  return (
    <div className="min-h-[75vh] flex items-center justify-center px-4 py-12 sm:px-6 lg:px-8">
      <div className="w-full max-w-md space-y-8 bg-white p-8 sm:p-10 rounded-3xl border border-neutral-200 shadow-sm">
        <div className="text-center space-y-2">
          <Link href="/" className="text-2xl font-black text-neutral-900 tracking-tight">
            {APP_CONFIG.name}
          </Link>
          <h2 className="text-xl font-bold text-neutral-900">Reset Your Password</h2>
          <p className="text-xs text-neutral-500">
            Enter your registered email and we will send you a secure password reset link.
          </p>
        </div>

        {submitted ? (
          <div className="py-6 text-center space-y-4">
            <div className="w-14 h-14 rounded-full bg-emerald-100 text-emerald-600 flex items-center justify-center mx-auto">
              <CheckCircle2 className="w-7 h-7" />
            </div>
            <h3 className="text-sm font-bold text-neutral-900">Check Your Inbox</h3>
            <p className="text-xs text-neutral-600 max-w-xs mx-auto leading-relaxed">
              We dispatched instructions to <strong>{email}</strong>. Use the link to securely establish a new password.
            </p>
            <div className="pt-2">
              <Link
                href="/auth/reset-password"
                className="text-xs font-semibold text-neutral-900 hover:underline block"
              >
                Go to Reset Form (Simulated Token) →
              </Link>
            </div>
          </div>
        ) : (
          <form onSubmit={handleSubmit} className="space-y-4">
            <Input
              label="Email Address *"
              type="email"
              placeholder="alex@example.com"
              value={email}
              onChange={(e) => setEmail(e.target.value)}
              required
            />
            <Button type="submit" isLoading={isSubmitting} className="w-full h-12 rounded-full font-bold text-xs">
              Send Password Reset Link
            </Button>
          </form>
        )}

        <div className="text-center pt-2">
          <Link
            href="/auth/login"
            className="inline-flex items-center gap-1.5 text-xs font-semibold text-neutral-600 hover:text-neutral-950"
          >
            <ArrowLeft className="w-3.5 h-3.5" /> Back to Sign In
          </Link>
        </div>
      </div>
    </div>
  );
}

