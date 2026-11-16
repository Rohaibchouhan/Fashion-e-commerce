"use client";

import React, { useState } from "react";
import Link from "next/link";
import { useRouter } from "next/navigation";
import { Input } from "@/components/ui/input";
import { Button } from "@/components/ui/button";
import { toast } from "@/components/ui/toast";
import { AuthService } from "@/services/auth.service";
import { Lock, CheckCircle2 } from "lucide-react";
import { APP_CONFIG } from "@/constants";

export default function ResetPasswordPage() {
  const router = useRouter();
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
      await AuthService.resetPassword("mock-token", password);
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
            Create a secure password for your WearDay account.
          </p>
        </div>

        {success ? (
          <div className="py-6 text-center space-y-4">
            <div className="w-14 h-14 rounded-full bg-emerald-100 text-emerald-600 flex items-center justify-center mx-auto">
              <CheckCircle2 className="w-7 h-7" />
            </div>
            <h3 className="text-sm font-bold text-neutral-900">Password Changed Successfully</h3>
            <p className="text-xs text-neutral-600 max-w-xs mx-auto">
              Your password has been securely updated. Please sign in to proceed.
            </p>
            <Link
              href="/auth/login"
              className="inline-block px-8 py-3 bg-neutral-900 text-white rounded-full text-xs font-bold hover:bg-neutral-800"
            >
              Sign In Now
            </Link>
          </div>
        ) : (
          <form onSubmit={handleSubmit} className="space-y-4">
            <Input
              label="New Password *"
              type="password"
              placeholder="At least 8 characters"
              value={password}
              onChange={(e) => setPassword(e.target.value)}
              required
            />
            <Input
              label="Confirm New Password *"
              type="password"
              placeholder="Re-enter your password"
              value={confirmPassword}
              onChange={(e) => setConfirmPassword(e.target.value)}
              required
            />
            <Button type="submit" isLoading={isLoading} className="w-full h-12 rounded-full font-bold text-xs mt-2">
              Update Password
            </Button>
          </form>
        )}
      </div>
    </div>
  );
}

