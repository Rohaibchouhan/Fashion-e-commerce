import { ReturnRequest } from "@/types/return";

export const MOCK_RETURNS: ReturnRequest[] = [
  {
    id: "ret-101",
    rmaNumber: "RMA-90412",
    orderId: "ord-77402",
    orderNumber: "WD-77402",
    createdAt: "2026-11-04T11:20:00Z",
    status: "approved",
    resolution: "refund",
    refundAmount: 44.99,
    trackingNumber: "RET-UPS-192837",
    labelUrl: "/returns/label-sample.pdf",
    items: [
      {
        orderItemId: "item-4",
        productId: "prod-4",
        name: "Everyday Hoodie",
        color: "Sage Green",
        size: "M",
        image: "https://images.unsplash.com/photo-1556905055-8f358a7a47b2?w=300&auto=format&fit=crop&q=80",
        price: 44.99,
        quantity: 1,
        reason: "wrong_size",
        comment: "Fits slightly larger than expected around the shoulders. Ordering Size S instead.",
      },
    ],
    timeline: [
      { status: "requested", label: "Return Request Submitted", date: "2026-11-04T11:20:00Z", completed: true },
      { status: "approved", label: "Return Approved & Prepaid Label Generated", date: "2026-11-04T11:22:00Z", completed: true, current: true },
      { status: "item_dropped_off", label: "Dropped Off at UPS Access Point", date: "", completed: false },
      { status: "inspected", label: "Inspected at Warehouse", date: "", completed: false },
      { status: "refunded", label: "Refund Deposited to Original Payment", date: "", completed: false },
    ],
  },
];

