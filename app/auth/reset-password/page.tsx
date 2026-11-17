"use client";

import React, { useState, Suspense } from "react";
import Link from "next/link";
import { useRouter, useSearchParams } from "next/navigation";
import { Input } from "@/components/ui/input";
import { Button } from "@/components/ui/button";
import { toast } from "@/components/ui/toast";
import { AuthService } from "@/services/auth.service";
import { Lock, CheckCircle2 } from "lucide-react";
import { APP_CONFIG } from "@/constants";

function ResetPasswordContent() {
  const router = useRouter();
  const searchParams = useSearchParams();
  const token = searchParams.get("token") || "";
  const email = searchParams.get("email") || "";

  const [password, setPassword] = useState("");
  const [confirmPassword, setConfirmPassword] = useState("");
  const [isLoading, setIsLoading] = useState(false);
  const [success, setSuccess] = useState(false);

  const handleSubmit = async (e: React.FormEvent) => {
    e.preventDefault();
    if (password.length < 8) {
      toast.error("Weak Password", "Password must be at least 8 characters.");
      return;
    }
    if (password !== confirmPassword) {
      toast.error("Mismatch", "Passwords do not match.");
      return;
    }

    try {
      setIsLoading(true);
      await AuthService.resetPassword(token, password, email);
      setSuccess(true);
      toast.success("Password Updated", "You can now log in with your new password.");
    } catch (err: any) {
      toast.error("Reset Failed", err.message || "Failed to update password.");
    } finally {
      setIsLoading(false);
    }
  };

  return (
    <div className="min-h-[75vh] flex items-center justify-center px-4 py-12 sm:px-6 lg:px-8">
      <div className="w-full max-w-md space-y-8 bg-white p-8 sm:p-10 rounded-3xl border border-neutral-200 shadow-sm">
        <div className="text-center space-y-2">
          <Link href="/" className="text-2xl font-black text-neutral-900 tracking-tight">
            {APP_CONFIG.name}
          </Link>
          <h2 className="text-xl font-bold text-neutral-900">Set New Password</h2>
          <p className="text-xs text-neutral-500">
            Choose a strong password to protect your account.
          </p>
        </div>

        {success ? (
          <div className="text-center space-y-4 py-4">
            <div className="w-12 h-12 rounded-full bg-emerald-100 text-emerald-600 flex items-center justify-center mx-auto">
              <CheckCircle2 className="w-6 h-6" />
            </div>
            <h3 className="text-base font-bold text-neutral-900">Password Changed</h3>
            <p className="text-xs text-neutral-500">
              Your password has been securely reset. You can now access your account.
            </p>
            <Button onClick={() => router.push("/auth/login")} className="w-full h-11 rounded-full">
              Proceed to Sign In
            </Button>
          </div>
        ) : (
          <form onSubmit={handleSubmit} className="space-y-4">
            <Input
              label="New Password"
              type="password"
              placeholder="••••••••"
              value={password}
              onChange={(e) => setPassword(e.target.value)}
              helperText="Minimum 8 characters with numbers and symbols"
              required
            />

            <Input
              label="Confirm New Password"
              type="password"
              placeholder="••••••••"
              value={confirmPassword}
              onChange={(e) => setConfirmPassword(e.target.value)}
              required
            />

            <Button type="submit" isLoading={isLoading} className="w-full h-11 rounded-full">
              Update Password
            </Button>

            <div className="text-center pt-2">
              <Link href="/auth/login" className="text-xs font-semibold text-neutral-600 hover:underline">
                Remember your password? Sign in
              </Link>
            </div>
          </form>
        )}
      </div>
    </div>
  );
}

export default function ResetPasswordPage() {
  return (
    <Suspense fallback={<div className="p-12 text-center text-xs text-neutral-400">Loading form...</div>}>
      <ResetPasswordContent />
    </Suspense>
  );
}
