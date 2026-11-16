import React from "react";
import Image from "next/image";
import Link from "next/link";
import { Breadcrumb } from "@/components/ui/breadcrumb";
import { Leaf, Award, ShieldCheck, HeartHandshake, Sparkles, ArrowRight } from "lucide-react";
import { APP_CONFIG } from "@/constants";

export default function AboutPage() {
  return (
    <div className="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8 py-8 sm:py-14 space-y-16">
      <Breadcrumb items={[{ label: "Home", href: "/" }, { label: "About Us" }]} />

      {/* Hero Story Banner */}
      <div className="grid grid-cols-1 lg:grid-cols-2 gap-10 items-center">
        <div className="space-y-5">
          <span className="text-[11px] font-bold uppercase tracking-widest text-neutral-400">
            Our Brand Philosophy
          </span>
          <h1 className="text-3xl sm:text-5xl font-black text-neutral-900 tracking-tight leading-tight">
            We Believe Great Clothes Make Everyday Living Effortless.
          </h1>
          <p className="text-sm text-neutral-600 leading-relaxed">
            Founded with a singular conviction: daily dressing should neither require agonizing over trends nor compromising on tactile comfort. We build understated, high-grade essentials engineered to be worn, loved, and washed on repeat.
          </p>
          <div className="pt-2">
            <Link
              href="/shop"
              className="inline-flex items-center gap-2 px-8 py-3.5 bg-neutral-900 text-white rounded-full font-bold text-xs hover:bg-neutral-800 transition-colors shadow-md"
            >
              <span>Explore The Collection</span>
              <ArrowRight className="w-4 h-4" />
            </Link>
          </div>
        </div>

        <div className="relative aspect-4/3 rounded-3xl overflow-hidden bg-neutral-100 shadow-md">
          <Image
            src="https://images.unsplash.com/photo-1558769132-cb1aea458c5e?w=1000&auto=format&fit=crop&q=80"
            alt="Craftsmanship and Fabric"
            fill
            className="object-cover"
          />
        </div>
      </div>

      {/* Core Values Pillars */}
      <div className="grid grid-cols-1 md:grid-cols-3 gap-8 pt-8 border-t border-neutral-200">
        <div className="p-8 rounded-3xl bg-neutral-50 border border-neutral-200/80 space-y-3">
          <div className="w-12 h-12 rounded-2xl bg-white shadow-xs flex items-center justify-center text-neutral-900">
            <Leaf className="w-6 h-6 text-emerald-600" />
          </div>
          <h3 className="text-base font-bold text-neutral-900">Single-Origin Fibers</h3>
          <p className="text-xs text-neutral-600 leading-relaxed">
            We exclusively partner with certified GOTS organic cotton farms and historical European flax mills that maintain rigorous soil health and fair wage standards.
          </p>
        </div>

        <div className="p-8 rounded-3xl bg-neutral-50 border border-neutral-200/80 space-y-3">
          <div className="w-12 h-12 rounded-2xl bg-white shadow-xs flex items-center justify-center text-neutral-900">
            <Award className="w-6 h-6 text-amber-600" />
          </div>
          <h3 className="text-base font-bold text-neutral-900">Heavyweight Durability</h3>
          <p className="text-xs text-neutral-600 leading-relaxed">
            Our 220 GSM tees and 400 GSM French terry fleece are preshrunk and tested against torque twisting to retain pristine structure wash after wash.
          </p>
        </div>

        <div className="p-8 rounded-3xl bg-neutral-50 border border-neutral-200/80 space-y-3">
          <div className="w-12 h-12 rounded-2xl bg-white shadow-xs flex items-center justify-center text-neutral-900">
            <HeartHandshake className="w-6 h-6 text-blue-600" />
          </div>
          <h3 className="text-base font-bold text-neutral-900">Transparent Pricing</h3>
          <p className="text-xs text-neutral-600 leading-relaxed">
            By avoiding bloated traditional wholesale markups and fleeting micro-trends, we pass supreme artisanal fabric quality directly to you at fair prices.
          </p>
        </div>
      </div>
    </div>
  );
}

