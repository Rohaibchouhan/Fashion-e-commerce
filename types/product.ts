export interface ProductColor {
  name: string;
  hex: string;
  image?: string;
}

export interface ProductSize {
  name: string; // 'XS' | 'S' | 'M' | 'L' | 'XL' | 'XXL' or number
  inStock: boolean;
  stockCount: number;
}

export interface ProductVariant {
  id: string;
  sku: string;
  color: string;
  size: string;
  price: number;
  compareAtPrice?: number;
  stock: number;
  image?: string;
}

export interface ProductReview {
  id: string;
  author: string;
  rating: number;
  date: string;
  title: string;
  comment: string;
  verified: boolean;
}

export interface RatingBreakdownItem {
  star: number;
  count: number;
  percentage: number;
}

export interface RatingStats {
  averageRating: number;
  totalCount: number;
  counts: Record<string, number>;
  percentages: Record<string, number>;
  breakdown: RatingBreakdownItem[];
}

export type ProductGender = "men" | "women" | "unisex";

export interface Product {
  id: string;
  slug: string;
  name: string;
  subtitle: string;
  description: string;
  category: string;
  categorySlug: string;
  gender: ProductGender;
  price: number;
  compareAtPrice?: number;
  isSale?: boolean;
  isNew?: boolean;
  isBestSeller?: boolean;
  isTrending?: boolean;
  rating: number;
  reviewCount: number;
  ratingStats?: RatingStats;
  images: string[];
  colors: ProductColor[];
  sizes: ProductSize[];
  variants: ProductVariant[];
  details: string[];
  care: string[];
  fabric: string;
  fit: string;
  inStock: boolean;
  outfitPairings?: string[]; // IDs or slugs of pairing items
  reviews?: ProductReview[];
}

export interface ProductFilterParams {
  gender?: string;
  category?: string;
  minPrice?: number;
  maxPrice?: number;
  sizes?: string[];
  colors?: string[];
  sort?: "featured" | "newest" | "price-asc" | "price-desc" | "rating";
  search?: string;
  inStockOnly?: boolean;
  page?: number;
  limit?: number;
}

