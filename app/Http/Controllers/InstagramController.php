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
        $this->baseUrl = 'https://graph.facebook.com/v23.0';
        $this->instagramBusinessAccountId = config('services.instagram.business_account_id');
    }

    /**
     * Ottiene informazioni sull'account Instagram Business
     */
    public function getAccountInfo() {
        try {
            Log::info("Getting Instagram account info for: " . $this->instagramBusinessAccountId);

            $response = Http::get($this->baseUrl . '/' . $this->instagramBusinessAccountId, [
                'fields' => 'id,username,account_type,media_count,followers_count,follows_count,name,profile_picture_url,website,biography',
                'access_token' => $this->accessToken
            ]);

            if ($response->successful()) {
                Log::info("Instagram account info retrieved successfully");
                return response()->json([
                    'success' => true,
                    'data' => $response->json()
                ]);
            } else {
                Log::error("Failed to get Instagram account info", $response->json());
                return response()->json([
                    'success' => false,
                    'error' => $response->json()
                ], $response->status());
            }
        } catch (\Exception $e) {
            Log::error("ERROR getting Instagram account info: " . $e->getMessage());
            return response()->json(['error' => $e->getMessage()], 500);
        }
    }

    /**
     * Ottiene i media di un account Instagram
     */
    public function getMedia($limit = 25) {
        try {
            Log::info("Getting Instagram media, limit: $limit");

            $response = Http::get($this->baseUrl . '/' . $this->instagramBusinessAccountId . '/media', [
                'fields' => 'id,caption,media_type,media_url,permalink,thumbnail_url,timestamp,username,comments_count,like_count',
                'limit' => $limit,
                'access_token' => $this->accessToken
            ]);

            if ($response->successful()) {
                Log::info("Instagram media retrieved successfully");
                return response()->json([
                    'success' => true,
                    'data' => $response->json()
                ]);
            } else {
                Log::error("Failed to get Instagram media", $response->json());
                return response()->json([
                    'success' => false,
                    'error' => $response->json()
                ], $response->status());
            }
        } catch (\Exception $e) {
            Log::error("ERROR getting Instagram media: " . $e->getMessage());
            return response()->json(['error' => $e->getMessage()], 500);
        }
    }

    /**
     * Crea un container per un post immagine
     */
    public function createImageContainer($imageUrl, $caption = '') {
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
                return response()->json([
                    'success' => true,
                    'container_id' => $data['id'],
                    'data' => $data
                ]);
            } else {
                Log::error("Failed to create Instagram image container", $response->json());
                return response()->json([
                    'success' => false,
                    'error' => $response->json()
                ], $response->status());
            }
        } catch (\Exception $e) {
            Log::error("ERROR creating Instagram image container: " . $e->getMessage());
            return response()->json(['error' => $e->getMessage()], 500);
        }
    }

    /**
     * Crea un container per un post video
     */
    public function createVideoContainer($videoUrl, $caption = '', $thumbnailUrl = null) {
        try {
            Log::info("Creating Instagram video container with URL: $videoUrl");

            $params = [
                'media_type' => 'VIDEO',
                'video_url' => $videoUrl,
                'caption' => $caption,
                'access_token' => $this->accessToken
            ];

            if ($thumbnailUrl) {
                $params['thumb_offset'] = 0; // Offset in millisecondi per il thumbnail
            }

            $response = Http::post($this->baseUrl . '/' . $this->instagramBusinessAccountId . '/media', $params);

            if ($response->successful()) {
                $data = $response->json();
                Log::info("Instagram video container created with ID: " . $data['id']);
                return response()->json([
                    'success' => true,
                    'container_id' => $data['id'],
                    'data' => $data
                ]);
            } else {
                Log::error("Failed to create Instagram video container", $response->json());
                return response()->json([
                    'success' => false,
                    'error' => $response->json()
                ], $response->status());
            }
        } catch (\Exception $e) {
            Log::error("ERROR creating Instagram video container: " . $e->getMessage());
            return response()->json(['error' => $e->getMessage()], 500);
        }
    }

    /**
     * Crea un container per un post carousel
     */
    public function createCarouselContainer($children, $caption = '') {
        try {
            Log::info("Creating Instagram carousel container with " . count($children) . " items");

            $response = Http::post($this->baseUrl . '/' . $this->instagramBusinessAccountId . '/media', [
                'media_type' => 'CAROUSEL',
                'children' => implode(',', $children), // Array di container IDs
                'caption' => $caption,
                'access_token' => $this->accessToken
            ]);

            if ($response->successful()) {
                $data = $response->json();
                Log::info("Instagram carousel container created with ID: " . $data['id']);
                return response()->json([
                    'success' => true,
                    'container_id' => $data['id'],
                    'data' => $data
                ]);
            } else {
                Log::error("Failed to create Instagram carousel container", $response->json());
                return response()->json([
                    'success' => false,
                    'error' => $response->json()
                ], $response->status());
            }
        } catch (\Exception $e) {
            Log::error("ERROR creating Instagram carousel container: " . $e->getMessage());
            return response()->json(['error' => $e->getMessage()], 500);
        }
    }

    /**
     * Crea un item del carousel (per uso nei carousel)
     */
    public function createCarouselItem($imageUrl, $isVideo = false, $videoUrl = null) {
        try {
            Log::info("Creating Instagram carousel item");

            $params = [
                'is_carousel_item' => true,
                'access_token' => $this->accessToken
            ];

            if ($isVideo && $videoUrl) {
                $params['media_type'] = 'VIDEO';
                $params['video_url'] = $videoUrl;
            } else {
                $params['image_url'] = $imageUrl;
            }

            $response = Http::post($this->baseUrl . '/' . $this->instagramBusinessAccountId . '/media', $params);

            if ($response->successful()) {
                $data = $response->json();
                Log::info("Instagram carousel item created with ID: " . $data['id']);
                return $data['id']; // Restituisce solo l'ID per l'uso nel carousel
            } else {
                Log::error("Failed to create Instagram carousel item", $response->json());
                return null;
            }
        } catch (\Exception $e) {
            Log::error("ERROR creating Instagram carousel item: " . $e->getMessage());
            return null;
        }
    }

    /**
     * Pubblica un container media
     */
    public function publishMedia($containerId) {
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
    public function checkContainerStatus($containerId) {
        try {
            Log::info("Checking Instagram container status: $containerId");

            $response = Http::get($this->baseUrl . '/' . $containerId, [
                'fields' => 'status_code,status',
                'access_token' => $this->accessToken
            ]);

            if ($response->successful()) {
                $data = $response->json();
                Log::info("Container status: " . ($data['status_code'] ?? 'unknown'));
                return response()->json([
                    'success' => true,
                    'data' => $data
                ]);
            } else {
                Log::error("Failed to check container status", $response->json());
                return response()->json([
                    'success' => false,
                    'error' => $response->json()
                ], $response->status());
            }
        } catch (\Exception $e) {
            Log::error("ERROR checking container status: " . $e->getMessage());
            return response()->json(['error' => $e->getMessage()], 500);
        }
    }

    /**
     * Helper method per creare e pubblicare un post immagine in un'unica operazione
     */
    public function createAndPublishImage($imageUrl, $caption = '') {
        try {
            Log::info("Creating and publishing Instagram image post");

            // Step 1: Crea il container
            $containerResponse = $this->createImageContainer($imageUrl, $caption);
            $containerData = json_decode($containerResponse->getContent(), true);

            if (!$containerData['success']) {
                return $containerResponse;
            }

            $containerId = $containerData['container_id'];

            // Step 2: Attendi che il container sia pronto (opzionale)
            sleep(2);

            // Step 3: Pubblica il media
            return $this->publishMedia($containerId);
        } catch (\Exception $e) {
            Log::error("ERROR in createAndPublishImage: " . $e->getMessage());
            return response()->json(['error' => $e->getMessage()], 500);
        }
    }

    /**
     * Helper method per creare e pubblicare un post video in un'unica operazione
     */
    public function createAndPublishVideo($videoUrl, $caption = '', $thumbnailUrl = null) {
        try {
            Log::info("Creating and publishing Instagram video post");

            // Step 1: Crea il container
            $containerResponse = $this->createVideoContainer($videoUrl, $caption, $thumbnailUrl);
            $containerData = json_decode($containerResponse->getContent(), true);

            if (!$containerData['success']) {
                return $containerResponse;
            }

            $containerId = $containerData['container_id'];

            // Step 2: Attendi che il video sia processato (i video richiedono più tempo)
            sleep(10);

            // Step 3: Verifica lo status del container
            $statusResponse = $this->checkContainerStatus($containerId);
            $statusData = json_decode($statusResponse->getContent(), true);

            if ($statusData['success'] && isset($statusData['data']['status_code'])) {
                if ($statusData['data']['status_code'] !== 'FINISHED') {
                    return response()->json([
                        'success' => false,
                        'error' => 'Video not ready for publishing',
                        'status' => $statusData['data']
                    ], 400);
                }
            }

            // Step 4: Pubblica il media
            return $this->publishMedia($containerId);
        } catch (\Exception $e) {
            Log::error("ERROR in createAndPublishVideo: " . $e->getMessage());
            return response()->json(['error' => $e->getMessage()], 500);
        }
    }

    /**
     * Helper method per creare e pubblicare un carousel in un'unica operazione
     */
    public function createAndPublishCarousel($items, $caption = '') {
        try {
            Log::info("Creating and publishing Instagram carousel post with " . count($items) . " items");

            // Step 1: Crea i container per ogni item del carousel
            $carouselItemIds = [];

            foreach ($items as $item) {
                $isVideo = isset($item['video_url']) && !empty($item['video_url']);
                $imageUrl = $item['image_url'] ?? '';
                $videoUrl = $item['video_url'] ?? null;

                $itemId = $this->createCarouselItem($imageUrl, $isVideo, $videoUrl);

                if ($itemId) {
                    $carouselItemIds[] = $itemId;
                } else {
                    return response()->json([
                        'success' => false,
                        'error' => 'Failed to create carousel item'
                    ], 400);
                }
            }

            // Step 2: Crea il container del carousel
            $containerResponse = $this->createCarouselContainer($carouselItemIds, $caption);
            $containerData = json_decode($containerResponse->getContent(), true);

            if (!$containerData['success']) {
                return $containerResponse;
            }

            $containerId = $containerData['container_id'];

            // Step 3: Attendi che il carousel sia pronto
            sleep(3);

            // Step 4: Pubblica il carousel
            return $this->publishMedia($containerId);
        } catch (\Exception $e) {
            Log::error("ERROR in createAndPublishCarousel: " . $e->getMessage());
            return response()->json(['error' => $e->getMessage()], 500);
        }
    }

    /**
     * Ottiene insights di un media specifico
     */
    public function getMediaInsights($mediaId, $metrics = ['impressions', 'reach', 'engagement']) {
        try {
            Log::info("Getting Instagram media insights for: $mediaId");

            $response = Http::get($this->baseUrl . '/' . $mediaId . '/insights', [
                'metric' => implode(',', $metrics),
                'access_token' => $this->accessToken
            ]);

            if ($response->successful()) {
                Log::info("Instagram media insights retrieved successfully");
                return response()->json([
                    'success' => true,
                    'data' => $response->json()
                ]);
            } else {
                Log::error("Failed to get Instagram media insights", $response->json());
                return response()->json([
                    'success' => false,
                    'error' => $response->json()
                ], $response->status());
            }
        } catch (\Exception $e) {
            Log::error("ERROR getting Instagram media insights: " . $e->getMessage());
            return response()->json(['error' => $e->getMessage()], 500);
        }
    }

    /**
     * Ottiene insights dell'account
     */
    public function getAccountInsights($metrics = ['impressions', 'reach', 'profile_views'], $period = 'day', $since = null, $until = null) {
        try {
            Log::info("Getting Instagram account insights");

            $params = [
                'metric' => implode(',', $metrics),
                'period' => $period,
                'access_token' => $this->accessToken
            ];

            if ($since) {
                $params['since'] = $since;
            }

            if ($until) {
                $params['until'] = $until;
            }

            $response = Http::get($this->baseUrl . '/' . $this->instagramBusinessAccountId . '/insights', $params);

            if ($response->successful()) {
                Log::info("Instagram account insights retrieved successfully");
                return response()->json([
                    'success' => true,
                    'data' => $response->json()
                ]);
            } else {
                Log::error("Failed to get Instagram account insights", $response->json());
                return response()->json([
                    'success' => false,
                    'error' => $response->json()
                ], $response->status());
            }
        } catch (\Exception $e) {
            Log::error("ERROR getting Instagram account insights: " . $e->getMessage());
            return response()->json(['error' => $e->getMessage()], 500);
        }
    }
}
