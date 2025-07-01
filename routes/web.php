<?php

use App\Http\Controllers\FacebookController;
use Illuminate\Support\Facades\Route;

Route::get('/', function () {
    return view('welcome');
});

Route::get('/test-post', function () {

    $page_id = config('services.facebook.page_id');
    $controller = new FacebookController();

    $controller->createPageFeedPost(
        $page_id,
        'Test post da laravel',
        [],
        [
            'link' => 'https://www.example.com',
            'picture' => 'https://www.example.com/image.jpg',
            'name' => 'Example Link',
            'caption' => 'This is a caption for the link.',
            'description' => 'This is a description for the link.',
        ]
    );
});
