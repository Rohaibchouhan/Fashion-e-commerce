export interface OutfitItem {
  id: string | number;
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
  bundleDiscountPercent: number;
}

export const FEATURED_OUTFIT_BUNDLE: OutfitBundle = {
  id: "outfit-casual-essential",
  title: "Everyday Relaxed Capsule",
  subtitle: "Mix. Match. Make it yours.",
  tagline: "Build Your Outfit",
  bundleDiscountPercent: 10,
  items: [
    {
      id: 6,
      name: "Essential Tee",
      price: 22.0,
      image: "https://images.unsplash.com/photo-1521572267360-ee0c2909d518?w=500&auto=format&fit=crop&q=80",
      slug: "essential-tee",
      category: "T-Shirts",
      color: "Forest Moss",
      size: "M",
    },
    {
      id: 3,
      name: "Easy Chino Pants",
      price: 49.99,
      image: "https://images.unsplash.com/photo-1624378439575-d8705ad7ae80?w=500&auto=format&fit=crop&q=80",
      slug: "easy-chino-pants",
      category: "Pants",
      color: "British Khaki",
      size: "32",
    },
    {
      id: 7,
      name: "Overshirt",
      price: 55.0,
      image: "https://images.unsplash.com/photo-1591047139829-d91aecb6caea?w=500&auto=format&fit=crop&q=80",
      slug: "overshirt",
      category: "Jackets",
      color: "Natural Ecru",
      size: "L",
    },
    {
      id: 8,
      name: "Clean Sneakers",
      price: 89.99,
      image: "https://images.unsplash.com/photo-1549298916-b41d501d3772?w=500&auto=format&fit=crop&q=80",
      slug: "clean-sneakers",
      category: "Shoes",
      color: "All White",
      size: "42",
    },
  ],
};
