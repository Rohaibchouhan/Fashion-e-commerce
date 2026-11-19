import { ApiClient } from "./api-client";
import { BackendCart, Coupon } from "@/types/cart";
import { VALID_COUPONS } from "@/constants";

export class CartService {
  /**
   * Retrieve current shopping cart from Laravel REST API.
   */
  public static async getCart(): Promise<BackendCart | null> {
    try {
      const res = await ApiClient.get<BackendCart>("/cart");
      return res.data || null;
    } catch (error) {
      console.warn("[CartService.getCart] Could not fetch server cart:", error);
      return null;
    }
  }

  /**
   * Add item to cart with authoritative backend pricing and inventory validation.
   */
  public static async addItem(params: {
    productId: string | number;
    variantId?: string | number | null;
    quantity: number;
    size?: string;
    color?: string;
  }): Promise<BackendCart> {
    const payload = {
      product_id: Number(params.productId),
      variant_id: params.variantId ? Number(params.variantId) : null,
      quantity: params.quantity,
      size: params.size,
      color: params.color,
    };

    const res = await ApiClient.post<BackendCart>("/cart/items", payload);
    return res.data;
  }

  /**
   * Update item quantity in the backend cart.
   */
  public static async updateQuantity(itemId: string | number, quantity: number): Promise<BackendCart> {
    const res = await ApiClient.put<BackendCart>(`/cart/items/${itemId}`, { quantity });
    return res.data;
  }

  /**
   * Remove item from backend cart.
   */
  public static async removeItem(itemId: string | number): Promise<BackendCart> {
    const res = await ApiClient.delete<BackendCart>(`/cart/items/${itemId}`);
    return res.data;
  }

  /**
   * Clear all items from cart.
   */
  public static async clearCart(): Promise<BackendCart> {
    const res = await ApiClient.delete<BackendCart>("/cart");
    return res.data;
  }

  /**
   * Apply coupon code.
   */
  public static async applyCoupon(code: string): Promise<{ coupon: Coupon; cart: BackendCart }> {
    try {
      const res = await ApiClient.post<BackendCart>("/cart/coupon", { code });
      const cart = res.data;
      return {
        coupon: {
          code: cart.couponCode || code.toUpperCase(),
          discountType: "percentage",
          value: cart.discount,
          description: res.message || `Promo code applied successfully.`,
        },
        cart,
      };
    } catch (error: any) {
      // If backend responded with an authoritative validation error, propagate it directly
      if (!error?.isNetworkError && error?.status && error?.status < 500) {
        throw new Error(error?.message || "Invalid coupon code.");
      }

      // Offline fallback check against static coupons ONLY if API is unreachable
      const staticFound = VALID_COUPONS.find((c) => c.code.toUpperCase() === code.toUpperCase().trim());
      if (staticFound) {
        const currentCart = (await this.getCart()) as BackendCart;
        const currentSubtotal = currentCart?.subtotal || 0;
        if (staticFound.minOrderAmount && currentSubtotal < staticFound.minOrderAmount) {
          throw new Error(
            `Coupon '${staticFound.code}' requires a minimum cart subtotal of $${staticFound.minOrderAmount.toFixed(
              2
            )} (current subtotal is $${currentSubtotal.toFixed(2)}).`
          );
        }
        return {
          coupon: staticFound,
          cart: currentCart,
        };
      }
      throw new Error(error?.message || "Invalid coupon code.");
    }
  }

  /**
   * Remove coupon from cart.
   */
  public static async removeCoupon(): Promise<BackendCart> {
    const res = await ApiClient.delete<BackendCart>("/cart/coupon");
    return res.data;
  }

  /**
   * Legacy helper retained for local validation fallback.
   */
  public static async validateCoupon(code: string): Promise<Coupon> {
    const res = await this.applyCoupon(code);
    return res.coupon;
  }
}
