"use client";

import React, { useState } from "react";
import Image from "next/image";
import { Modal } from "@/components/ui/modal";
import { Product } from "@/types/product";
import { ReviewsService, SubmitReviewPayload } from "@/services/reviews.service";
import { Star, CheckCircle2, AlertCircle, Loader2 } from "lucide-react";
import { cn } from "@/lib/utils";

interface ReviewModalProps {
  isOpen: boolean;
  onClose: () => void;
  product: Product;
  hasPurchased?: boolean;
  onSuccess?: () => void;
}

const RATING_LABELS: Record<number, string> = {
  1: "Poor (1/5)",
  2: "Fair (2/5)",
  3: "Average (3/5)",
  4: "Very Good (4/5)",
  5: "Exceptional (5/5)",
};

export const ReviewModal: React.FC<ReviewModalProps> = ({
  isOpen,
  onClose,
  product,
  hasPurchased = false,
  onSuccess,
}) => {
  const [rating, setRating] = useState<number>(5);
  const [hoverRating, setHoverRating] = useState<number | null>(null);
  const [title, setTitle] = useState<string>("");
  const [comment, setComment] = useState<string>("");
  const [isSubmitting, setIsSubmitting] = useState<boolean>(false);
  const [errorMessage, setErrorMessage] = useState<string | null>(null);
  const [successMessage, setSuccessMessage] = useState<string | null>(null);
  const [fieldErrors, setFieldErrors] = useState<{
    rating?: string;
    title?: string;
    comment?: string;
  }>({});

  const validate = (): boolean => {
    const errors: { rating?: string; title?: string; comment?: string } = {};

    if (!rating || rating < 1 || rating > 5) {
      errors.rating = "Please select a star rating between 1 and 5.";
    }

    const trimmedTitle = title.trim();
    if (!trimmedTitle) {
      errors.title = "Review headline is required.";
    } else if (trimmedTitle.length > 200) {
      errors.title = "Review headline must not exceed 200 characters.";
    }

    const trimmedComment = comment.trim();
    if (!trimmedComment) {
      errors.comment = "Review description is required.";
    } else if (trimmedComment.length < 10) {
      errors.comment = "Please provide at least 10 characters describing your experience.";
    } else if (trimmedComment.length > 2000) {
      errors.comment = "Review description must not exceed 2000 characters.";
    }

    setFieldErrors(errors);
    return Object.keys(errors).length === 0;
  };

  const handleSubmit = async (e: React.FormEvent) => {
    e.preventDefault();
    setErrorMessage(null);
    setSuccessMessage(null);

    if (!validate() || isSubmitting) {
      return;
    }

    setIsSubmitting(true);

    try {
      const payload: SubmitReviewPayload = {
        rating,
        title: title.trim(),
        comment: comment.trim(),
      };

      const res = await ReviewsService.submitReview(product.slug || product.id, payload);

      if (res.success) {
        setSuccessMessage(
          res.message || "Your review has been submitted successfully and is pending moderation."
        );
        setTimeout(() => {
          if (onSuccess) onSuccess();
          onClose();
        }, 1600);
      } else {
        setErrorMessage(res.message || "Failed to submit review. Please try again.");
      }
    } catch (err: any) {
      console.error("[ReviewModal.handleSubmit] Error submitting review:", err);
      const msg =
        err?.errors?.title?.[0] ||
        err?.errors?.comment?.[0] ||
        err?.errors?.rating?.[0] ||
        err?.message ||
        "An unexpected error occurred while submitting your review.";
      setErrorMessage(msg);
    } finally {
      setIsSubmitting(false);
    }
  };

  const activeStarRating = hoverRating !== null ? hoverRating : rating;

  return (
    <Modal
      isOpen={isOpen}
      onClose={() => {
        if (!isSubmitting) onClose();
      }}
      maxWidth="lg"
      className="p-6 sm:p-8"
    >
      <div className="space-y-6">
        {/* Header Strip with Product Context */}
        <div className="flex items-center gap-4 pb-4 border-b border-neutral-100 pr-6">
          <div className="relative w-14 h-18 rounded-xl overflow-hidden bg-neutral-100 shrink-0 border border-neutral-200">
            {product.images && product.images[0] ? (
              <Image
                src={product.images[0]}
                alt={product.name}
                fill
                className="object-cover"
              />
            ) : (
              <div className="w-full h-full flex items-center justify-center text-xs text-neutral-400 font-mono">
                WD
              </div>
            )}
          </div>
          <div className="min-w-0 flex-1">
            <span className="text-[10px] uppercase font-bold tracking-wider text-neutral-400">
              Customer Review
            </span>
            <h3 className="text-base font-black text-neutral-900 truncate tracking-tight">
              {product.name}
            </h3>
            {hasPurchased ? (
              <span className="inline-flex items-center gap-1 mt-0.5 text-[11px] font-semibold text-emerald-700">
                <CheckCircle2 className="w-3.5 h-3.5 text-emerald-600 shrink-0" /> Verified Purchase
              </span>
            ) : (
              <span className="text-[11px] text-neutral-400 mt-0.5 block">
                Standard Customer Feedback
              </span>
            )}
          </div>
        </div>

        {/* Success Banner */}
        {successMessage && (
          <div className="p-4 rounded-2xl bg-emerald-50 border border-emerald-200 text-emerald-800 text-xs flex items-start gap-2.5 animate-in fade-in">
            <CheckCircle2 className="w-4 h-4 text-emerald-600 shrink-0 mt-0.5" />
            <div className="font-medium leading-relaxed">{successMessage}</div>
          </div>
        )}

        {/* Error Banner */}
        {errorMessage && (
          <div className="p-4 rounded-2xl bg-rose-50 border border-rose-200 text-rose-800 text-xs flex items-start gap-2.5 animate-in fade-in">
            <AlertCircle className="w-4 h-4 text-rose-600 shrink-0 mt-0.5" />
            <div className="font-medium leading-relaxed">{errorMessage}</div>
          </div>
        )}

        <form onSubmit={handleSubmit} className="space-y-5">
          {/* Interactive Star Picker */}
          <div className="space-y-1.5">
            <label className="block text-xs font-bold text-neutral-900">
              Overall Rating <span className="text-rose-500">*</span>
            </label>
            <div className="flex items-center gap-2">
              <div
                className="flex items-center gap-1 cursor-pointer"
                onMouseLeave={() => setHoverRating(null)}
              >
                {[1, 2, 3, 4, 5].map((star) => (
                  <button
                    key={star}
                    type="button"
                    onClick={() => {
                      setRating(star);
                      if (fieldErrors.rating) {
                        setFieldErrors((prev) => ({ ...prev, rating: undefined }));
                      }
                    }}
                    onMouseEnter={() => setHoverRating(star)}
                    className="p-1 hover:scale-115 transition-transform cursor-pointer focus:outline-none"
                    aria-label={`Rate ${star} out of 5 stars`}
                  >
                    <Star
                      className={cn(
                        "w-7 h-7 transition-colors",
                        star <= activeStarRating
                          ? "fill-amber-400 text-amber-400"
                          : "text-neutral-200 fill-neutral-100"
                      )}
                    />
                  </button>
                ))}
              </div>
              <span className="text-xs font-bold text-neutral-700 ml-2">
                {RATING_LABELS[activeStarRating] || ""}
              </span>
            </div>
            {fieldErrors.rating && (
              <p className="text-[11px] text-rose-600 font-medium">{fieldErrors.rating}</p>
            )}
          </div>

          {/* Review Title */}
          <div className="space-y-1.5">
            <div className="flex items-center justify-between">
              <label htmlFor="review-title" className="block text-xs font-bold text-neutral-900">
                Headline / Title <span className="text-rose-500">*</span>
              </label>
              <span className="text-[10px] text-neutral-400 font-mono">
                {title.length}/200
              </span>
            </div>
            <input
              id="review-title"
              type="text"
              maxLength={200}
              placeholder="e.g., Flawless fit, breathable fabric, timeless cut"
              value={title}
              onChange={(e) => {
                setTitle(e.target.value);
                if (fieldErrors.title) {
                  setFieldErrors((prev) => ({ ...prev, title: undefined }));
                }
              }}
              disabled={isSubmitting}
              className={cn(
                "w-full h-11 px-3.5 text-xs bg-neutral-50 rounded-xl border transition-colors focus:outline-none focus:bg-white",
                fieldErrors.title
                  ? "border-rose-400 focus:ring-1 focus:ring-rose-500"
                  : "border-neutral-200 focus:border-neutral-900 focus:ring-1 focus:ring-neutral-900"
              )}
            />
            {fieldErrors.title && (
              <p className="text-[11px] text-rose-600 font-medium">{fieldErrors.title}</p>
            )}
          </div>

          {/* Review Comment */}
          <div className="space-y-1.5">
            <div className="flex items-center justify-between">
              <label htmlFor="review-comment" className="block text-xs font-bold text-neutral-900">
                Detailed Review <span className="text-rose-500">*</span>
              </label>
              <span className="text-[10px] text-neutral-400 font-mono">
                {comment.length}/2000
              </span>
            </div>
            <textarea
              id="review-comment"
              rows={4}
              maxLength={2000}
              placeholder="Share how the piece feels on the body, how it drapes, the texture of the fabric, sizing advice, and care experience..."
              value={comment}
              onChange={(e) => {
                setComment(e.target.value);
                if (fieldErrors.comment) {
                  setFieldErrors((prev) => ({ ...prev, comment: undefined }));
                }
              }}
              disabled={isSubmitting}
              className={cn(
                "w-full p-3 text-xs bg-neutral-50 rounded-xl border leading-relaxed transition-colors focus:outline-none focus:bg-white resize-none",
                fieldErrors.comment
                  ? "border-rose-400 focus:ring-1 focus:ring-rose-500"
                  : "border-neutral-200 focus:border-neutral-900 focus:ring-1 focus:ring-neutral-900"
              )}
            />
            {fieldErrors.comment && (
              <p className="text-[11px] text-rose-600 font-medium">{fieldErrors.comment}</p>
            )}
          </div>

          {/* Bottom Action Buttons */}
          <div className="pt-3 border-t border-neutral-100 flex items-center justify-end gap-3">
            <button
              type="button"
              onClick={onClose}
              disabled={isSubmitting}
              className="px-5 py-2.5 rounded-full border border-neutral-200 text-xs font-semibold text-neutral-700 hover:bg-neutral-50 transition-colors cursor-pointer disabled:opacity-50"
            >
              Cancel
            </button>
            <button
              type="submit"
              disabled={isSubmitting}
              className="px-6 py-2.5 rounded-full bg-neutral-900 hover:bg-neutral-800 text-white text-xs font-bold transition-all shadow-xs cursor-pointer flex items-center gap-2 disabled:opacity-50 active:scale-98"
            >
              {isSubmitting ? (
                <>
                  <Loader2 className="w-3.5 h-3.5 animate-spin" />
                  <span>Submitting Review...</span>
                </>
              ) : (
                <span>Submit Review</span>
              )}
            </button>
          </div>
        </form>
      </div>
    </Modal>
  );
};
