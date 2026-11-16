export interface CartItem {
  id: string; // unique item id from database or fallback composite
  productId: string;
  variantId?: string | null;
  sku?: string;
  slug: string;
  name: string;
  price: number;
  originalPrice?: number;
  image: string;
  selectedColor: string;
  selectedSize: string;
  quantity: number;
  maxStock: number;
  subtotal?: number;
}

export interface Coupon {
  code: string;
  discountType: "percentage" | "fixed";
  value: number; // e.g. 15 for 15% or 20 for $20
  minOrderAmount?: number;
  description: string;
}

export interface ShippingMethod {
  id: string;
  name: string;
  description: string;
  price: number;
  estimatedDays: string;
}

export interface BackendCartItem {
  id: string;
  productId: string;
  variantId: string | null;
  sku: string;
  name: string;
  slug: string;
  price: number;
  quantity: number;
  maxStock: number;
  selectedColor: string;
  selectedSize: string;
  image: string;
  subtotal: number;
}

export interface BackendCart {
  id: string;
  sessionId: string;
  itemsCount: number;
  items: BackendCartItem[];
  couponCode: string | null;
  subtotal: number;
  discount: number;
  shipping: number;
  tax: number;
  total: number;
}
