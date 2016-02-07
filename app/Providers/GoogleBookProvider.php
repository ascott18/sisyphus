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
        $cachedValue = Cache::get('isbn-'.$isbn);

        if($cachedValue == null) {
            $newValue = json_decode(file_get_contents('https://www.googleapis.com/books/v1/volumes?q=isbn:' . $isbn . '&key='.config('app.google_api_key')));
            if($newValue != null) {
                Cache::put('isbn-'.$isbn, $newValue, static::CACHE_DURATION);
            }
            return $newValue;
        } else {
            return $cachedValue;
        }
    }

    public function getCoverThumbnail($isbn) {
        $rawSearch = $this->getRawSearchResults($isbn);

        if(isset($rawSearch->items[0]->volumeInfo->imageLinks->thumbnail)) {
            $imageURL = $rawSearch->items[0]->volumeInfo->imageLinks->thumbnail;

            $cachedImage = Cache::get($imageURL);

            if($cachedImage == null) {
                $image = file_get_contents($imageURL.config('app.google_api_key'));

                $contentType = "";
                foreach ($http_response_header as $headerLine) {
                    if(preg_match('/content-type[^-]/i', $headerLine)){
                        $contentType = preg_split("/[\s]/", $headerLine)[1];
                        break;
                    }
                }

                Cache::put($imageURL, ['image' => $image, 'contentType' => $contentType], static::CACHE_DURATION);

                return response($image, 200, ['Content-Type' => $contentType]);
            } else {
                return response($cachedImage['image'], 200, ['Content-Type' => $cachedImage['contentType']]);
            }
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
