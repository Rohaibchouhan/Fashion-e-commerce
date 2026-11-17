import { Product } from "@/types/product";
import { ProductsService } from "./products.service";

export class WishlistService {
  static async getItems(ids: string[]): Promise<Product[]> {
    if (!ids || ids.length === 0) return [];
    try {
      const { products } = await ProductsService.getAll({ limit: 100 });
      return products.filter((p) => ids.includes(String(p.id)) || ids.includes(p.slug));
    } catch {
      return [];
    }
  }
}
