import { Order, OrderStatus } from "@/types/order";
import { MOCK_ORDERS } from "@/lib/mock-data/orders";
import { ORDER_TIMELINE_STAGES, ORDER_STATUS_DETAILS } from "@/constants";

export class OrdersService {
  static async getAll(statusFilter?: string): Promise<Order[]> {
    await new Promise((r) => setTimeout(r, 120));
    if (!statusFilter || statusFilter === "all") {
      return [...MOCK_ORDERS];
    }
    return MOCK_ORDERS.filter((o) => o.status === statusFilter);
  }

  static async getById(id: string): Promise<Order | null> {
    await new Promise((r) => setTimeout(r, 100));
    const cleanId = id.trim().toLowerCase();
    return (
      MOCK_ORDERS.find(
        (o) => o.id.toLowerCase() === cleanId || o.orderNumber.toLowerCase() === cleanId
      ) || null
    );
  }

  static async track(orderNumber: string, emailOrZip?: string): Promise<Order | null> {
    await new Promise((r) => setTimeout(r, 200));
    const cleanNum = orderNumber.trim().toUpperCase();
    return (
      MOCK_ORDERS.find(
        (o) =>
          o.orderNumber.toUpperCase() === cleanNum ||
          o.id.toUpperCase() === cleanNum ||
          o.trackingNumber.toUpperCase() === cleanNum
      ) || null
    );
  }

  static async updateOrderStatus(
    orderId: string,
    newStatus: OrderStatus,
    location?: string
  ): Promise<Order> {
    const order = MOCK_ORDERS.find(
      (o) => o.id.toLowerCase() === orderId.toLowerCase() || o.orderNumber.toLowerCase() === orderId.toLowerCase()
    );

    if (!order) {
      throw new Error("Order not found");
    }

    const oldStatus = order.status;
    order.status = newStatus;

    // Update timeline stages
    const targetIdx = (ORDER_TIMELINE_STAGES as readonly string[]).indexOf(newStatus);
    if (targetIdx !== -1) {
      order.timeline.forEach((event, idx) => {
        if (idx <= targetIdx) {
          event.completed = true;
          event.current = idx === targetIdx;
          if (!event.timestamp) {
            event.timestamp = new Date().toISOString();
          }
          if (idx === targetIdx && location) {
            event.location = location;
          }
        } else {
          event.completed = false;
          event.current = false;
        }
      });
    }

    return order;
  }
}

