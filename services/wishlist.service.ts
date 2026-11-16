import { MOCK_PRODUCTS } from "@/lib/mock-data/products";
import { Product } from "@/types/product";

export class WishlistService {
  static async getItems(ids: string[]): Promise<Product[]> {
    await new Promise((r) => setTimeout(r, 60));
    return MOCK_PRODUCTS.filter((p) => ids.includes(p.id));
  }
}

