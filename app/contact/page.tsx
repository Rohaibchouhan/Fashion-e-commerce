"use client";

import React, { useState } from "react";
import { Breadcrumb } from "@/components/ui/breadcrumb";
import { Input } from "@/components/ui/input";
import { Button } from "@/components/ui/button";
import { toast } from "@/components/ui/toast";
import { Mail, Phone, MapPin, Clock, MessageSquare, CheckCircle2 } from "lucide-react";
import { APP_CONFIG } from "@/constants";

export default function ContactPage() {
  const [formData, setFormData] = useState({
    name: "",
    email: "",
    orderNumber: "",
    topic: "order_inquiry",
    message: "",
  });
  const [isSubmitting, setIsSubmitting] = useState(false);
  const [submitted, setSubmitted] = useState(false);

  const handleSubmit = async (e: React.FormEvent) => {
    e.preventDefault();
    if (!formData.name || !formData.email || !formData.message) {
      toast.error("Missing Information", "Please fill in all required fields.");
      return;
    }

    setIsSubmitting(true);
    await new Promise((r) => setTimeout(r, 600));
    setIsSubmitting(false);
    setSubmitted(true);
    toast.success("Message Dispatched", "Our client concierge team will respond within 24 hours.");
  };

  return (
    <div className="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8 py-8 sm:py-14 space-y-12">
      <Breadcrumb items={[{ label: "Home", href: "/" }, { label: "Contact Us" }]} />

      <div className="max-w-xl">
        <span className="text-[11px] font-bold uppercase tracking-widest text-neutral-400">
          Client Concierge
        </span>
        <h1 className="text-3xl sm:text-4xl font-black text-neutral-900 tracking-tight mt-1">
          How Can We Assist You Today?
        </h1>
        <p className="text-xs sm:text-sm text-neutral-500 mt-2">
          Whether you need personalized sizing guidance, styling consultation, or order support, our team is at your disposal.
        </p>
      </div>

      <div className="grid grid-cols-1 lg:grid-cols-12 gap-12 items-start">
        {/* Contact Form */}
        <div className="lg:col-span-7 bg-white rounded-3xl p-6 sm:p-10 border border-neutral-200/80 shadow-xs">
          {submitted ? (
            <div className="py-12 text-center space-y-4">
              <div className="w-16 h-16 rounded-full bg-emerald-100 text-emerald-600 flex items-center justify-center mx-auto">
                <CheckCircle2 className="w-8 h-8" />
              </div>
              <h3 className="text-xl font-bold text-neutral-900">Thank You, {formData.name}</h3>
              <p className="text-xs text-neutral-600 max-w-sm mx-auto leading-relaxed">
                Your message has been received (Ticket #WD-{Math.floor(1000 + Math.random() * 9000)}). A dedicated stylist will reply to <strong>{formData.email}</strong> shortly.
              </p>
              <button
                onClick={() => {
                  setSubmitted(false);
                  setFormData({ name: "", email: "", orderNumber: "", topic: "order_inquiry", message: "" });
                }}
                className="mt-4 px-6 py-2.5 bg-neutral-900 text-white text-xs font-semibold rounded-full hover:bg-neutral-800 transition-colors"
              >
                Send Another Message
              </button>
            </div>
          ) : (
            <form onSubmit={handleSubmit} className="space-y-4">
              <div className="grid grid-cols-1 sm:grid-cols-2 gap-4">
                <Input
                  label="Your Full Name *"
                  placeholder="Alex Morgan"
                  value={formData.name}
                  onChange={(e) => setFormData({ ...formData, name: e.target.value })}
                  required
                />
                <Input
                  label="Email Address *"
                  type="email"
                  placeholder="alex@example.com"
                  value={formData.email}
                  onChange={(e) => setFormData({ ...formData, email: e.target.value })}
                  required
                />
              </div>

              <div className="grid grid-cols-1 sm:grid-cols-2 gap-4">
                <Input
                  label="Order Number (Optional)"
                  placeholder="e.g. WD-88219"
                  value={formData.orderNumber}
                  onChange={(e) => setFormData({ ...formData, orderNumber: e.target.value })}
                />
                <div className="space-y-1.5">
                  <label className="block text-xs font-semibold uppercase tracking-wider text-neutral-700">
                    Topic
                  </label>
                  <select
                    value={formData.topic}
                    onChange={(e) => setFormData({ ...formData, topic: e.target.value })}
                    className="w-full h-11 px-4 text-xs font-medium text-neutral-900 bg-white border border-neutral-300 rounded-xl focus:outline-none focus:ring-2 focus:ring-neutral-900"
                  >
                    <option value="order_inquiry">Order Status & Tracking</option>
                    <option value="sizing">Fit & Sizing Consultation</option>
                    <option value="returns">Returns & Exchanges</option>
                    <option value="collaboration">Press & Editorial Inquiries</option>
                  </select>
                </div>
              </div>

              <div className="space-y-1.5">
                <label className="block text-xs font-semibold uppercase tracking-wider text-neutral-700">
                  Your Message *
                </label>
                <textarea
                  rows={5}
                  required
                  placeholder="Tell us how we can help..."
                  value={formData.message}
                  onChange={(e) => setFormData({ ...formData, message: e.target.value })}
                  className="w-full p-4 text-xs text-neutral-900 bg-white border border-neutral-300 rounded-xl focus:outline-none focus:ring-2 focus:ring-neutral-900"
                />
              </div>

              <Button type="submit" isLoading={isSubmitting} className="w-full h-12 rounded-full font-bold text-xs mt-2">
                Submit Inquiry
              </Button>
            </form>
          )}
        </div>

        {/* Studio Info & Quick Assistance */}
        <div className="lg:col-span-5 space-y-6">
          <div className="p-8 rounded-3xl bg-neutral-50 border border-neutral-200/80 space-y-6">
            <h3 className="text-base font-bold text-neutral-900">Direct Contact Information</h3>
            <div className="space-y-4 text-xs text-neutral-600">
              <div className="flex items-start gap-3">
                <Mail className="w-4 h-4 text-neutral-900 mt-0.5 shrink-0" />
                <div>
                  <p className="font-bold text-neutral-900">Email Inquiries</p>
                  <p>{APP_CONFIG.supportEmail}</p>
                </div>
              </div>

              <div className="flex items-start gap-3">
                <Phone className="w-4 h-4 text-neutral-900 mt-0.5 shrink-0" />
                <div>
                  <p className="font-bold text-neutral-900">Customer Hotline</p>
                  <p>{APP_CONFIG.supportPhone}</p>
                </div>
              </div>

              <div className="flex items-start gap-3">
                <Clock className="w-4 h-4 text-neutral-900 mt-0.5 shrink-0" />
                <div>
                  <p className="font-bold text-neutral-900">Concierge Hours</p>
                  <p>Monday — Friday: 8 AM — 8 PM EST</p>
                  <p>Saturday — Sunday: 9 AM — 5 PM EST</p>
                </div>
              </div>

              <div className="flex items-start gap-3">
                <MapPin className="w-4 h-4 text-neutral-900 mt-0.5 shrink-0" />
                <div>
                  <p className="font-bold text-neutral-900">Design Studio & Flagship</p>
                  <p>{APP_CONFIG.address}</p>
                </div>
              </div>
            </div>
          </div>
        </div>
      </div>
    </div>
  );
}

