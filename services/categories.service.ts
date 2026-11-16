import { ApiClient } from "./api-client";

export interface Category {
  id: string;
  name: string;
  slug: string;
  description?: string;
  image?: string;
  gender?: string;
  parentId?: string | null;
  itemCount?: number;
  children?: Category[];
}

export class CategoriesService {
  public static async getAll(params?: { gender?: string }): Promise<Category[]> {
    try {
      const res = await ApiClient.get<Category[]>("/categories", params);
      return res.data || [];
    } catch (error) {
      console.error("[CategoriesService.getAll] Error fetching categories:", error);
      throw error;
    }
  }

  public static async getBySlug(slug: string): Promise<Category | null> {
    try {
      const res = await ApiClient.get<Category>(`/categories/${encodeURIComponent(slug)}`);
      return res.data || null;
    } catch (error: any) {
      if (error?.status === 404) return null;
      console.error(`[CategoriesService.getBySlug] Error fetching category ${slug}:`, error);
      throw error;
    }
  }
}
