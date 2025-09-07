<?php

declare(strict_types=1);

namespace AndreiLungeanu\SimpleCart\Console\Commands;

use AndreiLungeanu\SimpleCart\Enums\CartStatusEnum;
use AndreiLungeanu\SimpleCart\Models\Cart;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\Log;

class PurgeCartsCommand extends Command
{
    protected $signature = 'simple-cart:cleanup 
                           {--force : Force cleanup without confirmation}
                           {--days=30 : Days after which carts are considered expired}
                           {--status=abandoned : Status to set for expired carts before deletion}';

    protected $description = 'Clean up expired and abandoned carts';

    public function handle(): int
    {
        $days = (int) $this->option('days');
        $force = $this->option('force');
        $status = $this->option('status');

        $expiredDate = now()->subDays($days);

        $expiredCartsQuery = Cart::where('expires_at', '<', now())
            ->where('status', '!=', CartStatusEnum::Expired);

        $expiredCount = $expiredCartsQuery->count();

        if ($expiredCount > 0) {
            $this->info("Found {$expiredCount} expired carts to mark as expired");

            if ($force || $this->confirm('Mark expired carts?')) {
                $expiredCartsQuery->update(['status' => CartStatusEnum::Expired]);
                $this->info("Marked {$expiredCount} carts as expired");
            }
        }

        $oldCartsQuery = Cart::where('updated_at', '<', $expiredDate)
            ->whereIn('status', [CartStatusEnum::Expired, CartStatusEnum::Abandoned]);

        $oldCount = $oldCartsQuery->count();

        if ($oldCount > 0) {
            $this->info("Found {$oldCount} old carts to delete (older than {$days} days)");

            if ($force || $this->confirm('Delete old carts permanently?')) {
                $oldCartsQuery->each(function (Cart $cart) {
                    $cart->forceDelete();
                });
                $this->info("Deleted {$oldCount} old carts");
                Log::info("Simple Cart cleanup: deleted {$oldCount} old carts");
            }
        }

        $emptyCartsQuery = Cart::whereDoesntHave('items')
            ->where('created_at', '<', now()->subDays(1))
            ->where('status', CartStatusEnum::Active);

        $emptyCount = $emptyCartsQuery->count();

        if ($emptyCount > 0) {
            $this->info("Found {$emptyCount} empty carts to mark as abandoned");

            if ($force || $this->confirm('Mark empty carts as abandoned?')) {
                $emptyCartsQuery->update(['status' => CartStatusEnum::Abandoned]);
                $this->info("Marked {$emptyCount} empty carts as abandoned");
            }
        }

        $this->info('Cart cleanup completed successfully');

        return Command::SUCCESS;
    }
}
