<?php

namespace App\Service;

use Symfony\Component\HttpFoundation\File\Exception\FileException;
use Symfony\Component\HttpFoundation\File\UploadedFile;
use Symfony\Component\String\Slugger\SluggerInterface;

class FileUploader
{
    /** Largest side (in px) kept for uploaded images. */
    private const MAX_DIMENSION = 1200;

    /** WebP quality (0-100). */
    private const WEBP_QUALITY = 80;

    public function __construct(
        private string $targetDirectory,
        private SluggerInterface $slugger,
    ) {
    }

    public function upload(UploadedFile $file): string
    {
        $originalFilename = pathinfo($file->getClientOriginalName(), PATHINFO_FILENAME);
        $safeFilename = $this->slugger->slug($originalFilename) . '-' . uniqid();

        // Try to store a resized, compressed WebP version instead of the raw upload
        // (phone photos are often several MB, which makes the portfolio slow to load).
        $optimized = $this->optimize($file->getPathname(), $file->getMimeType());
        if ($optimized !== null) {
            $fileName = $safeFilename . '.webp';
            if (!is_dir($this->getTargetDirectory())) {
                mkdir($this->getTargetDirectory(), 0775, true);
            }
            if (imagewebp($optimized, $this->getTargetDirectory() . '/' . $fileName, self::WEBP_QUALITY)) {
                imagedestroy($optimized);
                return $fileName;
            }
            imagedestroy($optimized);
        }

        // Fallback: keep the original file (SVG, GIF, or GD not available).
        $fileName = $safeFilename . '.' . $file->guessExtension();

        try {
            $file->move($this->getTargetDirectory(), $fileName);
        } catch (FileException $e) {
            // ... handle exception if something happens during file upload
        }

        return $fileName;
    }

    public function getTargetDirectory(): string
    {
        return $this->targetDirectory;
    }

    /**
     * Returns a downscaled GD image, or null when the file should be kept as-is.
     */
    private function optimize(string $path, ?string $mimeType): ?\GdImage
    {
        if (!function_exists('imagewebp')) {
            return null;
        }

        $source = match ($mimeType) {
            'image/jpeg' => @imagecreatefromjpeg($path),
            'image/png' => @imagecreatefrompng($path),
            'image/webp' => @imagecreatefromwebp($path),
            'image/avif' => function_exists('imagecreatefromavif') ? @imagecreatefromavif($path) : false,
            // SVG is already light and vector; GIF may be animated: keep them untouched.
            default => false,
        };

        if (!$source instanceof \GdImage) {
            return null;
        }

        if ($mimeType === 'image/jpeg') {
            $source = $this->applyExifOrientation($source, $path);
        }

        $width = imagesx($source);
        $height = imagesy($source);
        $scale = min(1, self::MAX_DIMENSION / max($width, $height));

        if ($scale >= 1) {
            imagepalettetotruecolor($source);
            imagesavealpha($source, true);
            return $source;
        }

        $newWidth = max(1, (int) round($width * $scale));
        $newHeight = max(1, (int) round($height * $scale));

        $resized = imagecreatetruecolor($newWidth, $newHeight);
        imagealphablending($resized, false);
        imagesavealpha($resized, true);
        imagefill($resized, 0, 0, imagecolorallocatealpha($resized, 0, 0, 0, 127));
        imagecopyresampled($resized, $source, 0, 0, 0, 0, $newWidth, $newHeight, $width, $height);
        imagedestroy($source);

        return $resized;
    }

    /**
     * Phone photos store rotation in EXIF; GD ignores it, so rotate the pixels ourselves.
     */
    private function applyExifOrientation(\GdImage $image, string $path): \GdImage
    {
        if (!function_exists('exif_read_data')) {
            return $image;
        }

        $exif = @exif_read_data($path);
        $angle = match ($exif['Orientation'] ?? 1) {
            3 => 180,
            6 => -90,
            8 => 90,
            default => 0,
        };

        if ($angle === 0) {
            return $image;
        }

        $rotated = imagerotate($image, $angle, 0);
        if ($rotated instanceof \GdImage) {
            imagedestroy($image);
            return $rotated;
        }

        return $image;
    }
}
