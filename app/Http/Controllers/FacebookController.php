<?php

namespace App\Http\Controllers;

use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;

class FacebookController extends Controller {
    private $baseUrl;
    private $accessToken;

    public function __construct() {
        $this->accessToken = config('services.facebook.token');
        $this->baseUrl = 'https://graph.facebook.com/' . config('services.facebook.api_version', 'v23.0');
    }

    /**
     * Pubblica un post di testo o con un link sulla bacheca di una Pagina Facebook.
     *
     * @param string $pageId
     * @param string $message
     * @param string|null $link
     * @return \Illuminate\Http\JsonResponse
     */
    public function createPost($pageId, $message, $link = null) {
        Log::info("Creazione di un post sulla pagina Facebook: {$pageId}");

        try {
            $params = [
                'message' => $message,
                'access_token' => $this->getPageAccessToken($pageId) ?? $this->accessToken,
            ];

            if ($link) {
                $params['link'] = $link;
            }

            $response = Http::post("{$this->baseUrl}/{$pageId}/feed", $params);

            if ($response->successful()) {
                Log::info("Post su Facebook creato con successo.", ['response' => $response->json()]);
                return response()->json(['success' => true, 'data' => $response->json()]);
            } else {
                Log::error("Errore nella creazione del post su Facebook.", ['response' => $response->json()]);
                return response()->json(['success' => false, 'error' => $response->json()], $response->status());
            }
        } catch (\Exception $e) {
            Log::error("Eccezione nella creazione del post su Facebook: " . $e->getMessage());
            return response()->json(['success' => false, 'error' => $e->getMessage()], 500);
        }
    }

    /**
     * Pubblica una foto sulla bacheca di una Pagina Facebook.
     *
     * @param string $pageId
     * @param string $imageUrl
     * @param string|null $caption
     * @return \Illuminate\Http\JsonResponse
     */
    public function createPhotoPost($pageId, $imageUrl, $caption = null) {
        Log::info("Pubblicazione di una foto sulla pagina Facebook: {$pageId}");

        try {
            $params = [
                'url' => $imageUrl,
                'caption' => $caption,
                'access_token' => $this->getPageAccessToken($pageId) ?? $this->accessToken,
            ];

            $response = Http::post("{$this->baseUrl}/{$pageId}/photos", $params);

            if ($response->successful()) {
                Log::info("Foto pubblicata su Facebook con successo.", ['response' => $response->json()]);
                return response()->json(['success' => true, 'data' => $response->json()]);
            } else {
                Log::error("Errore nella pubblicazione della foto su Facebook.", ['response' => $response->json()]);
                return response()->json(['success' => false, 'error' => $response->json()], $response->status());
            }
        } catch (\Exception $e) {
            Log::error("Eccezione nella pubblicazione della foto su Facebook: " . $e->getMessage());
            return response()->json(['success' => false, 'error' => $e->getMessage()], 500);
        }
    }

    /**
     * Ottiene un token di accesso specifico per la pagina.
     * Questo è utile se l'utente ha concesso permessi a livello di pagina.
     *
     * @param string $pageId
     * @return string|null
     */
    private function getPageAccessToken($pageId) {
        Log::info("Recupero del token di accesso per la pagina: {$pageId}");

        try {
            $response = Http::get("{$this->baseUrl}/{$pageId}", [
                'fields' => 'access_token',
                'access_token' => $this->accessToken,
            ]);

            if ($response->successful() && isset($response->json()['access_token'])) {
                Log::info("Token di accesso per la pagina recuperato con successo.");
                return $response->json()['access_token'];
            }

            Log::warning("Impossibile recuperare un token di accesso specifico per la pagina {$pageId}. Verrà usato il token utente.");
            return null;
        } catch (\Exception $e) {
            Log::error("Errore durante il recupero del token di accesso per la pagina: " . $e->getMessage());
            return null;
        }
    }
}
