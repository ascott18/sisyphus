<?php

namespace App\Providers;

use Illuminate\Support\ServiceProvider;

class SearchServiceProvider extends ServiceProvider
{
    /**
     * Bootstrap the application services.
     *
     * @return void
     */
    public function boot()
    {
        //
    }

    public static function sectionSearchQuery($query, $search)
    {
        $searchArray = preg_split("/[\s-]/", $search);
        foreach ($searchArray as $key => $field) {       // strip leading zeros from search terms
            $searchArray[$key] = ltrim($field, '0');
        }
        if (count($searchArray) == 1) {
            $query->where(function ($sQuery) use ($searchArray) {
                return $sQuery->where('department', 'LIKE', '%' . $searchArray[0] . '%')
                    ->orWhere('course_number', 'LIKE', '%' . $searchArray[0] . '%');
            });
        } else if (count($searchArray) == 2) {
            // we need to use an anonymous function so the subquery does not override the book_id limit from parent
            $query->where(function ($sQuery) use ($searchArray) {
                return $sQuery->where('department', 'LIKE', '%' . $searchArray[0] . '%')
                    ->where('course_number', 'LIKE', '%' . $searchArray[1] . '%')
                    ->orWhere('course_number', 'LIKE', '%' . $searchArray[0] . '%')
                    ->where('course_section', 'LIKE', '%' . $searchArray[1] . '%');
            });
        } elseif (count($searchArray) == 3) {
            // this does not suffer the same problem but should be in a subquery like it is for proper formatting
            $query->where(function ($sQuery) use ($searchArray) {
                return $sQuery->where('department', 'LIKE', '%' . $searchArray[0] . '%')
                    ->where('course_number', 'LIKE', '%' . $searchArray[1] . '%')
                    ->where('course_section', 'LIKE', '%' . $searchArray[2] . '%');
            });
        }
    }

    public static function professorSearchQuery($query, $search) {
        $query = $query->where(function($sQuery) use ($search) {
            $sQuery = $sQuery->where('users.first_name', 'LIKE', '%'.$search.'%')
                ->orWhere('users.last_name', 'LIKE', '%'.$search.'%');

            $searchArray = preg_split("/[\s,]+/", $search);
            if(count($searchArray) == 2) {
                $sQuery = $sQuery->orWhere('users.first_name', 'LIKE', '%'.$searchArray[0].'%')
                    ->where('users.last_name', 'LIKE', '%'.$searchArray[1].'%')
                    ->orWhere('users.last_name', 'LIKE', '%'.$searchArray[0].'%')
                    ->where('users.first_name', 'LIKE', '%'.$searchArray[1].'%');
            }

            return $sQuery;
        });
    }

    /**
     * Register the application services.
     *
     * @return void
     */
    public function register()
    {
        $this->app->bind('SearchHelper', function() {
            return new SearchServiceProvider;
        });
    }
}
