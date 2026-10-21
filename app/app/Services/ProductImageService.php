<?php

namespace App\Services;

use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;

/**
 * Owns the lifecycle of product images stored on the public disk.
 */
class ProductImageService
{
    /**
     * Store an uploaded product image on the public disk.
     */
    public function store(UploadedFile $image): string
    {
        return $image->store('products', 'public');
    }

    /**
     * Remove a stored product image when one is present.
     */
    public function delete(?string $path): void
    {
        if ($path) {
            Storage::disk('public')->delete($path);
        }
    }
}
