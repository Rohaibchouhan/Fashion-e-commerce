"use client";

import { useEffect } from "react";
import { useQueryClient } from "@tanstack/react-query";
import { realtime, OrderStatusChangedEvent } from "@/services/realtime.service";
import { useNotificationStore } from "@/stores/notification-store";
import { Notification } from "@/types/notification";
import { OrdersService } from "@/services/orders.service";

export function useRealtime() {
  const queryClient = useQueryClient();
  const addNotification = useNotificationStore((s) => s.addNotification);

  useEffect(() => {
    // 1. Listen for Order Status Changes
    const unsubscribeOrders = realtime.subscribe<OrderStatusChangedEvent>(
      "orders",
      "order:status_changed",
      async (data) => {
        // Optimistically mutate cache without reloading page
        await OrdersService.updateOrderStatus(data.orderId, data.newStatus, data.location);

        // Targeted cache invalidation
        queryClient.invalidateQueries({ queryKey: ["orders"] });
        queryClient.invalidateQueries({ queryKey: ["order", data.orderId] });
        queryClient.invalidateQueries({ queryKey: ["order-stats"] });
      }
    );

    // 2. Listen for Realtime Push Notifications
    const unsubscribeNotifications = realtime.subscribe<Notification>(
      "user",
      "notification:new",
      (notification) => {
        addNotification(notification);
        queryClient.invalidateQueries({ queryKey: ["notifications"] });
      }
    );

    return () => {
      unsubscribeOrders();
      unsubscribeNotifications();
    };
  }, [queryClient, addNotification]);

  return {
    simulateOrderStatusChange: (
      orderId: string,
      orderNumber: string,
      oldStatus: any,
      newStatus: any,
      location?: string
    ) => {
      realtime.simulateOrderStatusChange(orderId, orderNumber, oldStatus, newStatus, location);
    },
  };
}

