import { OrderStatus } from "@/types/order";
import { Notification } from "@/types/notification";

type EventCallback<T = any> = (data: T) => void;

export interface OrderStatusChangedEvent {
  orderId: string;
  orderNumber: string;
  oldStatus: OrderStatus;
  newStatus: OrderStatus;
  timestamp: string;
  location?: string;
  message?: string;
}

export interface StockUpdatedEvent {
  productId: string;
  stockCount: number;
  inStock: boolean;
}

export class RealtimeService {
  private static instance: RealtimeService;
  private listeners: Map<string, Set<EventCallback>> = new Map();
  private isConnected: boolean = false;

  private constructor() {
    this.init();
  }

  public static getInstance(): RealtimeService {
    if (!RealtimeService.instance) {
      RealtimeService.instance = new RealtimeService();
    }
    return RealtimeService.instance;
  }

  private init() {
    this.isConnected = true;
    // In production with Laravel Reverb or Pusher:
    // window.Echo = new Echo({ broadcaster: 'reverb', key: process.env.NEXT_PUBLIC_REVERB_APP_KEY, ... })
  }

  public subscribe<T = any>(channel: string, event: string, callback: EventCallback<T>): () => void {
    const key = `${channel}:${event}`;
    if (!this.listeners.has(key)) {
      this.listeners.set(key, new Set());
    }
    this.listeners.get(key)!.add(callback);

    // Return cleanup function
    return () => {
      this.listeners.get(key)?.delete(callback);
    };
  }

  public emit<T = any>(channel: string, event: string, data: T) {
    const key = `${channel}:${event}`;
    if (this.listeners.has(key)) {
      this.listeners.get(key)!.forEach((cb) => {
        try {
          cb(data);
        } catch (e) {
          console.error("Error in realtime event handler", e);
        }
      });
    }
  }

  // Helper for simulating real-time Laravel backend pusher / reverb events
  public simulateOrderStatusChange(
    orderId: string,
    orderNumber: string,
    oldStatus: OrderStatus,
    newStatus: OrderStatus,
    location?: string
  ) {
    const eventData: OrderStatusChangedEvent = {
      orderId,
      orderNumber,
      oldStatus,
      newStatus,
      timestamp: new Date().toISOString(),
      location: location || "Regional Sort Facility",
      message: `Order #${orderNumber} transitioned from ${oldStatus} to ${newStatus}.`,
    };

    this.emit("orders", "order:status_changed", eventData);

    // Also emit a notification event
    const newNotification: Notification = {
      id: `notif-${Date.now()}`,
      type: "order_update",
      title: `Order #${orderNumber} ${newStatus.toUpperCase().replace("_", " ")}`,
      message: `Your package status is now ${newStatus.replace("_", " ")}.`,
      timestamp: new Date().toISOString(),
      read: false,
      link: `/account/orders/${orderId}/track`,
      data: {
        orderId,
        orderNumber,
        status: newStatus,
      },
    };

    this.emit("user", "notification:new", newNotification);
  }
}

export const realtime = RealtimeService.getInstance();

