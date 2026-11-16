"use client";

import React, { useState } from "react";
import Link from "next/link";
import { useRouter } from "next/navigation";
import { Input } from "@/components/ui/input";
import { Button } from "@/components/ui/button";
import { toast } from "@/components/ui/toast";
import { useAuthStore } from "@/stores/auth-store";
import { AuthService } from "@/services/auth.service";
import { APP_CONFIG } from "@/constants";

export default function RegisterPage() {
  const router = useRouter();
  const setAuth = useAuthStore((s) => s.setAuth);

  const [name, setName] = useState("");
  const [email, setEmail] = useState("");
  const [password, setPassword] = useState("");
  const [phone, setPhone] = useState("");
  const [agreed, setAgreed] = useState(true);
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
    if (!name.trim()) newErrors.name = "Full name is required.";
    if (!email.trim()) newErrors.email = "Email address is required.";
    if (!password) newErrors.password = "Password is required.";
    else if (password.length < 8) newErrors.password = "Password must be at least 8 characters.";

    if (Object.keys(newErrors).length > 0) {
      setFieldErrors(newErrors);
      toast.error("Validation Required", "Please fill in all required fields properly.");
      return;
    }

    if (!agreed) {
      toast.error("Terms Required", "Please accept our Terms and Privacy Policy.");
      return;
    }

    try {
      setIsLoading(true);
      const res = await AuthService.register(name, email, password, phone || undefined);
      setAuth(res.user, res.token);
      toast.success("Account Created", "Welcome to the WearDay community.");
      router.push("/account");
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
      toast.error("Registration Failed", err.message || "An error occurred during registration.");
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
          <h2 className="text-xl font-bold text-neutral-900">Create Your Account</h2>
          <p className="text-xs text-neutral-500">
            Enjoy tailored fit recommendations, express checkout, and seamless order tracking.
          </p>
        </div>

        <form onSubmit={handleSubmit} className="space-y-4">
          <Input
            label="Full Name *"
            placeholder="Alex Morgan"
            value={name}
            onChange={(e) => {
              setName(e.target.value);
              clearFieldError("name");
            }}
            error={fieldErrors.name}
            required
          />

          <Input
            label="Email Address *"
            type="email"
            placeholder="alex@example.com"
            value={email}
            onChange={(e) => {
              setEmail(e.target.value);
              clearFieldError("email");
            }}
            error={fieldErrors.email}
            required
          />

          <Input
            label="Phone Number"
            type="tel"
            placeholder="+1 (555) 234-5678"
            value={phone}
            onChange={(e) => {
              setPhone(e.target.value);
              clearFieldError("phone");
            }}
            error={fieldErrors.phone}
          />

          <Input
            label="Password *"
            type="password"
            placeholder="At least 8 characters"
            value={password}
            onChange={(e) => {
              setPassword(e.target.value);
              clearFieldError("password");
            }}
            error={fieldErrors.password}
            required
          />

          <div className="pt-1">
            <label className="flex items-start gap-2.5 cursor-pointer text-xs text-neutral-600 leading-relaxed">
              <input
                type="checkbox"
                checked={agreed}
                onChange={(e) => setAgreed(e.target.checked)}
                className="mt-0.5 rounded accent-neutral-900"
              />
              <span>
                I agree to the{" "}
                <Link href="/policies/terms" className="underline text-neutral-900">
                  Terms & Conditions
                </Link>{" "}
                and{" "}
                <Link href="/policies/privacy" className="underline text-neutral-900">
                  Privacy Policy
                </Link>
                .
              </span>
            </label>
          </div>

          <Button type="submit" isLoading={isLoading} disabled={isLoading} className="w-full h-12 rounded-full font-bold text-xs mt-2">
            Create Account
          </Button>
        </form>

        <div className="text-center text-xs text-neutral-500 pt-4 border-t border-neutral-100">
          <span>Already have an account? </span>
          <Link href="/auth/login" className="font-bold text-neutral-900 hover:underline">
            Sign In
          </Link>
        </div>
      </div>
    </div>
  );
}
