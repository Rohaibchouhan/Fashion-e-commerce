import { Order, OrderStatus } from "@/types/order";
import { apiClient } from "./api-client";

export class OrdersService {
  static async getAll(statusFilter?: string): Promise<Order[]> {
    try {
      const params: Record<string, string> = {};
      if (statusFilter && statusFilter !== "all") {
        params.status = statusFilter;
      }
      const res = await apiClient.get<Order[]>("/orders", params);
      return Array.isArray(res.data) ? res.data : [];
    } catch {
      return [];
    }
  }

  static async getById(id: string): Promise<Order | null> {
    try {
      const res = await apiClient.get<Order>(`/orders/${encodeURIComponent(id)}`);
      return res.data || null;
    } catch {
      return null;
    }
  }

  static async track(orderNumber: string, emailOrZip?: string): Promise<Order | null> {
    try {
      const res = await apiClient.post<Order>("/orders/track", {
        order_number: orderNumber.trim(),
        email_or_zip: (emailOrZip || "").trim(),
      });
      return res.data || null;
    } catch {
      return null;
    }
  }

  static async cancelOrder(orderNumber: string, reason?: string): Promise<Order | null> {
    try {
      const res = await apiClient.post<Order>(`/orders/${encodeURIComponent(orderNumber)}/cancel`, {
        reason: reason || "Customer requested cancellation",
      });
      return res.data || null;
    } catch {
      return null;
    }
  }

  static async updateOrderStatus(
    orderId: string,
    newStatus: OrderStatus,
    location?: string
  ): Promise<void> {
    // Realtime update hook
  }
}
