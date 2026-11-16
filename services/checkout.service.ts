import { Address } from "@/types/auth";
import { CartItem } from "@/types/cart";
import { Order } from "@/types/order";
import { MOCK_ORDERS } from "@/lib/mock-data/orders";

export interface CreateOrderPayload {
  items: CartItem[];
  shippingAddress: Address;
  billingAddress: Address;
  shippingMethodId: string;
  paymentMethod: {
    type: "card" | "apple_pay" | "paypal";
    cardNumber?: string;
    expDate?: string;
    cvv?: string;
  };
  couponCode?: string;
  subtotal: number;
  discount: number;
  shipping: number;
  tax: number;
  total: number;
  autoRegister?: {
    email: string;
    name: string;
  };
}

export class CheckoutService {
  static async processOrder(payload: CreateOrderPayload): Promise<Order> {
    await new Promise((r) => setTimeout(r, 600));

    const newOrderNumber = `WD-${Math.floor(10000 + Math.random() * 90000)}`;
    const newOrderId = `ord-${Date.now().toString().slice(-6)}`;

    const newOrder: Order = {
      id: newOrderId,
      orderNumber: newOrderNumber,
      date: new Date().toISOString(),
      status: "placed",
      paymentStatus: "paid",
      fulfillmentStatus: "unfulfilled",
      trackingNumber: "Pending Allocation",
      carrier: payload.shippingMethodId === "express" ? "FedEx Express" : "Standard Ground",
      estimatedDelivery: new Date(Date.now() + 5 * 24 * 60 * 60 * 1000).toISOString().split("T")[0],
      items: payload.items.map((item) => ({
        id: `item-${Math.random().toString(36).substring(2, 7)}`,
        productId: item.productId,
        slug: item.slug,
        name: item.name,
        price: item.price,
        quantity: item.quantity,
        color: item.selectedColor,
        size: item.selectedSize,
        image: item.image,
      })),
      subtotal: payload.subtotal,
      discount: payload.discount,
      shipping: payload.shipping,
      tax: payload.tax,
      total: payload.total,
      shippingAddress: payload.shippingAddress,
      billingAddress: payload.billingAddress,
      paymentMethod:
        payload.paymentMethod.type === "apple_pay"
          ? "Apple Pay"
          : payload.paymentMethod.type === "paypal"
          ? "PayPal"
          : `Visa ending in ${payload.paymentMethod.cardNumber?.slice(-4) || "8821"}`,
      paymentLast4: payload.paymentMethod.cardNumber?.slice(-4) || "8821",
      timeline: [
        {
          status: "placed",
          label: "Order Placed",
          description: "Thank you for your order. We are validating payment.",
          timestamp: new Date().toISOString(),
          completed: true,
          current: true,
        },
        { status: "confirmed", label: "Order Confirmed", description: "Pending confirmation", timestamp: "", completed: false },
        { status: "processing", label: "Processing", description: "Pending processing", timestamp: "", completed: false },
        { status: "packed", label: "Packed", description: "Pending packing", timestamp: "", completed: false },
        { status: "dispatched", label: "Dispatched", description: "Pending dispatch", timestamp: "", completed: false },
        { status: "in_transit", label: "In Transit", description: "Pending transit", timestamp: "", completed: false },
        { status: "out_for_delivery", label: "Out for Delivery", description: "Pending delivery", timestamp: "", completed: false },
        { status: "delivered", label: "Delivered", description: "Pending receipt", timestamp: "", completed: false },
      ],
    };

    // Prepend to mock orders list so it appears in Dashboard and Tracking immediately
    MOCK_ORDERS.unshift(newOrder);

    return newOrder;
  }
}

