<?php

namespace App\Services;

use Illuminate\Http\UploadedFile;

class ParcelImageService
{
    protected string $basePath = 'uploads/parcel/image/';

    /**
     * Upload multiple images
     *
     * @param UploadedFile[] $files
     * @return array{cover:?string, images:array}
     */
    public function uploadMultiple(array $files): array
    {
        $images = [];
        $cover = null;

        $destinationPath = public_path($this->basePath);

        if (!is_dir($destinationPath)) {
            mkdir($destinationPath, 0755, true);
        }

        foreach ($files as $index => $file) {
            if (!$file || !$file->isValid()) {
                continue;
            }

            $name = now()->format('YmdHis')
                . '_' . uniqid()
                . '.' . $file->getClientOriginalExtension();

            $file->move($destinationPath, $name);

            $path = $this->basePath . $name;

            $images[] = $path;

            // أول صورة فقط
            if ($index === 0) {
                $cover = $path;
            }
        }

        return [
            'cover'  => $cover,
            'images' => $images,
        ];
    }

    /**
     * Upload single image (signature, etc.)
     */
    public function uploadSingle(UploadedFile $file, string $folder): ?string
    {
        if (!$file->isValid()) {
            return null;
        }

        $basePath = 'uploads/parcel/' . trim($folder, '/') . '/';
        $destinationPath = public_path($basePath);

        if (!is_dir($destinationPath)) {
            mkdir($destinationPath, 0755, true);
        }

        $name = now()->format('YmdHis')
            . '_' . uniqid()
            . '.' . $file->getClientOriginalExtension();

        $file->move($destinationPath, $name);

        return $basePath . $name;
    }
}
