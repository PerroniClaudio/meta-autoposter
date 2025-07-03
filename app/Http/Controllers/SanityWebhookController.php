<?php

namespace App\Http\Controllers;

use Exception;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;

class SanityWebhookController extends Controller {
    /**
     * Handle the incoming webhook notification from Sanity.
     */
    public function handle(Request $request) {
        // 1. (Optional but recommended) Validate the webhook secret for security
        $secret = config('services.sanity.webhook_secret');
        if ($secret && $request->header('X-Sanity-Signature') !== $secret) {
            return response()->json(['message' => 'Invalid signature'], 401);
        }

        // 2. Extract the post data from the webhook payload
        $post = $request->all();

        // Ensure it\'s a blog post and not another document type
        if (!isset($post['_type']) || $post['_type'] !== 'blog') {
            return response()->json(['message' => 'Document type is not a blog post.']);
        }

        // Avoid posting if the post was just unpublished or deleted
        if (isset($post['publishedAt']) && $post['publishedAt'] === null) {
            return response()->json(['message' => 'Post is not published.']);
        }

        // 3. Initialize the social media controllers
        $facebookController = new FacebookController();
        $instagramController = new InstagramController();
        $sanityController = new SanityController();

        // 4. Create and publish the posts
        try {
            // Post to Facebook
            $facebookController->createPost(
                config('services.facebook.page_id'),
                $post['title'] . "\n\n\n\n" . ($post['smallDescription'] ?? ''),
                'https://news.integys.com/news/' . $post['slug']['current']
            );

            // Post to Instagram
            $imageUrl = $sanityController->buildUrl($post['titleImage']['asset']['_ref']);
            $instagramCaption = $post['title'] . "\n\n" . $post['smallDescription'] . "\n\n Scopri di più consultando il nostro sito web, l'indirizzo è nella biografia del profilo Instagram.";

            $instagramController->createAndPublishImage(
                $imageUrl,
                $instagramCaption
            );
        } catch (Exception $e) {
            // Log the error if something goes wrong
            Log::error('Failed to post to social media: ' . $e->getMessage());
            return response()->json(['message' => 'Error processing post'], 500);
        }

        return response()->json(['message' => 'Post processed successfully']);
    }
}
