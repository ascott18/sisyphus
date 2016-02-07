<?php

namespace App\Providers;

use Illuminate\Support\ServiceProvider;
use Cache;

class GoogleBookProvider extends ServiceProvider
{
    /**
     * The duration, in minutes, for storing search results and images in the cache
     * 43800 minutes = 1 Month
     */
    const CACHE_DURATION = 43800;

    /**
     * Bootstrap the application services.
     *
     * @return void
     */
    public function boot()
    {
        //
    }

    public function getRawSearchResults($isbn) {
        return Cache::remember('isbn-'.$isbn, static::CACHE_DURATION, function() use ($isbn) {
           return json_decode(file_get_contents("https://www.googleapis.com/books/v1/volumes?q=isbn:" . $isbn));
        });
    }

    public function getCoverThumbnail($isbn) {
        $rawSearch = $this->getRawSearchResults($isbn);

        if(isset($rawSearch->items[0]->volumeInfo->imageLinks->thumbnail)) {
            $imageURL = $rawSearch->items[0]->volumeInfo->imageLinks->thumbnail;
            return Cache::remember($imageURL, static::CACHE_DURATION, function() use ($imageURL) {
                $image = file_get_contents($imageURL);
                $contentType = preg_grep('/content-type/i', $http_response_header);
                dd($contentType);
                return response(file_get_contents(public_path('images/coverNotAvailable.jpg')), 200, ['Content-Type' => 'image/jpeg']);
                //return file_get_contents($imageURL);
            });
        } else {
            return response(file_get_contents(public_path('images/coverNotAvailable.jpg')), 200, ['Content-Type' => 'image/jpeg']);
        }
    }

    /**
     * Register the application services.
     *
     * @return void
     */
    public function register()
    {
        $this->app->singleton('GoogleBooks', function ($app) {
            return $this;
        });
    }
}
