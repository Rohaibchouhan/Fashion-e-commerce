import { create } from "zustand";
import { persist, createJSONStorage } from "zustand/middleware";
import { CartItem, Coupon, BackendCart } from "@/types/cart";
import { APP_CONFIG } from "@/constants";
import { CartService } from "@/services/cart.service";

interface CartStore {
  items: CartItem[];
  isOpen: boolean;
  coupon: Coupon | null;
  orderNotes: string;
  isLoading: boolean;
  serverSubtotal?: number;
  serverDiscount?: number;
  serverShipping?: number;
  serverTax?: number;
  serverTotal?: number;

  // Actions
  addItem: (item: Omit<CartItem, "id"> & { id?: string }) => Promise<void>;
  removeItem: (id: string) => Promise<void>;
  updateQuantity: (id: string, quantity: number) => Promise<void>;
  clearCart: () => Promise<void>;
  applyCoupon: (coupon: Coupon) => void;
  removeCoupon: () => Promise<void>;
  setOrderNotes: (notes: string) => void;
  openCart: () => void;
  closeCart: () => void;
  toggleCart: () => void;
  syncCart: () => Promise<void>;
  applyBackendCart: (backendCart: BackendCart) => void;

  // Computed values
  getSubtotal: () => number;
  getDiscount: () => number;
  getShipping: () => number;
  getTax: () => number;
  getTotal: () => number;
  getItemCount: () => number;
}

interface ItemUpdateQueue {
  targetQuantity: number;
  isProcessing: boolean;
  version: number;
  removed: boolean;
}

const itemQueues = new Map<string, ItemUpdateQueue>();

export const useCartStore = create<CartStore>()(
  persist(
    (set, get) => ({
      items: [],
      isOpen: false,
      coupon: null,
      orderNotes: "",
      isLoading: false,

      syncCart: async () => {
        try {
          const backendCart = await CartService.getCart();
          if (backendCart) {
            get().applyBackendCart(backendCart);
          }
        } catch (err) {
          console.warn("[useCartStore.syncCart] Sync failed:", err);
        }
      },

      applyBackendCart: (backendCart: BackendCart) => {
        const mappedItems: CartItem[] = backendCart.items.map((item) => {
          const idStr = String(item.id);
          const activeQueue = itemQueues.get(idStr);
          // If this item has an in-flight quantity update in progress, preserve customer's desired target quantity
          const effectiveQuantity = (activeQueue && activeQueue.isProcessing && !activeQueue.removed)
            ? activeQueue.targetQuantity
            : item.quantity;

          return {
            id: idStr,
            productId: String(item.productId),
            variantId: item.variantId ? String(item.variantId) : null,
            sku: item.sku,
            name: item.name,
            slug: item.slug,
            price: item.price,
            quantity: effectiveQuantity,
            maxStock: item.maxStock,
            selectedColor: item.selectedColor,
            selectedSize: item.selectedSize,
            image: item.image,
            subtotal: item.subtotal,
          };
        });

        set({
          items: mappedItems,
          serverSubtotal: backendCart.subtotal,
          serverDiscount: backendCart.discount,
          serverShipping: backendCart.shipping,
          serverTax: backendCart.tax,
          serverTotal: backendCart.total,
          coupon: backendCart.couponCode
            ? {
                code: backendCart.couponCode,
                discountType: "percentage",
                value: backendCart.discount,
                description: `Coupon ${backendCart.couponCode} applied`,
              }
            : null,
        });
      },

      addItem: async (newItem: Omit<CartItem, "id"> & { id?: string }) => {
        // Optimistic local add
        const compositeId = newItem.id || `${newItem.productId}-${newItem.selectedColor}-${newItem.selectedSize}`;
        set((state) => {
          const existingIndex = state.items.findIndex(
            (item) => item.id === compositeId || (item.productId === newItem.productId && item.variantId === newItem.variantId)
          );
          if (existingIndex > -1) {
            const updated = [...state.items];
            const item = updated[existingIndex];
            const newQty = Math.min(item.quantity + newItem.quantity, item.maxStock || 99);
            updated[existingIndex] = { ...item, quantity: newQty };
            return { items: updated, isOpen: true };
          } else {
            return {
              items: [...state.items, { ...newItem, id: compositeId }],
              isOpen: true,
            };
          }
        });

        // Sync to backend
        try {
          const backendCart = await CartService.addItem({
            productId: newItem.productId,
            variantId: newItem.variantId,
            quantity: newItem.quantity,
            size: newItem.selectedSize,
            color: newItem.selectedColor,
          });
          if (backendCart) {
            (get() as any).applyBackendCart(backendCart);
          }
        } catch (err) {
          console.error("[useCartStore.addItem] Server error adding to cart:", err);
          throw err;
        }
      },

      removeItem: async (id: string) => {
        // Discard any pending or in-flight update loops for this item
        const queue = itemQueues.get(id);
        if (queue) {
          queue.removed = true;
          queue.version++;
        }

        // Remove locally
        set((state) => ({
          items: state.items.filter((item) => item.id !== id),
        }));

        // Remove on backend if numeric ID
        try {
          if (!isNaN(Number(id))) {
            const backendCart = await CartService.removeItem(id);
            if (backendCart) {
              get().applyBackendCart(backendCart);
            }
          }
        } catch (err) {
          console.warn("[useCartStore.removeItem] Backend error:", err);
        }
      },

      updateQuantity: async (id: string, quantity: number) => {
        if (quantity <= 0) {
          await get().removeItem(id);
          return;
        }

        // 1. Optimistic local update: latest customer-selected quantity wins immediately in UI
        set((state) => ({
          items: state.items.map((item) =>
            item.id === id ? { ...item, quantity: Math.min(quantity, item.maxStock || 99) } : item
          ),
        }));

        // 2. Register/update serialized queue entry for this item
        let queue = itemQueues.get(id);
        if (!queue) {
          queue = {
            targetQuantity: quantity,
            isProcessing: false,
            version: 0,
            removed: false,
          };
          itemQueues.set(id, queue);
        }

        queue.targetQuantity = quantity;
        queue.removed = false;
        queue.version++;

        // If a request is already actively processing for this item, the running loop
        // will pick up queue.targetQuantity when its current request resolves
        if (queue.isProcessing) {
          return;
        }

        queue.isProcessing = true;

        // 3. Serialized Worker Loop: drains updates until targetQuantity equals sent quantity
        try {
          while (true) {
            if (queue.removed) break;

            const qtyToSend = queue.targetQuantity;
            const reqVersion = queue.version;

            if (isNaN(Number(id))) {
              break;
            }

            try {
              const backendCart = await CartService.updateQuantity(id, qtyToSend);

              // Only apply backend totals if no newer update was enqueued and item was not removed
              if (!queue.removed && queue.version === reqVersion && backendCart) {
                get().applyBackendCart(backendCart);
              }
            } catch (err: any) {
              console.warn(`[useCartStore.updateQuantity] Backend error updating item ${id}:`, err);
              // On error (e.g. stock exceeded), restore authoritative server state
              try {
                const freshCart = await CartService.getCart();
                if (freshCart) {
                  get().applyBackendCart(freshCart);
                }
              } catch {}
              break;
            }

            // If targetQuantity did not change while in-flight, queue is fully settled
            if (queue.targetQuantity === qtyToSend || queue.removed) {
              break;
            }
          }
        } finally {
          queue.isProcessing = false;
          if (queue.removed) {
            itemQueues.delete(id);
          }
        }
      },

      clearCart: async () => {
        itemQueues.forEach((q) => {
          q.removed = true;
          q.version++;
        });
        itemQueues.clear();
        set({ items: [], coupon: null, serverSubtotal: 0, serverDiscount: 0, serverShipping: 0, serverTax: 0, serverTotal: 0 });
        try {
          await CartService.clearCart();
        } catch (err) {
          console.warn("[useCartStore.clearCart] Backend clear error:", err);
        }
      },

      applyCoupon: (coupon: Coupon) => set({ coupon }),

      removeCoupon: async () => {
        set({ coupon: null });
        try {
          const backendCart = await CartService.removeCoupon();
          if (backendCart) {
            get().applyBackendCart(backendCart);
          }
        } catch (err) {
          console.warn("[useCartStore.removeCoupon] Error:", err);
        }
      },

      setOrderNotes: (notes: string) => set({ orderNotes: notes }),

      openCart: () => set({ isOpen: true }),
      closeCart: () => set({ isOpen: false }),
      toggleCart: () => set((state) => ({ isOpen: !state.isOpen })),

      getSubtotal: () => {
        const state = get();
        if (state.serverSubtotal !== undefined && state.serverSubtotal > 0) {
          return state.serverSubtotal;
        }
        return state.items.reduce((sum, item) => sum + item.price * item.quantity, 0);
      },

      getDiscount: () => {
        const state = get();
        if (state.serverDiscount !== undefined && state.serverDiscount > 0) {
          return state.serverDiscount;
        }
        const subtotal = state.getSubtotal();
        const coupon = state.coupon;
        if (!coupon) return 0;
        if (coupon.minOrderAmount && subtotal < coupon.minOrderAmount) return 0;

        if (coupon.discountType === "percentage") {
          return (subtotal * coupon.value) / 100;
        }
        return Math.min(coupon.value, subtotal);
      },

      getShipping: () => {
        const state = get();
        if (state.serverShipping !== undefined) {
          return state.serverShipping;
        }
        const subtotal = state.getSubtotal();
        if (subtotal === 0) return 0;
        return subtotal >= APP_CONFIG.freeShippingThreshold ? 0 : APP_CONFIG.defaultShippingCost;
      },

      getTax: () => {
        const state = get();
        if (state.serverTax !== undefined) {
          return state.serverTax;
        }
        const subtotal = state.getSubtotal();
        const discount = state.getDiscount();
        return roundPrice((subtotal - discount) * 0.08);
      },

      getTotal: () => {
        const state = get();
        if (state.serverTotal !== undefined && state.serverTotal > 0) {
          return state.serverTotal;
        }
        const subtotal = state.getSubtotal();
        const discount = state.getDiscount();
        const shipping = state.getShipping();
        const tax = state.getTax();
        return Math.max(0, subtotal - discount + shipping + tax);
      },

      getItemCount: () => {
        return get().items.reduce((count, item) => count + item.quantity, 0);
      },
    } as any),
    {
      name: "wearday_cart_storage",
      storage: createJSONStorage(() => localStorage),
      partialize: (state) => ({ items: state.items, coupon: state.coupon, orderNotes: state.orderNotes }),
    }
  )
);

function roundPrice(val: number): number {
  return Math.round((val + Number.EPSILON) * 100) / 100;
}
