import { ReturnRequest, ReturnItem } from "@/types/return";
import { apiClient } from "./api-client";

export interface CreateReturnPayload {
  orderId: string | number;
  orderNumber?: string;
  items: Array<{
    order_item_id: string | number;
    quantity: number;
    reason: string;
    comment?: string;
  }>;
  resolution: "refund" | "exchange";
  exchangeDetails?: string;
}

export class ReturnsService {
  static async getAll(): Promise<ReturnRequest[]> {
    try {
      const res = await apiClient.get<ReturnRequest[]>("/returns");
      return Array.isArray(res.data) ? res.data : [];
    } catch {
      return [];
    }
  }

  static async getById(id: string): Promise<ReturnRequest | null> {
    try {
      const res = await apiClient.get<ReturnRequest>(`/returns/${encodeURIComponent(id)}`);
      return res.data || null;
    } catch {
      return null;
    }
  }

  static async createReturn(payload: CreateReturnPayload): Promise<ReturnRequest> {
    const res = await apiClient.post<ReturnRequest>("/returns", {
      order_id: payload.orderId,
      resolution: payload.resolution,
      exchange_details: payload.exchangeDetails,
      items: payload.items,
    });
    return res.data;
  }
}
