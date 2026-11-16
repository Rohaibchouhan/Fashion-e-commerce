import { Product, ProductFilterParams } from "@/types/product";
import { ApiClient, PaginationMeta } from "./api-client";

export class ProductsService {
  /**
   * Fetch paginated and filtered products from the live Laravel REST API.
   */
  public static async getAll(
    params: ProductFilterParams = {}
  ): Promise<{ products: Product[]; total: number; meta?: PaginationMeta }> {
    try {
      const queryParams: Record<string, any> = { ...params };

      // Map filter properties cleanly for backend parameter parsing
      if (params.sizes && params.sizes.length > 0) {
        queryParams.sizes = params.sizes.join(",");
      }
      if (params.colors && params.colors.length > 0) {
        queryParams.colors = params.colors.join(",");
      }
      if (params.inStockOnly !== undefined) {
        queryParams.inStockOnly = params.inStockOnly ? 1 : 0;
      }

      const res = await ApiClient.get<Product[]>("/products", queryParams);
      return {
        products: res.data || [],
        total: res.meta?.total ?? res.data?.length ?? 0,
        meta: res.meta,
      };
    } catch (error) {
      console.error("[ProductsService.getAll] Error fetching products from backend:", error);
      throw error;
    }
  }

  /**
   * Fetch single product details by slug.
   */
  public static async getBySlug(slug: string): Promise<Product | null> {
    try {
      const res = await ApiClient.get<Product>(`/products/${encodeURIComponent(slug)}`);
      return res.data || null;
    } catch (error: any) {
      if (error?.status === 404) return null;
      console.error(`[ProductsService.getBySlug] Error fetching product ${slug}:`, error);
      throw error;
    }
  }

  /**
   * Fetch single product details by numeric/string ID.
   */
  public static async getById(id: string): Promise<Product | null> {
    try {
      const res = await ApiClient.get<Product>(`/products/${encodeURIComponent(id)}`);
      return res.data || null;
    } catch (error: any) {
      if (error?.status === 404) return null;
      console.error(`[ProductsService.getById] Error fetching product id ${id}:`, error);
      throw error;
    }
  }

  /**
   * Fetch featured products from database.
   */
  public static async getFeatured(): Promise<Product[]> {
    try {
      const res = await ApiClient.get<Product[]>("/products/featured");
      return res.data || [];
    } catch (error) {
      console.error("[ProductsService.getFeatured] Error fetching featured products:", error);
      throw error;
    }
  }

  /**
   * Fetch new arrivals, optionally scoped by gender.
   */
  public static async getNewArrivals(gender?: string): Promise<Product[]> {
    try {
      const params = gender && gender !== "all" ? { gender } : undefined;
      const res = await ApiClient.get<Product[]>("/products/new-arrivals", params);
      return res.data || [];
    } catch (error) {
      console.error("[ProductsService.getNewArrivals] Error fetching new arrivals:", error);
      throw error;
    }
  }

  /**
   * Fetch best-selling products from database.
   */
  public static async getBestSellers(): Promise<Product[]> {
    try {
      const res = await ApiClient.get<Product[]>("/products/bestsellers");
      return res.data || [];
    } catch (error) {
      console.error("[ProductsService.getBestSellers] Error fetching best sellers:", error);
      throw error;
    }
  }

  /**
   * Fetch trending products from database.
   */
  public static async getTrending(): Promise<Product[]> {
    try {
      const res = await ApiClient.get<Product[]>("/products/trending");
      return res.data || [];
    } catch (error) {
      console.error("[ProductsService.getTrending] Error fetching trending products:", error);
      throw error;
    }
  }

  /**
   * Fetch products on sale from database.
   */
  public static async getSale(): Promise<Product[]> {
    try {
      const res = await ApiClient.get<Product[]>("/products", { is_sale: true, limit: 12 });
      return res.data || [];
    } catch (error) {
      console.error("[ProductsService.getSale] Error fetching sale products:", error);
      throw error;
    }
  }

  /**
   * Fetch related products based on current item category and gender.
   */
  public static async getRelated(slug: string): Promise<Product[]> {
    try {
      const current = await this.getBySlug(slug);
      if (!current) return [];

      const res = await ApiClient.get<Product[]>("/products", {
        category: current.categorySlug,
        gender: current.gender,
        limit: 5,
      });

      return (res.data || []).filter((p) => p.slug !== slug).slice(0, 4);
    } catch (error) {
      console.error(`[ProductsService.getRelated] Error fetching related products for ${slug}:`, error);
      return [];
    }
  }
}
