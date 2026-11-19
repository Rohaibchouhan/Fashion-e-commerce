import { Address } from "./auth";

export type OrderStatus =
  | "placed"
  | "confirmed"
  | "processing"
  | "packed"
  | "dispatched"
  | "in_transit"
  | "out_for_delivery"
  | "delivered"
  | "cancelled";

export type PaymentStatus = "pending" | "authorized" | "paid" | "failed" | "refunded";
export type FulfillmentStatus = "unfulfilled" | "partially_fulfilled" | "fulfilled";

export interface TimelineEvent {
  status: OrderStatus;
  label: string;
  description: string;
  timestamp: string;
  completed: boolean;
  current?: boolean;
  location?: string;
}

export interface OrderItem {
  id: string;
  productId: string;
  variantId?: string | null;
  slug: string;
  name: string;
  sku?: string;
  price: number;
  quantity: number;
  color: string;
  size: string;
  image: string;
}

export interface Order {
  id: string;
  orderNumber: string;
  date: string;
  status: OrderStatus;
  paymentStatus: PaymentStatus;
  fulfillmentStatus: FulfillmentStatus;
  trackingNumber: string;
  carrier: string;
  trackingUrl?: string | null;
  estimatedDelivery: string;
  items: OrderItem[];
  subtotal: number;
  discount: number;
  shipping: number;
  tax: number;
  total: number;
  shippingAddress: Address;
  billingAddress: Address;
  paymentMethod: string;
  paymentLast4?: string;
  timeline: TimelineEvent[];
  notes?: string;
}

