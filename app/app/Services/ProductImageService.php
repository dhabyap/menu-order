<?php

namespace App\Services;

use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;
use Illuminate\Validation\ValidationException;

/**
 * Owns the lifecycle of product images stored on the public disk.
 *
 * Uploaded images are normalized before they are saved:
 * - EXIF orientation is applied so photos are never sideways.
 * - The longest side is scaled down to 1600 px without upscaling.
 * - The result is encoded as WebP (JPEG fallback) and kept under 500 KB.
 *
 * The processor relies on the GD extension, which ships with a standard PHP
 * installation, so no extra package is required on the hosting server.
 */
class ProductImageService
{
    /**
     * Longest side accepted from the browser, in pixels.
     */
    public const MAX_SOURCE_DIMENSION = 4000;

    /**
     * Longest side of the stored image, in pixels.
     */
    public const MAX_STORED_SIDE = 1600;

    /**
     * Maximum size of the stored image, in bytes.
     */
    public const MAX_STORED_BYTES = 512000;

    /**
     * Quality used for the first encoding attempt.
     */
    private const INITIAL_QUALITY = 82;

    /**
     * Lowest quality still considered acceptable.
     */
    private const MINIMUM_QUALITY = 65;

    /**
     * Quality reduction applied on every retry.
     */
    private const QUALITY_STEP = 5;

    /**
     * Store an uploaded product image on the public disk.
     *
     * @throws ValidationException when the image cannot be processed
     */
    public function store(UploadedFile $image): string
    {
        $encoded = $this->encode($this->prepare($image));

        $path = 'products/'.Str::random(24).'.'.$encoded['extension'];
        Storage::disk('public')->put($path, $encoded['content']);

        return $path;
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

    /**
     * Decode, orient, and resize an uploaded image.
     *
     * @throws ValidationException
     */
    private function prepare(UploadedFile $image): \GdImage
    {
        $source = $this->decode($image);

        $width = imagesx($source);
        $height = imagesy($source);

        if ($width > self::MAX_SOURCE_DIMENSION || $height > self::MAX_SOURCE_DIMENSION) {
            throw ValidationException::withMessages([
                'image' => 'Dimensi gambar terlalu besar. Maksimal 4000 × 4000 piksel.',
            ]);
        }

        $oriented = $this->applyOrientation($source, $image->getRealPath());

        return $this->scaleDown($oriented, $width, $height);
    }

    /**
     * Turn an uploaded file into a GD image.
     *
     * @throws ValidationException
     */
    private function decode(UploadedFile $image): \GdImage
    {
        $path = $image->getRealPath();

        $decoded = match (strtolower($image->extension())) {
            'jpg', 'jpeg' => @imagecreatefromjpeg($path),
            'png' => @imagecreatefrompng($path),
            'webp' => function_exists('imagecreatefromwebp') ? @imagecreatefromwebp($path) : false,
            default => false,
        };

        if (! $decoded) {
            throw ValidationException::withMessages([
                'image' => 'Gambar tidak valid. Pastikan file gambar yang dipilih bisa dibaca.',
            ]);
        }

        return $decoded;
    }

    /**
     * Apply the EXIF orientation tag so the photo is stored upright.
     */
    private function applyOrientation(\GdImage $image, string $path): \GdImage
    {
        $exif = @exif_read_data($path);
        $orientation = (int) ($exif['Orientation'] ?? 1);

        return match ($orientation) {
            2 => imageflip($image, IMG_FLIP_HORIZONTAL),
            3 => imagerotate($image, 180, 0),
            4 => imageflip($image, IMG_FLIP_VERTICAL),
            5 => $this->flipAndRotate($image, IMG_FLIP_VERTICAL, 90),
            6 => imagerotate($image, 90, 0),
            7 => $this->flipAndRotate($image, IMG_FLIP_HORIZONTAL, 90),
            8 => imagerotate($image, 270, 0),
            default => $image,
        };
    }

    /**
     * Flip an image and rotate it in one step.
     */
    private function flipAndRotate(\GdImage $image, int $mode, float $angle): \GdImage
    {
        $flipped = imageflip($image, $mode);

        return $flipped ? imagerotate($flipped, $angle, 0) : $image;
    }

    /**
     * Scale the image so the longest side fits the stored size limit.
     */
    private function scaleDown(\GdImage $image, int $width, int $height): \GdImage
    {
        $longestSide = max($width, $height);

        if ($longestSide <= self::MAX_STORED_SIDE) {
            return $image;
        }

        $ratio = self::MAX_STORED_SIDE / $longestSide;
        $targetWidth = max(1, (int) round($width * $ratio));
        $targetHeight = max(1, (int) round($height * $ratio));

        $resized = imagecreatetruecolor($targetWidth, $targetHeight);
        imagealphablending($resized, false);
        imagesavealpha($resized, true);
        imagecopyresampled(
            $resized,
            $image,
            0,
            0,
            0,
            0,
            $targetWidth,
            $targetHeight,
            $width,
            $height
        );

        return $resized;
    }

    /**
     * Encode the prepared image and keep it under the size limit.
     *
     * @return array{content: string, extension: string}
     *
     * @throws ValidationException
     */
    private function encode(\GdImage $image): array
    {
        $webpAvailable = function_exists('imagewebp');

        $quality = self::INITIAL_QUALITY;

        do {
            $content = $webpAvailable
                ? $this->capture(fn () => imagewebp($image, null, $quality))
                : $this->capture(fn () => imagejpeg($image, null, $quality));

            if (strlen($content) <= self::MAX_STORED_BYTES) {
                return [
                    'content' => $content,
                    'extension' => $webpAvailable ? 'webp' : 'jpg',
                ];
            }

            $quality -= self::QUALITY_STEP;
        } while ($quality >= self::MINIMUM_QUALITY);

        throw ValidationException::withMessages([
            'image' => 'Ukuran gambar masih terlalu besar setelah dikompresi. '
                .'Gunakan foto dengan resolusi lebih kecil.',
        ]);
    }

    /**
     * Capture the binary output of an image encoder.
     */
    private function capture(callable $encoder): string
    {
        ob_start();
        $encoder();

        return (string) ob_get_clean();
    }
}
