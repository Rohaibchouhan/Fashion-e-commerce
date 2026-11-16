<?php

namespace App\Services;

use App\Models\Product;
use App\Models\User;
use App\Models\Wishlist;
use App\Models\WishlistItem;

class WishlistService
{
    public function getWishlist(User $user)
    {
        $wishlist = Wishlist::firstOrCreate(['user_id' => $user->id]);
        return $wishlist->items()->with(['product.images', 'product.variants', 'product.category'])->get();
    }

    public function addToWishlist(User $user, int $productId): WishlistItem
    {
        $wishlist = Wishlist::firstOrCreate(['user_id' => $user->id]);
        Product::findOrFail($productId);

        return WishlistItem::firstOrCreate([
            'wishlist_id' => $wishlist->id,
            'product_id' => $productId,
        ]);
    }

    public function removeFromWishlist(User $user, int $productId): void
    {
        $wishlist = Wishlist::where('user_id', $user->id)->first();
        if ($wishlist) {
            $wishlist->items()->where('product_id', $productId)->delete();
        }
    }

    public function isInWishlist(User $user, int $productId): bool
    {
        $wishlist = Wishlist::where('user_id', $user->id)->first();
        return $wishlist ? $wishlist->items()->where('product_id', $productId)->exists() : false;
    }
}

