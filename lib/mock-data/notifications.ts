import { Notification } from "@/types/notification";

export const MOCK_NOTIFICATIONS: Notification[] = [
  {
    id: "notif-1",
    type: "order_update",
    title: "Order Dispatched",
    message: "Your order WD-88219 has been dispatched via FedEx Ground. Track your delivery.",
    timestamp: "2026-11-13T16:00:00Z",
    read: false,
    link: "/account/orders/ord-88219/track",
    data: {
      orderId: "ord-88219",
      orderNumber: "WD-88219",
      status: "dispatched",
    },
  },
  {
    id: "notif-2",
    type: "promo",
    title: "Weekend Wardrobe Flash Sale",
    message: "Take an extra 15% off all layering essentials with code WEARDAY15.",
    timestamp: "2026-11-12T09:00:00Z",
    read: false,
    link: "/sale",
  },
  {
    id: "notif-3",
    type: "order_update",
    title: "Return Approved",
    message: "Return request RMA-90412 for Order WD-77402 was approved. Your prepaid shipping label is ready.",
    timestamp: "2026-11-04T11:22:00Z",
    read: true,
    link: "/account/returns/ret-101",
    data: {
      orderId: "ord-77402",
      orderNumber: "WD-77402",
    },
  },
  {
    id: "notif-4",
    type: "security",
    title: "New Device Login",
    message: "We detected a login from Chrome on macOS in New York, USA. If this wasn't you, review security.",
    timestamp: "2026-10-30T18:24:00Z",
    read: true,
    link: "/account/security",
  },
];

