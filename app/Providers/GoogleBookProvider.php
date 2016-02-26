<?php

namespace App\Providers;

use Illuminate\Http\Response;
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


    /**
     * gets the book information from the google API
     * and caches it if we don't already have it
     *
     * @param $isbn
     * @return mixed
     */
    public function getRawSearchResults($isbn) {
        $cachedValue = Cache::get('isbn-'.$isbn);

        if ($cachedValue == null) {
            $apiURL = 'https://www.googleapis.com/books/v1/volumes?q=isbn:' . $isbn;
            if (config('app.google_api_key') != '') {
                $apiURL .= '&key='.config('app.google_api_key');
            }

            $newValue = json_decode(file_get_contents($apiURL));

            if ($newValue != null) {
                Cache::put('isbn-'.$isbn, $newValue, static::CACHE_DURATION);
            }

            return $newValue;
        } else {
            return $cachedValue;
        }
    }

    /**
     * returns the cover image and caches it if we have not already
     *
     * @param $isbn
     * @return Response
     */
    public function getCoverThumbnail($isbn) {
        $rawSearch = $this->getRawSearchResults($isbn);

        if (isset($rawSearch->items[0]->volumeInfo->imageLinks->thumbnail)) {
            $imageURL = $rawSearch->items[0]->volumeInfo->imageLinks->thumbnail;

            $cachedImage = Cache::get($imageURL);

            if ($cachedImage == null) {
                $image = file_get_contents($imageURL.config('app.google_api_key'));

                $contentType = "";
                foreach ($http_response_header as $headerLine) {
                    if (preg_match('/content-type[^-]/i', $headerLine)){
                        $contentType = preg_split("/[\s]/", $headerLine)[1];
                        break;
                    }
                }

                Cache::put($imageURL, ['image' => $image, 'contentType' => $contentType], static::CACHE_DURATION);

                return response($image,
                    Response::HTTP_OK,
                    ['Content-Type' => $contentType]);
            } else {
                return response($cachedImage['image'],
                    Response::HTTP_OK,
                    ['Content-Type' => $cachedImage['contentType']]);
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
