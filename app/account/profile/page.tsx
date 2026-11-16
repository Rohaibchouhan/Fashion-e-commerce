"use client";

import React, { useState } from "react";
import Image from "next/image";
import { useAuthStore } from "@/stores/auth-store";
import { AuthService } from "@/services/auth.service";
import { Input } from "@/components/ui/input";
import { Button } from "@/components/ui/button";
import { toast } from "@/components/ui/toast";
import { User, Camera, ShieldCheck } from "lucide-react";

export default function AccountProfilePage() {
  const { user, updateUser } = useAuthStore();

  const [name, setName] = useState(user?.name || "Alex Morgan");
  const [email, setEmail] = useState(user?.email || "alex.morgan@example.com");
  const [phone, setPhone] = useState(user?.phone || "+1 (555) 019-2834");
  const [isSaving, setIsSaving] = useState(false);
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

  const handleSave = async (e: React.FormEvent) => {
    e.preventDefault();
    setFieldErrors({});

    const newErrors: Record<string, string> = {};
    if (!name.trim()) newErrors.name = "Full name cannot be empty.";
    if (!email.trim()) newErrors.email = "Email address cannot be empty.";

    if (Object.keys(newErrors).length > 0) {
      setFieldErrors(newErrors);
      toast.error("Validation Required", "Please check your required profile fields.");
      return;
    }

    setIsSaving(true);
    try {
      const updatedUser = await AuthService.updateProfile({ name, email, phone });
      updateUser(updatedUser);
      toast.success("Profile Updated", "Your contact preferences were saved.");
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
      toast.error("Update Failed", err.message || "Could not save profile changes.");
    } finally {
      setIsSaving(false);
    }
  };

  return (
    <div className="space-y-6">
      <div className="bg-white rounded-3xl border border-neutral-200 p-6 sm:p-8 shadow-xs">
        <h1 className="text-xl sm:text-2xl font-black text-neutral-900 tracking-tight">
          Personal Information
        </h1>
        <p className="text-xs text-neutral-500 mt-1">
          Update your public profile and verified contact details.
        </p>

        {/* Profile Avatar Banner */}
        <div className="flex items-center gap-4 py-6 border-b border-neutral-100">
          <div className="relative w-16 h-16 rounded-full overflow-hidden bg-neutral-900 text-white font-black text-lg flex items-center justify-center shrink-0">
            {user?.name
              ?.split(" ")
              .map((n) => n[0])
              .join("") || "AM"}
          </div>
          <div>
            <h3 className="text-sm font-bold text-neutral-900">{user?.name}</h3>
            <p className="text-xs text-neutral-500">
              Role: <span className="font-semibold text-neutral-800">{user?.role || "Customer"}</span>
            </p>
          </div>
        </div>

        <form onSubmit={handleSave} className="space-y-5 pt-6 max-w-xl">
          <Input
            label="Full Name *"
            value={name}
            onChange={(e) => {
              setName(e.target.value);
              clearFieldError("name");
            }}
            error={fieldErrors.name}
            required
          />

          <Input
            label="Primary Email Address *"
            type="email"
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
            value={phone}
            onChange={(e) => {
              setPhone(e.target.value);
              clearFieldError("phone");
            }}
            error={fieldErrors.phone}
            placeholder="+1 (555) 234-5678"
          />

          <div className="pt-2">
            <Button type="submit" isLoading={isSaving} disabled={isSaving} className="px-8 h-11 rounded-full font-bold text-xs">
              Save Profile Changes
            </Button>
          </div>
        </form>
      </div>
    </div>
  );
}
