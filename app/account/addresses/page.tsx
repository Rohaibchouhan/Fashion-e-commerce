"use client";

import React, { useState } from "react";
import { useAuthStore } from "@/stores/auth-store";
import { Input } from "@/components/ui/input";
import { Button } from "@/components/ui/button";
import { Modal } from "@/components/ui/modal";
import { toast } from "@/components/ui/toast";
import { AuthService } from "@/services/auth.service";
import { MapPin, Plus, Trash2, CheckCircle2, Star } from "lucide-react";
import { Address } from "@/types/auth";

export default function AccountAddressesPage() {
  const { user, addAddress, removeAddress, setDefaultAddress } = useAuthStore();
  const [isModalOpen, setIsModalOpen] = useState(false);
  const [isSaving, setIsSaving] = useState(false);
  const [fieldErrors, setFieldErrors] = useState<Record<string, string>>({});

  const [form, setForm] = useState({
    firstName: "Alex",
    lastName: "Morgan",
    street: "",
    apartment: "",
    city: "",
    state: "",
    zipCode: "",
    country: "United States",
    phone: "",
    isDefaultShipping: true,
  });

  const clearFieldError = (field: string) => {
    if (fieldErrors[field]) {
      setFieldErrors((prev) => {
        const next = { ...prev };
        delete next[field];
        return next;
      });
    }
  };

  const handleAdd = async (e: React.FormEvent) => {
    e.preventDefault();
    setFieldErrors({});

    const newErrors: Record<string, string> = {};
    if (!form.firstName.trim()) newErrors.first_name = "First name is required.";
    if (!form.lastName.trim()) newErrors.last_name = "Last name is required.";
    if (!form.street.trim()) newErrors.street = "Street address is required.";
    if (!form.city.trim()) newErrors.city = "City is required.";
    if (!form.state.trim()) newErrors.state = "State is required.";
    if (!form.zipCode.trim()) newErrors.zip_code = "Postal code is required.";

    if (Object.keys(newErrors).length > 0) {
      setFieldErrors(newErrors);
      toast.error("Required Fields", "Please complete all required fields.");
      return;
    }

    setIsSaving(true);
    try {
      const saved = await AuthService.addAddress(form);
      addAddress(saved);
      toast.success("Address Saved", "New shipping destination added.");
      setIsModalOpen(false);
      setForm({
        firstName: "Alex",
        lastName: "Morgan",
        street: "",
        apartment: "",
        city: "",
        state: "",
        zipCode: "",
        country: "United States",
        phone: "",
        isDefaultShipping: false,
      });
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
      toast.error("Failed to Save", err.message || "Could not save address to server.");
    } finally {
      setIsSaving(false);
    }
  };

  const addresses = user?.addresses || [];

  return (
    <div className="space-y-6">
      <div className="bg-white rounded-3xl border border-neutral-200 p-6 sm:p-8 shadow-xs flex flex-col sm:flex-row sm:items-center justify-between gap-4">
        <div>
          <h1 className="text-xl sm:text-2xl font-black text-neutral-900 tracking-tight">
            Shipping & Billing Addresses
          </h1>
          <p className="text-xs text-neutral-500 mt-1">
            Manage your saved delivery destinations for effortless one-click checkout.
          </p>
        </div>

        <button
          onClick={() => {
            setFieldErrors({});
            setIsModalOpen(true);
          }}
          className="px-5 py-2.5 bg-neutral-900 hover:bg-neutral-800 text-white rounded-full text-xs font-bold transition-colors flex items-center gap-1.5 shadow-xs self-start sm:self-auto cursor-pointer"
        >
          <Plus className="w-3.5 h-3.5" /> Add New Address
        </button>
      </div>

      <div className="grid grid-cols-1 md:grid-cols-2 gap-6">
        {addresses.map((addr) => (
          <div
            key={addr.id}
            className={`bg-white rounded-3xl border p-6 shadow-xs flex flex-col justify-between transition-all ${
              addr.isDefaultShipping
                ? "border-neutral-900 ring-2 ring-neutral-900/10"
                : "border-neutral-200"
            }`}
          >
            <div className="space-y-3">
              <div className="flex items-center justify-between">
                <span className="text-xs font-bold text-neutral-900">
                  {addr.firstName} {addr.lastName}
                </span>
                {addr.isDefaultShipping && (
                  <span className="px-2.5 py-0.5 text-[10px] font-bold uppercase tracking-wider bg-neutral-900 text-white rounded-full">
                    Default Destination
                  </span>
                )}
              </div>

              <div className="text-xs text-neutral-600 space-y-1">
                <p>{addr.street} {addr.apartment}</p>
                <p>{addr.city}, {addr.state} {addr.zipCode}</p>
                <p>{addr.country}</p>
                {addr.phone && <p className="text-neutral-500 pt-1">Phone: {addr.phone}</p>}
              </div>
            </div>

            <div className="pt-6 mt-4 border-t border-neutral-100 flex items-center justify-between text-xs">
              {!addr.isDefaultShipping ? (
                <button
                  onClick={async () => {
                    try {
                      await AuthService.updateAddress(addr.id, { isDefaultShipping: true });
                      setDefaultAddress(addr.id);
                      toast.success("Default Updated", "Primary shipping address set.");
                    } catch (err: any) {
                      toast.error("Error", err.message || "Could not set default.");
                    }
                  }}
                  className="font-semibold text-neutral-900 hover:underline cursor-pointer"
                >
                  Set as Default
                </button>
              ) : (
                <span className="text-emerald-700 font-semibold flex items-center gap-1 text-[11px]">
                  <CheckCircle2 className="w-3.5 h-3.5" /> Primary Address
                </span>
              )}

              <button
                onClick={async () => {
                  try {
                    await AuthService.deleteAddress(addr.id);
                    removeAddress(addr.id);
                    toast.info("Address Removed", "Address removed from your profile.");
                  } catch (err: any) {
                    toast.error("Error", err.message || "Could not delete address.");
                  }
                }}
                className="text-neutral-400 hover:text-rose-600 transition-colors p-1 cursor-pointer"
                aria-label="Delete address"
              >
                <Trash2 className="w-4 h-4" />
              </button>
            </div>
          </div>
        ))}
      </div>

      {/* Add Address Modal */}
      <Modal
        isOpen={isModalOpen}
        onClose={() => setIsModalOpen(false)}
        maxWidth="lg"
        title="Add New Shipping Address"
        description="Provide your delivery coordinates."
      >
        <form onSubmit={handleAdd} className="space-y-4 pt-2">
          <div className="grid grid-cols-2 gap-3">
            <Input
              label="First Name *"
              value={form.firstName}
              onChange={(e) => {
                setForm({ ...form, firstName: e.target.value });
                clearFieldError("first_name");
              }}
              error={fieldErrors.first_name}
              required
            />
            <Input
              label="Last Name *"
              value={form.lastName}
              onChange={(e) => {
                setForm({ ...form, lastName: e.target.value });
                clearFieldError("last_name");
              }}
              error={fieldErrors.last_name}
              required
            />
          </div>

          <Input
            label="Street Address *"
            placeholder="e.g. 100 Main St"
            value={form.street}
            onChange={(e) => {
              setForm({ ...form, street: e.target.value });
              clearFieldError("street");
            }}
            error={fieldErrors.street}
            required
          />

          <div className="grid grid-cols-3 gap-3">
            <Input
              label="Apt / Suite"
              placeholder="Suite 400"
              value={form.apartment}
              onChange={(e) => setForm({ ...form, apartment: e.target.value })}
            />
            <Input
              label="City *"
              value={form.city}
              onChange={(e) => {
                setForm({ ...form, city: e.target.value });
                clearFieldError("city");
              }}
              error={fieldErrors.city}
              required
            />
            <Input
              label="State *"
              value={form.state}
              onChange={(e) => {
                setForm({ ...form, state: e.target.value });
                clearFieldError("state");
              }}
              error={fieldErrors.state}
              required
            />
          </div>

          <div className="grid grid-cols-2 gap-3">
            <Input
              label="Postal Code *"
              value={form.zipCode}
              onChange={(e) => {
                setForm({ ...form, zipCode: e.target.value });
                clearFieldError("zip_code");
              }}
              error={fieldErrors.zip_code}
              required
            />
            <Input
              label="Phone Number"
              value={form.phone}
              onChange={(e) => {
                setForm({ ...form, phone: e.target.value });
                clearFieldError("phone");
              }}
              error={fieldErrors.phone}
              placeholder="+1 (555) 000-0000"
            />
          </div>

          <div className="pt-2">
            <label className="flex items-center gap-2 cursor-pointer text-xs font-semibold text-neutral-800">
              <input
                type="checkbox"
                checked={form.isDefaultShipping}
                onChange={(e) => setForm({ ...form, isDefaultShipping: e.target.checked })}
                className="rounded accent-neutral-900"
              />
              <span>Set as default delivery address</span>
            </label>
          </div>

          <div className="pt-4 flex justify-end gap-2 border-t border-neutral-100">
            <button
              type="button"
              onClick={() => setIsModalOpen(false)}
              className="px-4 py-2 border border-neutral-200 rounded-full text-xs font-semibold text-neutral-600 hover:bg-neutral-50 cursor-pointer"
            >
              Cancel
            </button>
            <Button type="submit" isLoading={isSaving} disabled={isSaving} className="px-6 rounded-full font-bold text-xs">
              Save Address
            </Button>
          </div>
        </form>
      </Modal>
    </div>
  );
}
