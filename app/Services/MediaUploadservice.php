<?php

namespace App\Services;

use Cloudinary\Cloudinary;
use InvalidArgumentException;
use Exception;

class MediaUploadService
{
    protected Cloudinary $cloudinary;

    public function __construct()
    {
        $this->cloudinary = new Cloudinary([
            'cloud' => [
                'cloud_name' => env('CLOUDINARY_CLOUD_NAME'),
                'api_key' => env('CLOUDINARY_API_KEY'),
                'api_secret' => env('CLOUDINARY_API_SECRET'),
            ],
        ]);
    }

    /**
     * Upload a base64 file to Cloudinary and return the secure URL.
     *
     * @param string $avatar Base64 encoded file or file path
     * @param string $folder
     * @return string
     * @throws InvalidArgumentException
     */
    public function upload($avatar, string $folder = 'default'): string
    {
        if (empty($avatar)) {
            throw new InvalidArgumentException('No file provided for upload.');
        }
        try {
            $uploadResult = $this->cloudinary->uploadApi()->upload($avatar, [
                'folder' => $folder,
                'resource_type' => 'auto',
            ]);
            if (empty($uploadResult['secure_url'])) {
                throw new InvalidArgumentException('Upload failed: No URL returned from Cloudinary.');
            }
            return $uploadResult['secure_url'];
        } catch (\Cloudinary\Api\Exception\ApiError $e) {
            throw new InvalidArgumentException('File upload failed: ' . $e->getMessage());
        } catch (InvalidArgumentException $e) {
            throw $e;
        } catch (Exception $e) {
            throw new InvalidArgumentException('Failed to upload file. Please try again later.');
        }
    }
    /**
     * Delete a file from Cloudinary using its public_id.
     *
     * @param string $publicId
     * @return bool
     * @throws Exception
     */
    public function delete(string $url): bool
    {
        $publicId = $this->getPublicIdFromUrl($url);
        try {
            $result = $this->cloudinary->uploadApi()->destroy($publicId);
            return isset($result['result']) && $result['result'] === 'ok';
        } catch (Exception $e) {
            throw new Exception('Cloudinary delete failed: ' . $e->getMessage());
        }
    }
    public function getPublicIdFromUrl(string $url): ?string
    {
        if (preg_match('/\/v\d+\/(.+)\.\w+$/', $url, $matches)) {
            return $matches[1];
        }
        return null;
    }
}
