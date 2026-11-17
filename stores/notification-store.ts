import { create } from "zustand";
import { persist, createJSONStorage } from "zustand/middleware";
import { Notification } from "@/types/notification";

interface NotificationStore {
  notifications: Notification[];
  isOpen: boolean;

  // Actions
  setNotifications: (notifs: Notification[]) => void;
  addNotification: (notif: Notification) => void;
  markAsRead: (id: string) => void;
  markAllAsRead: () => void;
  toggleDropdown: () => void;
  closeDropdown: () => void;
  getUnreadCount: () => number;
}

export const useNotificationStore = create<NotificationStore>()(
  persist(
    (set, get) => ({
      notifications: [],
      isOpen: false,

      setNotifications: (notifications) => set({ notifications }),

      addNotification: (notif) => {
        set((state) => ({
          notifications: [notif, ...state.notifications],
        }));
      },

      markAsRead: (id) => {
        set((state) => ({
          notifications: state.notifications.map((n) =>
            n.id === id ? { ...n, read: true } : n
          ),
        }));
      },

      markAllAsRead: () => {
        set((state) => ({
          notifications: state.notifications.map((n) => ({ ...n, read: true })),
        }));
      },

      toggleDropdown: () => set((state) => ({ isOpen: !state.isOpen })),
      closeDropdown: () => set({ isOpen: false }),

      getUnreadCount: () => {
        return get().notifications.filter((n) => !n.read).length;
      },
    }),
    {
      name: "wearday_notifications_storage",
      storage: createJSONStorage(() => localStorage),
    }
  )
);

