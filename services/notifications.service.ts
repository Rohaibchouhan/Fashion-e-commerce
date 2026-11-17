import { Notification } from "@/types/notification";
import { apiClient } from "./api-client";

export class NotificationsService {
  static async getAll(): Promise<Notification[]> {
    try {
      const res = await apiClient.get<Notification[]>("/notifications");
      return Array.isArray(res.data) ? res.data : [];
    } catch {
      return [];
    }
  }

  static async markAsRead(id: string): Promise<void> {
    try {
      await apiClient.post(`/notifications/${id}/read`);
    } catch {
      // Ignore network errors on background notification ack
    }
  }

  static async markAllAsRead(): Promise<void> {
    try {
      await apiClient.post("/notifications/read-all");
    } catch {
      // Ignore
    }
  }
}
