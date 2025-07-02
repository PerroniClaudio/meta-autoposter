<?php

use App\Http\Controllers\FacebookController;
use App\Http\Controllers\InstagramController;
use Illuminate\Support\Facades\Route;
use Illuminate\Support\Facades\Log;
use FacebookAds\Object\Page;

Route::get('/', function () {
    return view('welcome');
});
