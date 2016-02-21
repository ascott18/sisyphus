<?php

namespace App\Providers;

use App\Models\Listing;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Pagination\LengthAwarePaginator;
use Illuminate\Pagination\Paginator;
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

    /**
     * Adds a search to a query that has the course listings joined
     *
     * @param $query
     * @param $search
     */
    public static function sectionSearchQuery($query, $search)
    {
        $searchArray = preg_split("/[\s-]/", $search);
        foreach ($searchArray as $key => $field) {       // strip leading zeros from search terms
            $searchArray[$key] = trim(ltrim($field, '0'));
        }

        $query->where(function ($sQuery) use ($searchArray) {

            if (count($searchArray) == 1) {
                if (!is_numeric($searchArray[0]))
                    $sQuery->where('department', 'LIKE', '%' . $searchArray[0] . '%');
                else
                    $sQuery->where('number', 'LIKE', '%' . $searchArray[0] . '%');
            }

            elseif (count($searchArray) == 2){
                if (!is_numeric($searchArray[0])) {
                    $sQuery->where('department', 'LIKE', '%' . $searchArray[0] . '%')
                           ->where('number', 'LIKE', '%' . $searchArray[1] . '%');
                }
                else {
                    $sQuery->Where('number', 'LIKE', '%' . $searchArray[0] . '%')
                           ->where('section', 'LIKE', '%' . $searchArray[1] . '%');
                }
            }

            elseif (count($searchArray) == 3) {
                $sQuery->where('department', 'LIKE', '%' . $searchArray[0] . '%')
                    ->where('number', 'LIKE', '%' . $searchArray[1] . '%')
                    ->where('section', 'LIKE', '%' . $searchArray[2] . '%');
            }
        });
    }

    /**
     * Adds a search for a professor name to a query that
     * has professors already joined.
     *
     * @param $query
     * @param $search
     */
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
     *
     * Checks for which columns to sort based on predicate column pairs passed
     * from the calling function.
     *
     * @param $query
     * @param $sortInput
     * @param $sorts
     */

    public static function buildSortQuery($query, $sortInput, $sorts) {
        $order = $sortInput->reverse ? "desc" : "asc";

        if(isset($sorts[$sortInput->predicate])) {
            $cols = $sorts[$sortInput->predicate];
            for($i = 0; $i< count($cols); $i+=2) {
                $query->orderBy($cols[$i],  $cols[$i+1] ? $cols[$i+1] : $order);
            }
        }
    }


    /**
     *
     * A (sometimes) better version of $query->paginate() that can properly determine
     * the number of pages in a result set when there are distinct clauses in the query.
     * Default behavior is to perform a count on the model's primary key. The count will
     * be distinct if the query coming is is distinct. If different columns need to be counted,
     * pass those in as the columns parmeter.
     *
     * @param Builder $query The Eloquent query to paginate.
     * @param int $perPage The number of results in each page.
     * @param array $columns The columns to perform the count on. Will be the model's primary key by default.
     * @param string $pageName
     * @param int|null  $page
     * @return LengthAwarePaginator
     */
    public static function paginate(Builder $query, $perPage = 15, $columns = null, $pageName = 'page', $page = null)
    {
        $page = $page ?: Paginator::resolveCurrentPage($pageName);

        $total = static::getCountForPagination($query, $columns);

        $results = $query->forPage($page, $perPage)->get();

        return new LengthAwarePaginator($results, $total, $perPage, $page, [
            'path' => Paginator::resolveCurrentPath(),
            'pageName' => $pageName,
        ]);
    }

    /**
     * Modified from \Illuminate\Database\Query\Builder->getCountForPagination
     *
     * @param Builder $query
     * @param null $columns
     * @return int
     */
    private static function getCountForPagination(Builder $query, $columns = null)
    {
        if (!$columns)
            $columns = [$query->getModel()->getQualifiedKeyName()];

        $query = $query->toBase();

        $backups = [];
        $bindingBackups = [];

        foreach (['orders', 'limit', 'offset'] as $field) {
            $backups[$field] = $query->{$field};
            $query->{$field} = null;
        }
        foreach (['order'] as $key) {
            $bindingBackups[$key] = $query->getRawBindings()[$key];
            $query->getRawBindings()[$key] = [];
        }

        $count = $query->count($columns);

        foreach (['orders', 'limit', 'offset'] as $field) {
            $query->{$field} = $backups[$field];
        }
        foreach (['order'] as $key) {
            $query->getRawBindings()[$key] = $bindingBackups[$key];
        }

        return $count;
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
