export const APP_CONFIG = {
  name: "WearDay",
  tagline: "Everyday Looks. Made Effortless.",
  description: "Practical pieces. Real comfort. For every part of your day.",
  freeShippingThreshold: 50,
  defaultShippingCost: 5.99,
  currency: "USD",
  currencySymbol: "$",
  supportEmail: "care@wearday.com",
  supportPhone: "+1 (800) 555-0199",
  address: "742 Evergreen Fashion District, New York, NY 10001",
};

export const NAVIGATION_LINKS = [
  { label: "New In", href: "/new-arrivals" },
  {
    label: "Men",
    href: "/men",
    megaMenu: [
      {
        title: "Tops & Layering",
        items: [
          { name: "T-Shirts", href: "/shop?gender=men&category=t-shirts" },
          { name: "Linen & Casual Shirts", href: "/shop?gender=men&category=shirts" },
          { name: "Overshirts & Jackets", href: "/shop?gender=men&category=jackets" },
          { name: "Hoodies & Sweatshirts", href: "/shop?gender=men&category=hoodies" },
        ],
      },
      {
        title: "Bottoms & Denim",
        items: [
          { name: "Chino Pants", href: "/shop?gender=men&category=pants" },
          { name: "Slim & Straight Denim", href: "/shop?gender=men&category=denim" },
          { name: "Relaxed Trousers", href: "/shop?gender=men&category=pants" },
        ],
      },
      {
        title: "Footwear & Extras",
        items: [
          { name: "Clean Minimalist Sneakers", href: "/shop?gender=men&category=shoes" },
          { name: "Caps & Hats", href: "/shop?gender=men&category=accessories" },
          { name: "Canvas Totes & Bags", href: "/shop?gender=men&category=accessories" },
        ],
      },
    ],
  },
  {
    label: "Women",
    href: "/women",
    megaMenu: [
      {
        title: "Tops & Blouses",
        items: [
          { name: "Relaxed Fit Tees", href: "/shop?gender=women&category=t-shirts" },
          { name: "Linen Blend Shirts", href: "/shop?gender=women&category=shirts" },
          { name: "Classic Denim Jackets", href: "/shop?gender=women&category=jackets" },
          { name: "Cosy Knitwear & Hoodies", href: "/shop?gender=women&category=hoodies" },
        ],
      },
      {
        title: "Trousers & Jeans",
        items: [
          { name: "High-Rise Denim", href: "/shop?gender=women&category=denim" },
          { name: "Easy Pleated Chinos", href: "/shop?gender=women&category=pants" },
          { name: "Flowing Linen Pants", href: "/shop?gender=women&category=pants" },
        ],
      },
      {
        title: "Accessories & Shoes",
        items: [
          { name: "Everyday White Sneakers", href: "/shop?gender=women&category=shoes" },
          { name: "Minimalist Leather Bags", href: "/shop?gender=women&category=accessories" },
          { name: "Caps & Headwear", href: "/shop?gender=women&category=accessories" },
        ],
      },
    ],
  },
  { label: "Best Sellers", href: "/best-sellers" },
  { label: "Trending", href: "/trending" },
  { label: "Sale", href: "/sale" },
  { label: "Styling Guides", href: "/blog" },
];

export const CATEGORIES = [
  {
    id: "t-shirts",
    name: "T-Shirts",
    slug: "t-shirts",
    image: "https://images.unsplash.com/photo-1521572267360-ee0c2909d518?w=300&auto=format&fit=crop&q=80",
    itemCount: 24,
  },
  {
    id: "shirts",
    name: "Shirts",
    slug: "shirts",
    image: "https://images.unsplash.com/photo-1596755094514-f87e34085b2c?w=300&auto=format&fit=crop&q=80",
    itemCount: 18,
  },
  {
    id: "pants",
    name: "Pants",
    slug: "pants",
    image: "https://images.unsplash.com/photo-1624378439575-d8705ad7ae80?w=300&auto=format&fit=crop&q=80",
    itemCount: 16,
  },
  {
    id: "denim",
    name: "Denim",
    slug: "denim",
    image: "https://images.unsplash.com/photo-1542272604-780c96856592?w=300&auto=format&fit=crop&q=80",
    itemCount: 14,
  },
  {
    id: "hoodies",
    name: "Hoodies",
    slug: "hoodies",
    image: "https://images.unsplash.com/photo-1556905055-8f358a7a47b2?w=300&auto=format&fit=crop&q=80",
    itemCount: 12,
  },
  {
    id: "jackets",
    name: "Jackets",
    slug: "jackets",
    image: "https://images.unsplash.com/photo-1551028719-00167b16eac5?w=300&auto=format&fit=crop&q=80",
    itemCount: 10,
  },
  {
    id: "accessories",
    name: "Accessories",
    slug: "accessories",
    image: "https://images.unsplash.com/photo-1588850561407-ed78c282e89b?w=300&auto=format&fit=crop&q=80",
    itemCount: 20,
  },
  {
    id: "shoes",
    name: "Shoes",
    slug: "shoes",
    image: "https://images.unsplash.com/photo-1549298916-b41d501d3772?w=300&auto=format&fit=crop&q=80",
    itemCount: 9,
  },
];

export const ORDER_STATUS_DETAILS = {
  placed: { label: "Order Placed", step: 1, color: "bg-amber-100 text-amber-800 border-amber-200" },
  confirmed: { label: "Order Confirmed", step: 2, color: "bg-blue-100 text-blue-800 border-blue-200" },
  processing: { label: "Processing", step: 3, color: "bg-indigo-100 text-indigo-800 border-indigo-200" },
  packed: { label: "Packed in Warehouse", step: 4, color: "bg-purple-100 text-purple-800 border-purple-200" },
  dispatched: { label: "Dispatched", step: 5, color: "bg-sky-100 text-sky-800 border-sky-200" },
  in_transit: { label: "In Transit", step: 6, color: "bg-cyan-100 text-cyan-800 border-cyan-200" },
  out_for_delivery: { label: "Out for Delivery", step: 7, color: "bg-amber-100 text-amber-900 border-amber-300" },
  delivered: { label: "Delivered", step: 8, color: "bg-emerald-100 text-emerald-800 border-emerald-200" },
  cancelled: { label: "Cancelled", step: 0, color: "bg-rose-100 text-rose-800 border-rose-200" },
} as const;

export const ORDER_TIMELINE_STAGES = [
  "placed",
  "confirmed",
  "processing",
  "packed",
  "dispatched",
  "in_transit",
  "out_for_delivery",
  "delivered",
] as const;

export const RETURN_STATUS_DETAILS = {
  requested: { label: "Return Requested", step: 1, color: "bg-amber-100 text-amber-800" },
  approved: { label: "Approved (RMA Issued)", step: 2, color: "bg-blue-100 text-blue-800" },
  item_dropped_off: { label: "Package Dropped Off", step: 3, color: "bg-purple-100 text-purple-800" },
  inspected: { label: "Inspected at Facility", step: 4, color: "bg-indigo-100 text-indigo-800" },
  refunded: { label: "Refund Processed", step: 5, color: "bg-emerald-100 text-emerald-800" },
  exchanged: { label: "Exchange Shipped", step: 5, color: "bg-emerald-100 text-emerald-800" },
  rejected: { label: "Return Rejected", step: 0, color: "bg-rose-100 text-rose-800" },
} as const;

export const RETURN_REASONS = [
  { value: "wrong_size", label: "Fit / Size did not match" },
  { value: "damaged_defective", label: "Item arrived damaged or defective" },
  { value: "not_as_described", label: "Fabric/Color not as pictured on site" },
  { value: "quality_issue", label: "Quality did not meet expectations" },
  { value: "changed_mind", label: "Changed mind / don't need anymore" },
  { value: "received_wrong_item", label: "Received wrong item or variant" },
] as const;

export const SHIPPING_METHODS = [
  {
    id: "standard",
    name: "Standard Ground Delivery",
    description: "Eco-friendly carbon-neutral delivery",
    price: 5.99,
    estimatedDays: "3-5 business days",
  },
  {
    id: "express",
    name: "Express Air Shipping",
    description: "Fast-tracked priority fulfillment",
    price: 14.99,
    estimatedDays: "1-2 business days",
  },
  {
    id: "overnight",
    name: "Next Day Priority",
    description: "Guaranteed next day delivery before 5 PM",
    price: 24.99,
    estimatedDays: "Next business day",
  },
];

export const VALID_COUPONS = [
  {
    code: "WEARDAY15",
    discountType: "percentage" as const,
    value: 15,
    minOrderAmount: 40,
    description: "15% off orders over $40",
  },
  {
    code: "WELCOME10",
    discountType: "percentage" as const,
    value: 10,
    minOrderAmount: 0,
    description: "10% off your first order",
  },
  {
    code: "SAVE20",
    discountType: "fixed" as const,
    value: 20,
    minOrderAmount: 100,
    description: "$20 off orders above $100",
  },
];

