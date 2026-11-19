<?php

namespace App\Console\Commands;

use App\Models\Product;
use Illuminate\Console\Command;

class RecalculateProductReviewsCommand extends Command
{
    protected $signature = 'products:recalculate-reviews {--product= : Optional product ID or slug to recalculate}';

    protected $description = 'Recalculate dynamic review counts, average ratings, and rating distributions for all products based on actual approved reviews.';

    public function handle(): int
    {
        $this->info('Starting product review statistics recalculation...');

        $productId = $this->option('product');

        if ($productId) {
            $product = Product::where('id', $productId)->orWhere('slug', $productId)->first();
            if (!$product) {
                $this->error("Product [{$productId}] not found.");
                return 1;
            }
            $products = collect([$product]);
        } else {
            $products = Product::all();
        }

        $bar = $this->output->createProgressBar($products->count());
        $bar->start();

        $recalculated = 0;
        foreach ($products as $product) {
            $product->updateReviewStats();
            $recalculated++;
            $bar->advance();
        }

        $bar->finish();
        $this->newLine(2);

        $this->info("Successfully recalculated review statistics for {$recalculated} product(s).");
        return 0;
    }
}
