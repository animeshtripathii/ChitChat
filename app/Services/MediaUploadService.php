<?php

namespace App\Services;

use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;

class MediaUploadService
{
    /**
     * Upload an uploaded file to Cloudinary or fallback to local public disk storage.
     */
    public function upload(UploadedFile $file, string $folder = 'media'): string
    {
        $cloudName = env('CLOUDINARY_CLOUD_NAME');
        $apiKey = env('CLOUDINARY_API_KEY');
        $apiSecret = env('CLOUDINARY_API_SECRET');

        if ($cloudName && $apiKey && $apiSecret) {
            try {
                $timestamp = time();
                
                // Parameters to sign
                $params = [
                    'timestamp' => $timestamp,
                    'folder' => "chitchat/{$folder}"
                ];
                
                // Sign the parameters
                ksort($params);
                $paramString = http_build_query($params);
                $signature = sha1($paramString . $apiSecret);

                $response = \Illuminate\Support\Facades\Http::asMultipart()
                    ->post("https://api.cloudinary.com/v1_1/{$cloudName}/auto/upload", array_merge($params, [
                        'file' => fopen($file->getRealPath(), 'r'),
                        'api_key' => $apiKey,
                        'signature' => $signature,
                    ]));

                if ($response->successful()) {
                    $data = $response->json();
                    return $data['secure_url'] ?? $data['url'];
                } else {
                    \Illuminate\Support\Facades\Log::error("[MediaUploadService] Cloudinary upload failed: " . $response->body());
                }
            } catch (\Exception $e) {
                \Illuminate\Support\Facades\Log::error("[MediaUploadService] Cloudinary upload exception: " . $e->getMessage());
            }
        }

        // Store on 'public' disk inside 'uploads/{folder}' directory as fallback
        $path = $file->store("uploads/{$folder}", 'public');

        // Return the accessible public asset URL
        return asset(Storage::url($path));
    }
}
