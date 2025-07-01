<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;
use FacebookAds\Api;
use FacebookAds\Object\Page;
use FacebookAds\Object\PagePost;
use FacebookAds\Object\Photo;
use FacebookAds\Object\ProfilePictureSource;
use FacebookAds\Object\User;


class FacebookController extends Controller {
    //

    private $api;

    public function __construct() {
        $this->api = Api::init(
            config('services.facebook.app_id'),
            config('services.facebook.app_secret'),
            config('services.facebook.token')
        );
    }

    /**
     * Ottiene il feed di una pagina Facebook
     */
    public function getPageFeed($pageId, $fields = [], $params = []) {
        try {
            $response = (new Page($pageId))->getFeed($fields, $params);
            return response()->json($response->getResponse()->getContent());
        } catch (\Exception $e) {
            return response()->json(['error' => $e->getMessage()], 500);
        }
    }

    /**
     * Crea un post nel feed di una pagina Facebook
     */
    public function createPageFeedPost($pageId, $message, $fields = [], $additionalParams = []) {

        Log::info("Creating post for page ID: $pageId with message: $message");

        // Prima ottieni e imposta il token della pagina
        if (!$this->setPageAccessToken($pageId)) {
            Log::warning("Could not set page access token, using current token");
        }

        $params = array_merge([
            'message' => $message,
        ], $additionalParams);

        // Rimuovi parametri che potrebbero causare l'errore #100
        $forbiddenParams = ['picture', 'name', 'thumbnail', 'description'];
        foreach ($forbiddenParams as $param) {
            if (isset($params[$param])) {
                Log::warning("Removing forbidden parameter '$param'");
                unset($params[$param]);
            }
        }

        Log::info("Parameters being sent", $params);

        $response = (new Page($pageId))->createFeed($fields, $params);

        $responseData = $response->exportAllData();

        if (isset($responseData['id'])) {
            Log::info("SUCCESS: Post created with ID: " . $responseData['id']);
            return response()->json([
                'success' => true,
                'post_id' => $responseData['id'],
                'data' => $responseData
            ]);
        } else {
            Log::warning("No post ID returned");
            Log::debug("Full response", $responseData);
            return response()->json([
                'success' => false,
                'data' => $responseData
            ]);
        }
    }

    /**
     * Crea un post nel feed con immagine e call-to-action
     */
    public function createPageFeedPostWithImage($pageId, $message, $callToAction = null, $fields = []) {
        try {
            $params = [
                'message' => $message,
            ];

            if ($callToAction) {
                $params['call_to_action'] = $callToAction;
            }

            $response = (new Page($pageId))->createFeed($fields, $params);
            return response()->json($response->exportAllData());
        } catch (\Exception $e) {
            return response()->json(['error' => $e->getMessage()], 500);
        }
    }

    /**
     * Ottiene informazioni su una pagina e le sue location
     */
    public function getPageLocations($pageId, $limit = 30000) {
        try {
            $fields = [
                'location{latitude',
                'longitude}',
                'is_permanently_closed',
            ];
            $params = [
                'limit' => $limit,
            ];

            $response = (new Page($pageId))->getLocations($fields, $params);
            return response()->json($response->getResponse()->getContent());
        } catch (\Exception $e) {
            return response()->json(['error' => $e->getMessage()], 500);
        }
    }

    /**
     * Ottiene le foto di una pagina Facebook
     */
    public function getPagePhotos($pageId, $fields = [], $params = []) {
        try {
            $response = (new Page($pageId))->getPhotos($fields, $params);
            return response()->json($response->getResponse()->getContent());
        } catch (\Exception $e) {
            return response()->json(['error' => $e->getMessage()], 500);
        }
    }

    /**
     * Carica una foto su una pagina Facebook
     */
    public function createPagePhoto($pageId, $imageUrl, $published = false, $fields = []) {
        try {
            $params = [
                'url' => $imageUrl,
                'published' => $published ? 'true' : 'false',
            ];

            $response = (new Page($pageId))->createPhoto($fields, $params);
            return response()->json($response->exportAllData());
        } catch (\Exception $e) {
            return response()->json(['error' => $e->getMessage()], 500);
        }
    }

    /**
     * Ottiene i post di una pagina Facebook
     */
    public function getPagePosts($pageId, $fields = [], $params = []) {
        try {
            $response = (new Page($pageId))->getPosts($fields, $params);
            return response()->json($response->getResponse()->getContent());
        } catch (\Exception $e) {
            return response()->json(['error' => $e->getMessage()], 500);
        }
    }

    /**
     * Crea un post carousel su una pagina Facebook
     */
    public function createPageCarouselPost($pageId, $message, $childAttachments, $link, $caption = '', $published = false) {
        try {
            $params = [
                'message' => $message,
                'published' => $published ? '1' : '0',
                'child_attachments' => $childAttachments,
                'caption' => $caption,
                'link' => $link,
            ];

            $response = (new Page($pageId))->getPosts([], $params);
            return response()->json($response->getResponse()->getContent());
        } catch (\Exception $e) {
            return response()->json(['error' => $e->getMessage()], 500);
        }
    }

    /**
     * Ottiene l'immagine profilo di una pagina Facebook
     */
    public function getPagePicture($pageId, $redirect = false, $fields = []) {
        try {
            $params = [
                'redirect' => $redirect ? '1' : '0',
            ];

            $response = (new Page($pageId))->getPicture($fields, $params);
            return response()->json($response->getResponse()->getContent());
        } catch (\Exception $e) {
            return response()->json(['error' => $e->getMessage()], 500);
        }
    }

    /**
     * Ottiene i ruoli degli utenti di una pagina Facebook
     */
    public function getPageRoles($pageId, $fields = [], $params = []) {
        try {
            $response = (new Page($pageId))->getRoles($fields, $params);
            return response()->json($response->getResponse()->getContent());
        } catch (\Exception $e) {
            return response()->json(['error' => $e->getMessage()], 500);
        }
    }

    /**
     * Sottoscrive un'app ai webhook di una pagina
     */
    public function subscribePageApp($pageId, $subscribedFields = 'leadgen', $fields = []) {
        try {
            $params = [
                'subscribed_fields' => $subscribedFields,
            ];

            $response = (new Page($pageId))->createSubscribedApp($fields, $params);
            return response()->json($response->exportAllData());
        } catch (\Exception $e) {
            return response()->json(['error' => $e->getMessage()], 500);
        }
    }

    /**
     * Ricerca pagine per posizione geografica
     */
    public function searchPagesByLocation($pageId, $latitude, $longitude, $type = 'adradiussuggestion') {
        try {
            $fields = [];
            $params = [
                'type' => $type,
                'latitude' => $latitude,
                'longitude' => $longitude,
            ];

            $response = (new Page($pageId))->getSelf($fields, $params);
            return response()->json($response->exportAllData());
        } catch (\Exception $e) {
            return response()->json(['error' => $e->getMessage()], 500);
        }
    }

    /**
     * Ottiene informazioni su un singolo post di una pagina
     */
    public function getPagePostDetails($postId, $fields = [], $params = []) {
        try {
            $response = (new PagePost($postId))->getSelf($fields, $params);
            return response()->json($response->exportAllData());
        } catch (\Exception $e) {
            return response()->json(['error' => $e->getMessage()], 500);
        }
    }

    /**
     * Helper method per creare un esempio di carousel con prodotti
     */
    public function createProductCarousel($pageId, $message, $products, $websiteUrl, $caption = '') {
        $childAttachments = [];

        foreach ($products as $product) {
            $childAttachments[] = [
                'link' => $product['link'] ?? $websiteUrl,
                'name' => $product['name'] ?? 'Product',
                'description' => $product['description'] ?? '$0.00',
                'image_hash' => $product['image_hash'] ?? '',
            ];
        }

        return $this->createPageCarouselPost(
            $pageId,
            $message,
            $childAttachments,
            $websiteUrl,
            $caption,
            false
        );
    }

    /**
     * Helper method per creare un post con call-to-action personalizzata
     */
    public function createPostWithCallToAction($pageId, $message, $ctaType = 'BUY_NOW', $link = '', $appLink = '') {
        $callToAction = [
            'type' => $ctaType,
            'value' => [
                'link' => $link,
            ]
        ];

        if ($appLink) {
            $callToAction['value']['app_link'] = $appLink;
        }

        return $this->createPageFeedPostWithImage($pageId, $message, $callToAction);
    }

    /**
     * Ottiene il token di accesso per una pagina specifica
     * 
     * @param string $pageId ID della pagina Facebook
     * @return string|null Token di accesso della pagina o null se non trovato
     */
    public function getPageAccessToken($pageId) {
        try {
            Log::info("Getting access token for page: $pageId");

            $page = new Page($pageId);
            $pageData = $page->getSelf(['access_token', 'name', 'id'])->exportAllData();

            Log::debug("Page data retrieved", $pageData);

            if (isset($pageData['access_token'])) {
                Log::info("Page access token found");
                return $pageData['access_token'];
            } else {
                Log::warning("No access token found in page data");
                return null;
            }
        } catch (\Exception $e) {
            Log::error("ERROR getting page token: " . $e->getMessage());
            return null;
        }
    }

    /**
     * Aggiorna l'API con il token specifico della pagina
     * 
     * @param string $pageId ID della pagina Facebook
     * @return bool True se il token è stato aggiornato con successo
     */
    public function setPageAccessToken($pageId) {
        try {
            $pageToken = $this->getPageAccessToken($pageId);

            if ($pageToken) {
                Log::info("Setting API to use page access token");

                // Reinizializza l'API con il token della pagina
                $this->api = Api::init(
                    config('services.facebook.app_id'),
                    config('services.facebook.app_secret'),
                    $pageToken
                );

                Log::info("API successfully updated with page token");
                return true;
            } else {
                Log::warning("Could not retrieve page access token");
                return false;
            }
        } catch (\Exception $e) {
            Log::error("ERROR setting page access token: " . $e->getMessage());
            return false;
        }
    }

    /**
     * Ottiene tutte le pagine associate all'utente corrente con i loro token
     * 
     * @return array Lista delle pagine con i loro dettagli
     */
    public function getUserPages() {
        try {
            Log::info("Getting user pages");

            $user = new User('me');
            $pages = $user->getAccounts(['id', 'name', 'access_token', 'category', 'tasks'])->getResponse()->getContent();

            Log::info("Found " . count($pages['data']) . " pages");

            return response()->json($pages);
        } catch (\Exception $e) {
            Log::error("ERROR getting user pages: " . $e->getMessage());
            return response()->json(['error' => $e->getMessage()], 500);
        }
    }

    /**
     * Verifica i permessi del token per una pagina specifica
     * 
     * @param string $pageId ID della pagina Facebook
     * @return array Informazioni sui permessi della pagina
     */
    public function checkPagePermissions($pageId) {
        try {
            Log::info("Checking permissions for page: $pageId");

            // Prima ottieni il token della pagina
            $pageToken = $this->getPageAccessToken($pageId);

            if ($pageToken) {
                // Controlla i permessi con il token della pagina
                $page = new Page($pageId);
                $pageData = $page->getSelf([
                    'id',
                    'name',
                    'category',
                    'access_token',
                    'tasks',
                    'permissions'
                ])->exportAllData();

                Log::debug("Page permissions data", $pageData);

                return response()->json([
                    'success' => true,
                    'page' => $pageData,
                    'has_token' => !empty($pageData['access_token'])
                ]);
            } else {
                return response()->json([
                    'success' => false,
                    'error' => 'Could not retrieve page access token'
                ], 400);
            }
        } catch (\Exception $e) {
            Log::error("ERROR checking page permissions: " . $e->getMessage());
            return response()->json(['error' => $e->getMessage()], 500);
        }
    }
}
