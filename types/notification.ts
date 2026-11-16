export type NotificationType =
  | "order_update"
  | "price_drop"
  | "promo"
  | "security"
  | "restock";

export interface Notification {
  id: string;
  type: NotificationType;
  title: string;
  message: string;
  timestamp: string;
  read: boolean;
  link?: string;
  data?: {
    orderId?: string;
    orderNumber?: string;
    status?: string;
    productId?: string;
  };
}

