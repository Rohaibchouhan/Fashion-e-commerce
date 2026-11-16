export interface OutfitItem {
  id: string;
  name: string;
  price: number;
  image: string;
  slug: string;
  category: string;
  color: string;
  size: string;
}

export interface OutfitBundle {
  id: string;
  title: string;
  subtitle: string;
  tagline: string;
  items: OutfitItem[];
  bundleDiscountPercent: number; // e.g. 10% bundle discount
}

export const FEATURED_OUTFIT_BUNDLE: OutfitBundle = {
  id: "outfit-casual-essential",
  title: "Everyday Relaxed Capsule",
  subtitle: "Mix. Match. Make it yours.",
  tagline: "Build Your Outfit",
  bundleDiscountPercent: 10,
  items: [
    {
      id: "prod-6",
      name: "Essential Tee",
      price: 19.99,
      image: "https://images.unsplash.com/photo-1521572267360-ee0c2909d518?w=500&auto=format&fit=crop&q=80",
      slug: "essential-tee",
      category: "T-Shirts",
      color: "Forest Moss",
      size: "M",
    },
    {
      id: "prod-3",
      name: "Chino Pants",
      price: 39.99,
      image: "https://images.unsplash.com/photo-1624378439575-d8705ad7ae80?w=500&auto=format&fit=crop&q=80",
      slug: "easy-chino-pants",
      category: "Pants",
      color: "British Khaki",
      size: "32",
    },
    {
      id: "prod-7",
      name: "Overshirt",
      price: 49.99,
      image: "https://images.unsplash.com/photo-1591047139829-d91aecb6caea?w=500&auto=format&fit=crop&q=80",
      slug: "overshirt",
      category: "Jackets",
      color: "Natural Ecru",
      size: "L",
    },
    {
      id: "prod-8",
      name: "Clean Sneakers",
      price: 69.99,
      image: "https://images.unsplash.com/photo-1549298916-b41d501d3772?w=500&auto=format&fit=crop&q=80",
      slug: "clean-sneakers",
      category: "Shoes",
      color: "All White",
      size: "42",
    },
  ],
};

