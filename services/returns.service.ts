import { ReturnRequest, ReturnItem, ReturnStatus } from "@/types/return";
import { MOCK_RETURNS } from "@/lib/mock-data/returns";
import { MOCK_ORDERS } from "@/lib/mock-data/orders";

export interface CreateReturnPayload {
  orderId: string;
  orderNumber: string;
  items: ReturnItem[];
  resolution: "refund" | "exchange";
  exchangeDetails?: string;
}

export class ReturnsService {
  static async getAll(): Promise<ReturnRequest[]> {
    await new Promise((r) => setTimeout(r, 120));
    return [...MOCK_RETURNS];
  }

  static async getById(id: string): Promise<ReturnRequest | null> {
    await new Promise((r) => setTimeout(r, 100));
    const clean = id.toLowerCase();
    return (
      MOCK_RETURNS.find((ret) => ret.id.toLowerCase() === clean || ret.rmaNumber.toLowerCase() === clean) || null
    );
  }

  static async createReturn(payload: CreateReturnPayload): Promise<ReturnRequest> {
    await new Promise((r) => setTimeout(r, 350));

    const totalRefund = payload.items.reduce((acc, curr) => acc + curr.price * curr.quantity, 0);
    const rma = `RMA-${Math.floor(10000 + Math.random() * 90000)}`;
    const newId = `ret-${Date.now().toString().slice(-6)}`;

    const newReturn: ReturnRequest = {
      id: newId,
      rmaNumber: rma,
      orderId: payload.orderId,
      orderNumber: payload.orderNumber,
      createdAt: new Date().toISOString(),
      status: "approved",
      items: payload.items,
      resolution: payload.resolution,
      exchangeDetails: payload.exchangeDetails,
      refundAmount: totalRefund,
      trackingNumber: `RET-UPS-${Math.floor(100000 + Math.random() * 900000)}`,
      labelUrl: "/returns/label-sample.pdf",
      timeline: [
        { status: "requested", label: "Return Request Submitted", date: new Date().toISOString(), completed: true },
        { status: "approved", label: "Return Approved & Prepaid Label Generated", date: new Date().toISOString(), completed: true, current: true },
        { status: "item_dropped_off", label: "Package Dropped Off", date: "", completed: false },
        { status: "inspected", label: "Inspected at Facility", date: "", completed: false },
        { status: "refunded", label: "Refund Processed", date: "", completed: false },
      ],
    };

    MOCK_RETURNS.unshift(newReturn);
    return newReturn;
  }
}

