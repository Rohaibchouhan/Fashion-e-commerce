import { apiClient } from "./api-client";

export interface BlogPostData {
  id: string;
  title: string;
  slug: string;
  excerpt: string;
  content?: string;
  featuredImage: string;
  category?: string;
  author: string;
  publishedAt?: string;
}

export class BlogService {
  static async getAll(): Promise<BlogPostData[]> {
    try {
      const res = await apiClient.get<BlogPostData[]>("/content/blog/posts");
      return Array.isArray(res.data) ? res.data : [];
    } catch {
      return [];
    }
  }

  static async getBySlug(slug: string): Promise<BlogPostData | null> {
    try {
      const res = await apiClient.get<BlogPostData>(`/content/blog/posts/${encodeURIComponent(slug)}`);
      return res.data || null;
    } catch {
      return null;
    }
  }
}
