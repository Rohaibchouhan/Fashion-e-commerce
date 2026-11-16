"use client";

import React from "react";
import { AppQueryProvider } from "./query-provider";
import { MiniCartDrawer } from "@/components/cart/mini-cart-drawer";
import { SearchModal } from "@/components/layout/search-modal";
import { MobileNavDrawer } from "@/components/layout/mobile-nav";
import { QuickViewModal } from "@/components/product/quick-view-modal";
import { ToastContainer } from "@/components/ui/toast";
import { RealtimeSimulator } from "@/components/ui/realtime-simulator";
import { MobileBottomBar } from "@/components/layout/mobile-bottom-bar";
import { useRealtime } from "@/hooks/use-realtime";

function RealtimeListener() {
  useRealtime();
  return null;
}

export function ClientProviders({ children }: { children: React.ReactNode }) {
  return (
    <AppQueryProvider>
      <RealtimeListener />
      {children}
      <MiniCartDrawer />
      <SearchModal />
      <MobileNavDrawer />
      <QuickViewModal />
      <ToastContainer />
      <RealtimeSimulator />
      <MobileBottomBar />
    </AppQueryProvider>
  );
}

