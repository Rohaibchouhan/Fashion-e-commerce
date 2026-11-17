import { create } from "zustand";
import { persist, createJSONStorage } from "zustand/middleware";
import { User, Address } from "@/types/auth";
import { ApiClient } from "@/services/api-client";
import { AuthService } from "@/services/auth.service";

interface AuthStore {
  user: User | null;
  isAuthenticated: boolean;
  token: string | null;

  // Actions
  setAuth: (user: User, token: string) => void;
  login: (userOrEmail: User | string, tokenOrName?: string) => void;
  register: (name: string, email: string) => void;
  logout: () => void;
  updateUser: (data: Partial<User>) => void;
  refreshUser: () => Promise<void>;
  addAddress: (address: Address) => void;
  removeAddress: (id: string) => void;
  setDefaultAddress: (id: string) => void;
}

export const useAuthStore = create<AuthStore>()(
  persist(
    (set, get) => ({
      user: null,
      isAuthenticated: false,
      token: null,

      setAuth: (user: User, token: string) => {
        ApiClient.setAuthToken(token);
        set({ user, isAuthenticated: true, token });
      },

      login: (userOrEmail: User | string, tokenOrName?: string) => {
        if (typeof userOrEmail === "object") {
          const token = tokenOrName || "";
          ApiClient.setAuthToken(token);
          set({ user: userOrEmail, isAuthenticated: true, token });
        }
      },

      register: (name: string, email: string) => {
        // Registered users receive official credentials from API responses
      },

      logout: () => {
        AuthService.logout().catch(() => {});
        ApiClient.setAuthToken(null);
        ApiClient.rotateSessionId();
        set({ user: null, isAuthenticated: false, token: null });
      },

      updateUser: (data) => {
        const current = get().user;
        if (current) {
          set({ user: { ...current, ...data } });
        }
      },

      refreshUser: async () => {
        try {
          const user = await AuthService.getCurrentUser();
          set({ user, isAuthenticated: true });
        } catch {
          // Token invalid or expired
          get().logout();
        }
      },

      addAddress: (address: Address) => {
        const current = get().user;
        if (!current) return;
        const addresses = [...(current.addresses || [])];
        if (address.isDefaultShipping) {
          addresses.forEach((a) => (a.isDefaultShipping = false));
        }
        addresses.push(address);
        set({ user: { ...current, addresses } });
      },

      removeAddress: (id: string) => {
        const current = get().user;
        if (!current) return;
        set({
          user: {
            ...current,
            addresses: (current.addresses || []).filter((a) => a.id !== id),
          },
        });
      },

      setDefaultAddress: (id: string) => {
        const current = get().user;
        if (!current) return;
        const updated = (current.addresses || []).map((a) => ({
          ...a,
          isDefaultShipping: a.id === id,
        }));
        set({ user: { ...current, addresses: updated } });
      },
    }),
    {
      name: "wearday_auth_storage",
      storage: createJSONStorage(() => localStorage),
      onRehydrateStorage: () => (state) => {
        if (state?.token) {
          ApiClient.setAuthToken(state.token);
        }
      },
    }
  )
);
