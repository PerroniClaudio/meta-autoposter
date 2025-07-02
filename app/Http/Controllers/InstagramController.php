<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Http;

class InstagramController extends Controller {
    private $accessToken;
    private $baseUrl;
    private $instagramBusinessAccountId;

    public function __construct() {
        $this->accessToken = config('services.instagram.access_token');
        $this->baseUrl = 'https://graph.facebook.com/' . config('services.instagram.api_version', 'v23.0');
        $this->instagramBusinessAccountId = config('services.instagram.business_account_id');
    }

    /**
     * Crea un container per un post immagine
     */
    private function createImageContainer($imageUrl, $caption = '') {
        try {
            Log::info("Creating Instagram image container with URL: $imageUrl");

            $response = Http::post($this->baseUrl . '/' . $this->instagramBusinessAccountId . '/media', [
                'image_url' => $imageUrl,
                'caption' => $caption,
                'access_token' => $this->accessToken
            ]);

            if ($response->successful()) {
                $data = $response->json();
                Log::info("Instagram image container created with ID: " . $data['id']);
                return $data['id'];
            } else {
                Log::error("Failed to create Instagram image container", $response->json());
                return null;
            }
        } catch (\Exception $e) {
            Log::error("ERROR creating Instagram image container: " . $e->getMessage());
            return null;
        }
    }

    /**
     * Crea un container per un post video
     */
    private function createVideoContainer($videoUrl, $caption = '') {
        try {
            Log::info("Creating Instagram video container with URL: $videoUrl");

            $params = [
                'media_type' => 'VIDEO',
                'video_url' => $videoUrl,
                'caption' => $caption,
                'access_token' => $this->accessToken
            ];

            $response = Http::post($this->baseUrl . '/' . $this->instagramBusinessAccountId . '/media', $params);

            if ($response->successful()) {
                $data = $response->json();
                Log::info("Instagram video container created with ID: " . $data['id']);
                return $data['id'];
            } else {
                Log::error("Failed to create Instagram video container", $response->json());
                return null;
            }
        } catch (\Exception $e) {
            Log::error("ERROR creating Instagram video container: " . $e->getMessage());
            return null;
        }
    }

    /**
     * Crea un container per una Storia (immagine o video)
     */
    private function createStoryContainer($mediaUrl, $mediaType = 'IMAGE') {
        try {
            Log::info("Creating Instagram Story container", ['url' => $mediaUrl, 'type' => $mediaType]);

            $params = [
                'media_type' => 'STORIES',
                'access_token' => $this->accessToken
            ];

            if (strtoupper($mediaType) === 'IMAGE') {
                $params['image_url'] = $mediaUrl;
            } else {
                $params['video_url'] = $mediaUrl;
            }

            $response = Http::post($this->baseUrl . '/' . $this->instagramBusinessAccountId . '/media', $params);

            if ($response->successful()) {
                $data = $response->json();
                Log::info("Instagram Story container created with ID: " . $data['id']);
                return $data['id'];
            } else {
                Log::error("Failed to create Instagram Story container", $response->json());
                return null;
            }
        } catch (\Exception $e) {
            Log::error("ERROR creating Instagram Story container: " . $e->getMessage());
            return null;
        }
    }

    /**
     * Crea un container per un Reel
     */
    private function createReelContainer($videoUrl, $caption = '', $shareToFeed = false) {
        try {
            Log::info("Creating Instagram Reel container", ['url' => $videoUrl]);

            $params = [
                'media_type' => 'REELS',
                'video_url' => $videoUrl,
                'caption' => $caption,
                'share_to_feed' => $shareToFeed,
                'access_token' => $this->accessToken
            ];

            $response = Http::post($this->baseUrl . '/' . $this->instagramBusinessAccountId . '/media', $params);

            if ($response->successful()) {
                $data = $response->json();
                Log::info("Instagram Reel container created with ID: " . $data['id']);
                return $data['id'];
            } else {
                Log::error("Failed to create Instagram Reel container", $response->json());
                return null;
            }
        } catch (\Exception $e) {
            Log::error("ERROR creating Instagram Reel container: " . $e->getMessage());
            return null;
        }
    }


    /**
     * Pubblica un container media
     */
    private function publishMedia($containerId) {
        try {
            Log::info("Publishing Instagram media container: $containerId");

            $response = Http::post($this->baseUrl . '/' . $this->instagramBusinessAccountId . '/media_publish', [
                'creation_id' => $containerId,
                'access_token' => $this->accessToken
            ]);

            if ($response->successful()) {
                $data = $response->json();
                Log::info("Instagram media published successfully with ID: " . $data['id']);
                return response()->json([
                    'success' => true,
                    'media_id' => $data['id'],
                    'data' => $data
                ]);
            } else {
                Log::error("Failed to publish Instagram media", $response->json());
                return response()->json([
                    'success' => false,
                    'error' => $response->json()
                ], $response->status());
            }
        } catch (\Exception $e) {
            Log::error("ERROR publishing Instagram media: " . $e->getMessage());
            return response()->json(['error' => $e->getMessage()], 500);
        }
    }

    /**
     * Controlla lo status di un container
     */
    private function checkContainerStatus($containerId) {
        try {
            Log::info("Checking Instagram container status: $containerId");

            $response = Http::get($this->baseUrl . '/' . $containerId, [
                'fields' => 'status_code,status',
                'access_token' => $this->accessToken
            ]);

            if ($response->successful()) {
                return $response->json();
            } else {
                Log::error("Failed to check container status", $response->json());
                return null;
            }
        } catch (\Exception $e) {
            Log::error("ERROR checking container status: " . $e->getMessage());
            return null;
        }
    }

    /**
     * Helper method per creare e pubblicare un post immagine in un'unica operazione
     */
    public function createAndPublishImage($imageUrl, $caption = '') {
        try {
            Log::info("Creating and publishing Instagram image post");

            $containerId = $this->createImageContainer($imageUrl, $caption);
            if (!$containerId) {
                return response()->json(['success' => false, 'error' => 'Failed to create image container'], 400);
            }

            // Attendi che il container sia pronto
            sleep(5); // Aumentato per maggiore affidabilità

            return $this->publishMedia($containerId);
        } catch (\Exception $e) {
            Log::error("ERROR in createAndPublishImage: " . $e->getMessage());
            return response()->json(['error' => $e->getMessage()], 500);
        }
    }

    /**
     * Helper method per creare e pubblicare un post video in un'unica operazione
     */
    public function createAndPublishVideo($videoUrl, $caption = '') {
        try {
            Log::info("Creating and publishing Instagram video post");

            $containerId = $this->createVideoContainer($videoUrl, $caption);
            if (!$containerId) {
                return response()->json(['success' => false, 'error' => 'Failed to create video container'], 400);
            }

            // I video richiedono più tempo per l'elaborazione
            $attempts = 0;
            $maxAttempts = 12; // 12 tentativi * 10 secondi = 2 minuti di attesa massima
            $status = null;

            do {
                sleep(10);
                $status = $this->checkContainerStatus($containerId);
                $attempts++;
            } while ($attempts < $maxAttempts && $status && $status['status_code'] !== 'FINISHED');

            if (!$status || $status['status_code'] !== 'FINISHED') {
                Log::error('Video processing timed out or failed.', ['status' => $status]);
                return response()->json([
                    'success' => false,
                    'error' => 'Video not ready for publishing after ' . ($maxAttempts * 10) . ' seconds.',
                    'status' => $status
                ], 400);
            }

            return $this->publishMedia($containerId);
        } catch (\Exception $e) {
            Log::error("ERROR in createAndPublishVideo: " . $e->getMessage());
            return response()->json(['error' => $e->getMessage()], 500);
        }
    }

    /**
     * Helper method per creare e pubblicare una Storia (immagine o video)
     */
    public function createAndPublishStory($mediaUrl, $mediaType = 'IMAGE') {
        try {
            Log::info("Creating and publishing Instagram Story");

            $containerId = $this->createStoryContainer($mediaUrl, $mediaType);
            if (!$containerId) {
                return response()->json(['success' => false, 'error' => 'Failed to create Story container'], 400);
            }

            // Attendi che il container sia pronto
            $attempts = 0;
            $maxAttempts = 12; // 1 minuto di attesa massima
            $status = null;

            do {
                sleep(5);
                $status = $this->checkContainerStatus($containerId);
                $attempts++;
            } while ($attempts < $maxAttempts && $status && $status['status_code'] !== 'FINISHED');

            if (!$status || $status['status_code'] !== 'FINISHED') {
                Log::error('Story processing timed out or failed.', ['status' => $status]);
                return response()->json(['success' => false, 'error' => 'Story not ready for publishing.', 'status' => $status], 400);
            }

            return $this->publishMedia($containerId);
        } catch (\Exception $e) {
            Log::error("ERROR in createAndPublishStory: " . $e->getMessage());
            return response()->json(['error' => $e->getMessage()], 500);
        }
    }

    /**
     * Helper method per creare e pubblicare un Reel
     */
    public function createAndPublishReel($videoUrl, $caption = '', $shareToFeed = false) {
        try {
            Log::info("Creating and publishing Instagram Reel");

            $containerId = $this->createReelContainer($videoUrl, $caption, $shareToFeed);
            if (!$containerId) {
                return response()->json(['success' => false, 'error' => 'Failed to create Reel container'], 400);
            }

            // I Reel, essendo video, richiedono tempo per l'elaborazione
            $attempts = 0;
            $maxAttempts = 12; // 2 minuti di attesa massima
            $status = null;

            do {
                sleep(10);
                $status = $this->checkContainerStatus($containerId);
                $attempts++;
            } while ($attempts < $maxAttempts && $status && $status['status_code'] !== 'FINISHED');

            if (!$status || $status['status_code'] !== 'FINISHED') {
                Log::error('Reel processing timed out or failed.', ['status' => $status]);
                return response()->json([
                    'success' => false,
                    'error' => 'Reel not ready for publishing after ' . ($maxAttempts * 10) . ' seconds.',
                    'status' => $status
                ], 400);
            }

            return $this->publishMedia($containerId);
        } catch (\Exception $e) {
            Log::error("ERROR in createAndPublishReel: " . $e->getMessage());
            return response()->json(['error' => $e->getMessage()], 500);
        }
    }
}
