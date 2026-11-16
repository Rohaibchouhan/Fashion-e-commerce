export type ReturnStatus =
  | "requested"
  | "approved"
  | "item_dropped_off"
  | "inspected"
  | "refunded"
  | "rejected"
  | "exchanged";

export type ReturnReason =
  | "wrong_size"
  | "damaged_defective"
  | "not_as_described"
  | "quality_issue"
  | "changed_mind"
  | "received_wrong_item";

export interface ReturnItem {
  orderItemId: string;
  productId: string;
  name: string;
  color: string;
  size: string;
  image: string;
  price: number;
  quantity: number;
  reason: ReturnReason;
  comment?: string;
}

export interface ReturnTimelineEvent {
  status: ReturnStatus;
  label: string;
  date: string;
  completed: boolean;
  current?: boolean;
}

export interface ReturnRequest {
  id: string;
  rmaNumber: string;
  orderId: string;
  orderNumber: string;
  createdAt: string;
  status: ReturnStatus;
  items: ReturnItem[];
  resolution: "refund" | "exchange";
  exchangeDetails?: string;
  refundAmount: number;
  trackingNumber?: string;
  labelUrl?: string;
  timeline: ReturnTimelineEvent[];
}

