import { create } from "zustand";
import { persist, createJSONStorage } from "zustand/middleware";

interface WishlistStore {
  items: string[]; // Product IDs
  toggleItem: (productId: string) => boolean; // returns true if added, false if removed
  hasItem: (productId: string) => boolean;
  removeItem: (productId: string) => void;
  clearWishlist: () => void;
}

export const useWishlistStore = create<WishlistStore>()(
  persist(
    (set, get) => ({
      items: ["prod-1", "prod-4", "prod-9"],

      toggleItem: (productId) => {
        const current = get().items;
        const exists = current.includes(productId);
        if (exists) {
          set({ items: current.filter((id) => id !== productId) });
          return false;
        } else {
          set({ items: [...current, productId] });
          return true;
        }
      },

      hasItem: (productId) => {
        return get().items.includes(productId);
      },

      removeItem: (productId) => {
        set((state) => ({ items: state.items.filter((id) => id !== productId) }));
      },

      clearWishlist: () => set({ items: [] }),
    }),
    {
      name: "wearday_wishlist_storage",
      storage: createJSONStorage(() => localStorage),
    }
  )
);

