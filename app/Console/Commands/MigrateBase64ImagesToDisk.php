<?php

namespace App\Console\Commands;

use App\Models\Category;
use App\Models\Order;
use App\Models\Product;
use App\Models\Setting;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;

class MigrateBase64ImagesToDisk extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'kashk:migrate-images-to-files {--dry-run : Only report without writing changes}';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Extract Base64 data URIs from the database and save them as physical files on the public disk';

    /**
     * Execute the console command.
     */
    public function handle(): int
    {
        $dryRun = $this->option('dry-run');
        if ($dryRun) {
            $this->warn('Running in DRY-RUN mode. No changes will be saved to disk or database.');
        }

        $this->info('Starting migration of Base64 images to physical disk files...');
        $totalBytesFreed = 0;

        // 1. Categories
        $categories = Category::all();
        $catsMigrated = 0;
        foreach ($categories as $category) {
            $raw = $category->image_path;
            if ($raw && str_starts_with($raw, 'data:image/')) {
                $bytes = strlen($raw);
                $path = $this->saveBase64ToDisk($raw, 'categories', "cat_{$category->id}");
                if ($path) {
                    $totalBytesFreed += $bytes;
                    $catsMigrated++;
                    if (!$dryRun) {
                        $category->image_path = $path;
                        $category->saveQuietly();
                    }
                    $this->line("  ✓ Migrated Category #{$category->id} ({$category->name_ar}): saved to {$path} (freed " . number_format($bytes) . " bytes)");
                }
            }
        }
        $this->info("Categories completed: {$catsMigrated} migrated.");

        // 2. Products
        $products = Product::all();
        $prodsMigrated = 0;
        foreach ($products as $product) {
            $raw = $product->image_path;
            if (empty($raw)) {
                continue;
            }

            $bytes = strlen($raw);
            $changed = false;

            // Check if JSON
            if (str_starts_with($raw, '[') || str_starts_with($raw, '{')) {
                $decoded = json_decode($raw, true);
                if (is_array($decoded)) {
                    if (array_is_list($decoded)) {
                        foreach ($decoded as $idx => $item) {
                            if (is_string($item) && str_starts_with($item, 'data:image/')) {
                                $path = $this->saveBase64ToDisk($item, 'products', "prod_{$product->id}_{$idx}");
                                if ($path) {
                                    $decoded[$idx] = $path;
                                    $changed = true;
                                }
                            } elseif (is_array($item)) {
                                foreach ($item as $k => $v) {
                                    if (is_string($v) && str_starts_with($v, 'data:image/')) {
                                        $path = $this->saveBase64ToDisk($v, 'products', "prod_{$product->id}_{$idx}_{$k}");
                                        if ($path) {
                                            $decoded[$idx][$k] = $path;
                                            $changed = true;
                                        }
                                    }
                                }
                            }
                        }
                    } else {
                        foreach ($decoded as $k => $v) {
                            if (is_string($v) && str_starts_with($v, 'data:image/')) {
                                $path = $this->saveBase64ToDisk($v, 'products', "prod_{$product->id}_{$k}");
                                if ($path) {
                                    $decoded[$k] = $path;
                                    $changed = true;
                                }
                            }
                        }
                    }

                    if ($changed) {
                        $totalBytesFreed += $bytes;
                        $prodsMigrated++;
                        if (!$dryRun) {
                            $product->image_path = json_encode($decoded);
                            $product->saveQuietly();
                        }
                        $this->line("  ✓ Migrated Product #{$product->id} ({$product->name_ar}): saved variants to disk (freed " . number_format($bytes) . " bytes)");
                    }
                }
            } elseif (str_starts_with($raw, 'data:image/')) {
                // Direct Base64 string
                $path = $this->saveBase64ToDisk($raw, 'products', "prod_{$product->id}");
                if ($path) {
                    $totalBytesFreed += $bytes;
                    $prodsMigrated++;
                    if (!$dryRun) {
                        $product->image_path = $path;
                        $product->saveQuietly();
                    }
                    $this->line("  ✓ Migrated Product #{$product->id} ({$product->name_ar}): saved to {$path} (freed " . number_format($bytes) . " bytes)");
                }
            }
        }
        $this->info("Products completed: {$prodsMigrated} migrated.");

        // 3. Settings
        $settings = Setting::all();
        $settingsMigrated = 0;
        foreach ($settings as $setting) {
            $raw = $setting->value;
            if ($raw && str_starts_with($raw, 'data:image/')) {
                $bytes = strlen($raw);
                $path = $this->saveBase64ToDisk($raw, 'settings', "setting_{$setting->key}");
                if ($path) {
                    $totalBytesFreed += $bytes;
                    $settingsMigrated++;
                    if (!$dryRun) {
                        $setting->value = $path;
                        $setting->saveQuietly();
                    }
                    $this->line("  ✓ Migrated Setting {$setting->key}: saved to {$path} (freed " . number_format($bytes) . " bytes)");
                }
            }
        }
        $this->info("Settings completed: {$settingsMigrated} migrated.");

        // 4. Orders Payment Proofs
        $orders = Order::whereNotNull('payment_proof')->get();
        $ordersMigrated = 0;
        foreach ($orders as $order) {
            $raw = $order->payment_proof;
            if ($raw && str_starts_with($raw, 'data:image/')) {
                $bytes = strlen($raw);
                $path = $this->saveBase64ToDisk($raw, 'payment-proofs', "order_{$order->id}");
                if ($path) {
                    $totalBytesFreed += $bytes;
                    $ordersMigrated++;
                    if (!$dryRun) {
                        $order->payment_proof = $path;
                        $order->saveQuietly();
                    }
                    $this->line("  ✓ Migrated Order #{$order->order_number} proof: saved to {$path} (freed " . number_format($bytes) . " bytes)");
                }
            }
        }
        $this->info("Orders completed: {$ordersMigrated} migrated.");

        $mb = round($totalBytesFreed / (1024 * 1024), 2);
        $this->newLine();
        $this->info("==================================================");
        $this->info(" Migration finished successfully!");
        $this->info(" Total database storage freed: {$mb} MB (" . number_format($totalBytesFreed) . " bytes)");
        $this->info(" All images are now saved as physical files on disk");
        $this->info(" and served directly by the web server (zero PHP).");
        $this->info("==================================================");

        return self::SUCCESS;
    }

    /**
     * Decode a base64 data URI and save to the public disk.
     */
    protected function saveBase64ToDisk(string $dataUri, string $directory, string $prefix): ?string
    {
        if (!preg_match('/^data:image\/(\w+);base64,/', $dataUri, $matches)) {
            return null;
        }

        $format = strtolower($matches[1]);
        if ($format === 'jpeg') {
            $format = 'jpg';
        }

        $base64Data = substr($dataUri, strpos($dataUri, ',') + 1);
        $binary = base64_decode($base64Data);

        if ($binary === false) {
            return null;
        }

        $filename = "{$directory}/{$prefix}_" . Str::random(8) . ".{$format}";

        if (!$this->option('dry-run')) {
            Storage::disk('public')->put($filename, $binary);
        }

        return $filename;
    }
}
