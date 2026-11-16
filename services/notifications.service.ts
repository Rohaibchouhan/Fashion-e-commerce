import { Notification } from "@/types/notification";
import { MOCK_NOTIFICATIONS } from "@/lib/mock-data/notifications";

export class NotificationsService {
  static async getAll(): Promise<Notification[]> {
    await new Promise((r) => setTimeout(r, 80));
    return [...MOCK_NOTIFICATIONS];
  }

  static async markAsRead(id: string): Promise<void> {
    const notif = MOCK_NOTIFICATIONS.find((n) => n.id === id);
    if (notif) notif.read = true;
  }

  static async markAllAsRead(): Promise<void> {
    MOCK_NOTIFICATIONS.forEach((n) => (n.read = true));
  }
}

