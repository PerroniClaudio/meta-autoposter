<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Sanity\Client as SanityClient;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Storage;

class SanityController extends Controller {
    //

    private $sanityClient;

    public function __construct() {
        $sanityClient = new SanityClient([
            'projectId' => config('services.sanity.project_id'),
            'dataset' => config('services.sanity.dataset'),
            'useCdn' => false,
            'apiVersion' => config('services.sanity.api_version'),

            'token' => config('services.sanity.token'),
        ]);
    }

    public function downloadImage($imageRef) {
        try {
            $assetParts = explode('-', $imageRef);
            $filename = $assetParts[1] . '-' . $assetParts[2] . '.' . $assetParts[3];
            $imageUrl = $this->buildUrl($imageRef);
            $imageContents = Http::get($imageUrl)->body();
            Storage::disk('public')->put('images/' . $filename, $imageContents);

            return Storage::url('images/' . $filename);
        } catch (\Exception $e) {
            return response()->json(['error' => $e->getMessage()], 500);
        }
    }

    public function buildUrl($imageRef) {
        $assetParts = explode('-', $imageRef);
        $filename = $assetParts[1] . '-' . $assetParts[2] . '.' . $assetParts[3];

        $projectId = config('services.sanity.project_id');
        $dataset = config('services.sanity.dataset');

        $imageUrl = "https://cdn.sanity.io/images/{$projectId}/{$dataset}/{$filename}";

        return $imageUrl;
    }
}
