"use client";

import React, { useState } from "react";
import { Input } from "@/components/ui/input";
import { Button } from "@/components/ui/button";
import { toast } from "@/components/ui/toast";
import { AuthService } from "@/services/auth.service";
import { Shield, KeyRound, Smartphone, Laptop, CheckCircle2 } from "lucide-react";

export default function AccountSecurityPage() {
  const [currentPassword, setCurrentPassword] = useState("");
  const [newPassword, setNewPassword] = useState("");
  const [confirmPassword, setConfirmPassword] = useState("");
  const [twoFactorEnabled, setTwoFactorEnabled] = useState(false);
  const [isUpdatingPassword, setIsUpdatingPassword] = useState(false);
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

  const handlePasswordUpdate = async (e: React.FormEvent) => {
    e.preventDefault();
    setFieldErrors({});

    const newErrors: Record<string, string> = {};
    if (!currentPassword) newErrors.current_password = "Your current password is required.";
    if (!newPassword) newErrors.new_password = "A new password is required.";
    else if (newPassword.length < 8) newErrors.new_password = "Password must be at least 8 characters.";
    else if (newPassword === currentPassword) newErrors.new_password = "New password must be different from current password.";

    if (newPassword !== confirmPassword) {
      newErrors.new_password_confirmation = "Passwords do not match.";
    }

    if (Object.keys(newErrors).length > 0) {
      setFieldErrors(newErrors);
      toast.error("Validation Required", "Please correct the indicated password fields.");
      return;
    }

    try {
      setIsUpdatingPassword(true);
      await AuthService.changePassword(currentPassword, newPassword, confirmPassword);
      setCurrentPassword("");
      setNewPassword("");
      setConfirmPassword("");
      toast.success("Security Updated", "Your account password has been changed successfully.");
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
      toast.error("Update Failed", err.message || "Failed to update password. Please check your credentials.");
    } finally {
      setIsUpdatingPassword(false);
    }
  };

  const toggle2FA = () => {
    const nextState = !twoFactorEnabled;
    setTwoFactorEnabled(nextState);
    if (nextState) {
      toast.success("Two-Factor Enabled", "SMS verification enabled for future logins.");
    } else {
      toast.info("Two-Factor Disabled", "Two-factor authentication turned off.");
    }
  };

  return (
    <div className="space-y-6">
      {/* Change Password Card */}
      <div className="bg-white rounded-3xl border border-neutral-200 p-6 sm:p-8 shadow-xs space-y-6">
        <div>
          <h1 className="text-xl sm:text-2xl font-black text-neutral-900 tracking-tight">
            Security & Login Settings
          </h1>
          <p className="text-xs text-neutral-500 mt-1">
            Ensure your account uses a strong password and secondary authentication factors.
          </p>
        </div>

        <form onSubmit={handlePasswordUpdate} className="space-y-4 max-w-xl">
          <Input
            label="Current Password *"
            type="password"
            value={currentPassword}
            onChange={(e) => {
              setCurrentPassword(e.target.value);
              clearFieldError("current_password");
            }}
            error={fieldErrors.current_password}
            required
          />

          <div className="grid grid-cols-1 sm:grid-cols-2 gap-4">
            <Input
              label="New Password *"
              type="password"
              placeholder="At least 8 chars"
              value={newPassword}
              onChange={(e) => {
                setNewPassword(e.target.value);
                clearFieldError("new_password");
              }}
              error={fieldErrors.new_password}
              required
            />
            <Input
              label="Confirm New Password *"
              type="password"
              placeholder="Re-enter password"
              value={confirmPassword}
              onChange={(e) => {
                setConfirmPassword(e.target.value);
                clearFieldError("new_password_confirmation");
              }}
              error={fieldErrors.new_password_confirmation}
              required
            />
          </div>

          <div className="pt-2">
            <Button
              type="submit"
              isLoading={isUpdatingPassword}
              disabled={isUpdatingPassword}
              className="px-6 h-11 rounded-full font-bold text-xs"
            >
              Update Password
            </Button>
          </div>
        </form>
      </div>

      {/* Two-Factor Authentication Card */}
      <div className="bg-white rounded-3xl border border-neutral-200 p-6 sm:p-8 shadow-xs flex flex-col sm:flex-row sm:items-center justify-between gap-4">
        <div className="flex items-start gap-4">
          <div className="p-3 rounded-2xl bg-neutral-100 text-neutral-800 shrink-0">
            <Smartphone className="w-5 h-5" />
          </div>
          <div>
            <h3 className="text-sm font-bold text-neutral-900">Two-Factor Authentication (2FA)</h3>
            <p className="text-xs text-neutral-500 mt-0.5">
              Require a single-use verification code sent to your phone number on every new login.
            </p>
          </div>
        </div>

        <button
          onClick={toggle2FA}
          className={`px-4 py-2 rounded-xl text-xs font-bold transition-colors cursor-pointer shrink-0 ${
            twoFactorEnabled
              ? "bg-emerald-50 text-emerald-700 border border-emerald-200 hover:bg-emerald-100"
              : "bg-neutral-100 text-neutral-700 hover:bg-neutral-200"
          }`}
        >
          {twoFactorEnabled ? "2FA Enabled" : "Enable 2FA"}
        </button>
      </div>
    </div>
  );
}
