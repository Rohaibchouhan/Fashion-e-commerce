import { ApiClient } from "./api-client";
import { ProductReview, RatingStats } from "@/types/product";

export interface SubmitReviewPayload {
  rating: number;
  title: string;
  comment: string;
  images?: string[];
}

export interface ReviewSubmissionResponse {
  id: string;
  rating: number;
  title: string;
  comment: string;
  is_verified_purchase: boolean;
  is_approved: boolean;
}

export interface UserReviewData {
  id: string;
  rating: number;
  title: string;
  comment: string;
  isVerifiedPurchase: boolean;
  isApproved: boolean;
  status: "approved" | "pending";
  createdAt: string;
}

export interface UserEligibilityData {
  isAuthenticated: boolean;
  hasPurchased: boolean;
  canReview: boolean;
}

export interface GetReviewsResponse {
  data: ProductReview[];
  stats?: RatingStats;
  userReview?: UserReviewData | null;
  userEligibility?: UserEligibilityData;
  meta: {
    current_page: number;
    last_page: number;
    total: number;
  };
}

export class ReviewsService {
  /**
   * Submit a new customer review for a product.
   */
  public static async submitReview(
    productIdOrSlug: string,
    payload: SubmitReviewPayload
  ): Promise<{ success: boolean; message: string; data: ReviewSubmissionResponse }> {
    const res = await ApiClient.post<ReviewSubmissionResponse>(
      `/products/${encodeURIComponent(productIdOrSlug)}/reviews`,
      payload
    );

    return {
      success: res.success,
      message: res.message || "Review submitted successfully. It will appear once approved by our moderation team.",
      data: res.data,
    };
  }

  /**
   * Fetch paginated approved reviews for a product along with dynamic statistics and user status.
   */
  public static async getReviews(
    productIdOrSlug: string,
    page = 1
  ): Promise<GetReviewsResponse> {
    const res = await ApiClient.get<ProductReview[]>(
      `/products/${encodeURIComponent(productIdOrSlug)}/reviews`,
      { page }
    );

    const raw = res as any;

    return {
      data: res.data || [],
      stats: raw.stats,
      userReview: raw.userReview || null,
      userEligibility: raw.userEligibility || {
        isAuthenticated: !!ApiClient.getAuthToken(),
        hasPurchased: false,
        canReview: !!ApiClient.getAuthToken(),
      },
      meta: res.meta || { current_page: 1, last_page: 1, total: res.data?.length || 0 },
    };
  }
}
