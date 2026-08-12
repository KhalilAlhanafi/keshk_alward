<?php

namespace App\Jobs;

use App\Models\Product;
use App\Services\ImageService;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Storage;

class ProcessProductImage implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    /**
     * Number of retry attempts.
     */
    public int $tries = 3;

    /**
     * Timeout in seconds.
     */
    public int $timeout = 120;

    /**
     * @param int $productId
     * @param string $tempPath Temporary path of the uploaded file on the 'local' disk
     */
    public function __construct(
        public readonly int $productId,
        public readonly string $tempPath,
    ) {}

    /**
     * Execute the job.
     */
    public function handle(ImageService $imageService): void
    {
        $product = Product::find($this->productId);

        if (!$product) {
            Log::warning("[ProcessProductImage] Product not found: #{$this->productId}");
            return;
        }

        // Read the uploaded file from temp storage
        $tempFile = Storage::disk('local')->path($this->tempPath);

        if (!file_exists($tempFile)) {
            Log::error("[ProcessProductImage] Temp file not found at {$tempFile}");
            return;
        }

        // Process image and generate all size variants
        $uploadedFile = new \Illuminate\Http\File($tempFile);

        // Create an UploadedFile instance from file on disk
        $fakeUploadedFile = new \Illuminate\Http\UploadedFile(
            $tempFile,
            basename($this->tempPath),
            mime_content_type($tempFile),
            null,
            true
        );

        $paths = $imageService->store($fakeUploadedFile, 'products');

        // Store paths as JSON on the product's image_path column
        $product->image_path = json_encode($paths);
        $product->save();

        // Clean up temp file
        Storage::disk('local')->delete($this->tempPath);

        Log::info("[ProcessProductImage] Product #{$this->productId} images processed successfully.");
    }

    /**
     * Handle a job failure.
     */
    public function failed(\Throwable $exception): void
    {
        Log::error("[ProcessProductImage] Job failed for product #{$this->productId}: " . $exception->getMessage());
        // Clean up temp file even on failure
        Storage::disk('local')->delete($this->tempPath);
    }
}
