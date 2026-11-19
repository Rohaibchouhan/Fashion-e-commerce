"use client";

import React, { useState, Suspense } from "react";
import Link from "next/link";
import { useRouter, useSearchParams } from "next/navigation";
import { Input } from "@/components/ui/input";
import { Button } from "@/components/ui/button";
import { toast } from "@/components/ui/toast";
import { useAuthStore } from "@/stores/auth-store";
import { AuthService } from "@/services/auth.service";
import { Lock, ArrowRight, Sparkles, UserCheck } from "lucide-react";
import { APP_CONFIG } from "@/constants";

function LoginForm() {
  const router = useRouter();
  const searchParams = useSearchParams();
  const setAuth = useAuthStore((s) => s.setAuth);

  const redirectUrl = searchParams.get("redirect") || "/account";

  const [email, setEmail] = useState("alex.morgan@example.com");
  const [password, setPassword] = useState("Password123!");
  const [isLoading, setIsLoading] = useState(false);
  const [fieldErrors, setFieldErrors] = useState<Record<string, string>>({});

  const clearFieldError = (field: string) => {
    if (fieldErrors[field]) {
      setFieldErrors((prev) => {
        const next = { ...prev };
        delete next[field];
        return next;
      });
    }
  };

  const handleSubmit = async (e: React.FormEvent) => {
    e.preventDefault();
    setFieldErrors({});

    const newErrors: Record<string, string> = {};
    if (!email.trim()) newErrors.email = "Please enter your email address.";
    if (!password) newErrors.password = "Please enter your password.";

    if (Object.keys(newErrors).length > 0) {
      setFieldErrors(newErrors);
      toast.error("Required Fields", "Please enter your email and password.");
      return;
    }

    try {
      setIsLoading(true);
      const res = await AuthService.login(email, password);
      setAuth(res.user, res.token);
      toast.success("Welcome Back", `Signed in as ${res.user.name}.`);
      router.push(redirectUrl);
    } catch (err: any) {
      if (err.errors) {
        const mappedErrors: Record<string, string> = {};
        Object.entries(err.errors).forEach(([field, messages]) => {
          if (Array.isArray(messages) && messages.length > 0) {
            mappedErrors[field] = messages[0];
          }
        });
        setFieldErrors(mappedErrors);
      }
      toast.error("Sign In Failed", err.message || "Invalid email or password credentials.");
    } finally {
      setIsLoading(false);
    }
  };

  const handleDemoLogin = async () => {
    try {
      setIsLoading(true);
      const res = await AuthService.login("alex.morgan@example.com", "Password123!");
      setAuth(res.user, res.token);
      toast.success("Demo Account Activated", "Signed in as Alex Morgan.");
      router.push(redirectUrl);
    } catch (err: any) {
      toast.error("Sign In Failed", err.message || "Could not sign in with demo credentials.");
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
          <h2 className="text-xl font-bold text-neutral-900">Sign In to Your Account</h2>
          <p className="text-xs text-neutral-500">
            Access your orders, saved addresses, returns, and wishlist.
          </p>
        </div>

        {/* Demo Fast Login Pill */}
        <button
          type="button"
          onClick={handleDemoLogin}
          disabled={isLoading}
          className="w-full py-2.5 px-4 bg-amber-50 hover:bg-amber-100 border border-amber-200 rounded-2xl flex items-center justify-between text-xs font-semibold text-amber-900 transition-colors cursor-pointer disabled:opacity-50"
        >
          <span className="flex items-center gap-2">
            <Sparkles className="w-4 h-4 text-amber-600" />
            Quick Demo Login (Alex Morgan)
          </span>
          <ArrowRight className="w-3.5 h-3.5" />
        </button>

        <form onSubmit={handleSubmit} className="space-y-4">
          <Input
            label="Email Address *"
            type="email"
            value={email}
            onChange={(e) => {
              setEmail(e.target.value);
              clearFieldError("email");
            }}
            error={fieldErrors.email}
            required
          />

          <div className="space-y-1">
            <div className="flex justify-between items-center text-xs">
              <label className="font-semibold uppercase tracking-wider text-neutral-700">
                Password *
              </label>
              <Link
                href="/auth/forgot-password"
                className="text-neutral-500 hover:text-neutral-900 underline"
              >
                Forgot Password?
              </Link>
            </div>
            <Input
              type="password"
              value={password}
              onChange={(e) => {
                setPassword(e.target.value);
                clearFieldError("password");
              }}
              error={fieldErrors.password}
              required
            />
          </div>

          <div className="flex items-center justify-between text-xs text-neutral-600 pt-1">
            <label className="flex items-center gap-2 cursor-pointer">
              <input type="checkbox" defaultChecked className="rounded accent-neutral-900" />
              <span>Remember this browser</span>
            </label>
          </div>

          <Button type="submit" isLoading={isLoading} disabled={isLoading} className="w-full h-12 rounded-full font-bold text-xs mt-2">
            Sign In to Account
          </Button>
        </form>

        <div className="text-center text-xs text-neutral-500 pt-4 border-t border-neutral-100">
          <span>Don&apos;t have an account yet? </span>
          <Link href="/auth/register" className="font-bold text-neutral-900 hover:underline">
            Create an Account
          </Link>
        </div>
      </div>
    </div>
  );
}

export default function LoginPage() {
  return (
    <Suspense fallback={<div className="min-h-[75vh] flex items-center justify-center text-xs text-neutral-400">Loading sign in...</div>}>
      <LoginForm />
    </Suspense>
  );
}
