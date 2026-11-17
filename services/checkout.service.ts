import { Address } from "@/types/auth";
import { CartItem } from "@/types/cart";
import { Order } from "@/types/order";
import { apiClient } from "./api-client";
import { useAuthStore } from "@/stores/auth-store";

export interface CheckoutQuoteItem {
  productId: string;
  variantId: string | null;
  name: string;
  sku: string;
  slug: string;
  color?: string;
  size?: string;
  image?: string;
  unitPrice: number;
  quantity: number;
  subtotal: number;
  availableStock: number;
}

export interface CheckoutQuote {
  subtotal: number;
  discount: number;
  shipping: number;
  shippingMethodId: string;
  shippingMethodName: string;
  estimatedDays: number;
  tax: number;
  taxRate: number;
  total: number;
  couponCode?: string | null;
  items: CheckoutQuoteItem[];
}

export interface PreviewQuotePayload {
  items: CartItem[];
  shippingMethodId?: string;
  couponCode?: string;
  shippingAddress?: Partial<Address>;
}

export interface CreateOrderPayload {
  items: CartItem[];
  shippingAddressId?: string | number;
  shippingAddress?: Address;
  billingSameAsShipping?: boolean;
  billingAddressId?: string | number;
  billingAddress?: Address;
  shippingMethodId: string;
  paymentMethod: {
    type: "card" | "apple_pay" | "paypal";
    cardNumber?: string;
    expDate?: string;
    cvv?: string;
    nameOnCard?: string;
  };
  couponCode?: string;
  notes?: string;
  idempotencyKey?: string;
  autoRegister?: {
    email: string;
    name: string;
    phone?: string;
  };
}

export class CheckoutService {
  /**
   * Fetch an authoritative live quote from the Laravel backend.
   */
  static async getQuote(payload: PreviewQuotePayload): Promise<CheckoutQuote> {
    const formattedPayload = {
      items: payload.items.map((item) => ({
        productId: item.productId,
        variantId: item.variantId || null,
        product_id: item.productId,
        variant_id: item.variantId || null,
        quantity: item.quantity,
        color: item.selectedColor,
        size: item.selectedSize,
      })),
      shippingMethodId: payload.shippingMethodId || "standard",
      shipping_method_id: payload.shippingMethodId || "standard",
      couponCode: payload.couponCode || null,
      coupon_code: payload.couponCode || null,
      shippingAddress: payload.shippingAddress
        ? {
            firstName: payload.shippingAddress.firstName,
            lastName: payload.shippingAddress.lastName,
            street: payload.shippingAddress.street,
            city: payload.shippingAddress.city,
            state: payload.shippingAddress.state,
            zipCode: payload.shippingAddress.zipCode,
            country: payload.shippingAddress.country || "United States",
          }
        : undefined,
    };

    const res = await apiClient.post<CheckoutQuote>("/checkout/preview", formattedPayload);
    if (res.data) {
      return res.data;
    }

    throw new Error("Failed to calculate checkout quote.");
  }

  /**
   * Submit the order to Laravel with authoritative validation and inventory reservation.
   */
  static async processOrder(payload: CreateOrderPayload): Promise<Order> {
    // Generate unique idempotency key if not provided
    const idempotencyKey =
      payload.idempotencyKey ||
      (typeof crypto !== "undefined" && crypto.randomUUID
        ? crypto.randomUUID()
        : `idemp-${Date.now()}-${Math.random().toString(36).slice(2, 9)}`);

    const formattedPayload: Record<string, any> = {
      idempotency_key: idempotencyKey,
      items: payload.items.map((item) => ({
        productId: item.productId,
        variantId: item.variantId || null,
        product_id: item.productId,
        variant_id: item.variantId || null,
        quantity: item.quantity,
        color: item.selectedColor,
        size: item.selectedSize,
      })),
      shipping_method_id: payload.shippingMethodId,
      shippingMethodId: payload.shippingMethodId,
      payment_method: payload.paymentMethod.type,
      paymentMethod: payload.paymentMethod,
      coupon_code: payload.couponCode || null,
      couponCode: payload.couponCode || null,
      notes: payload.notes || null,
    };

    // Shipping address handling: either saved ID or full payload
    if (payload.shippingAddressId) {
      formattedPayload.shipping_address_id = Number(payload.shippingAddressId);
    } else if (payload.shippingAddress) {
      formattedPayload.shippingAddress = {
        firstName: payload.shippingAddress.firstName,
        lastName: payload.shippingAddress.lastName,
        first_name: payload.shippingAddress.firstName,
        last_name: payload.shippingAddress.lastName,
        street: payload.shippingAddress.street,
        apartment: payload.shippingAddress.apartment || "",
        city: payload.shippingAddress.city,
        state: payload.shippingAddress.state,
        zipCode: payload.shippingAddress.zipCode,
        postal_code: payload.shippingAddress.zipCode,
        country: payload.shippingAddress.country || "United States",
        phone: payload.shippingAddress.phone || "",
        email: (payload.shippingAddress as any).email || payload.autoRegister?.email || "",
      };
    }

    // Billing address handling
    formattedPayload.billing_same_as_shipping = payload.billingSameAsShipping ?? true;
    if (!formattedPayload.billing_same_as_shipping) {
      if (payload.billingAddressId) {
        formattedPayload.billing_address_id = Number(payload.billingAddressId);
      } else if (payload.billingAddress) {
        formattedPayload.billingAddress = {
          firstName: payload.billingAddress.firstName,
          lastName: payload.billingAddress.lastName,
          street: payload.billingAddress.street,
          apartment: payload.billingAddress.apartment || "",
          city: payload.billingAddress.city,
          state: payload.billingAddress.state,
          zipCode: payload.billingAddress.zipCode,
          country: payload.billingAddress.country || "United States",
          phone: payload.billingAddress.phone || "",
        };
      }
    }

    // Customer Auto-registration if unauthenticated
    if (payload.autoRegister) {
      formattedPayload.autoRegister = payload.autoRegister;
      formattedPayload.customer = {
        name: payload.autoRegister.name,
        email: payload.autoRegister.email,
        phone: payload.autoRegister.phone || "",
      };
    }

    try {
      const res = await apiClient.post<{ order: Order; token?: string; user?: any }>(
        "/checkout/process",
        formattedPayload,
        {
          headers: {
            "X-Idempotency-Key": idempotencyKey,
            "Idempotency-Key": idempotencyKey,
          },
        }
      );

      // If server created a new customer account or refreshed auth, sync with auth store
      if (res.data?.token && res.data?.user) {
        useAuthStore.getState().setAuth(res.data.user, res.data.token);
      }

      if (res.data?.order) {
        return res.data.order;
      }
    } catch (err: any) {
      console.warn("Backend checkout API error:", err?.message || err);
      throw err;
    }

    throw new Error("Failed to process order.");
  }
}
