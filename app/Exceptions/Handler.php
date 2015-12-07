<?php

namespace App\Exceptions;

use Exception;
use Illuminate\Database\Eloquent\ModelNotFoundException;
use Illuminate\Support\Facades\Config;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpKernel\Exception\BadRequestHttpException;
use Symfony\Component\HttpKernel\Exception\HttpException;
use Symfony\Component\HttpKernel\Exception\NotFoundHttpException;
use Illuminate\Foundation\Exceptions\Handler as ExceptionHandler;

class Handler extends ExceptionHandler
{
    /**
     * A list of the exception types that should not be reported.
     *
     * @var array
     */
    protected $dontReport = [
        HttpException::class,
        ModelNotFoundException::class,
    ];

    /**
     * Report or log an exception.
     *
     * This is a great spot to send exceptions to Sentry, Bugsnag, etc.
     *
     * @param  \Exception  $e
     * @return void
     */
    public function report(Exception $e)
    {
        return parent::report($e);
    }

    protected $excludeCustomExceptions = [
        401,
    ];

    /**
     * Render the given HttpException.
     *
     * @param  \Symfony\Component\HttpKernel\Exception\HttpException  $e
     * @return \Symfony\Component\HttpFoundation\Response
     */
    protected function renderHttpException(HttpException $e)
    {
        $status = $e->getStatusCode();

        if (!in_array($status, $this->excludeCustomExceptions)) {
            $debug = config('app.debug');

            $response = [
                'success' => false,
                'status' => $status,
                'statusName' => Response::$statusTexts[$status],
                'message' => $e->getMessage(),
            ];

            if (\Request::ajax()){
                return response()->json(['response' => $response], $status);
            }
            else{
                return response()->view("error", ['response' => $response], $status);
            }
        } else {
            return $this->convertExceptionToResponse($e);
        }
    }

    /**
     * Render an exception into an HTTP response.
     *
     * @param  \Illuminate\Http\Request  $request
     * @param  \Exception  $e
     * @return \Illuminate\Http\Response
     */
    public function render($request, Exception $e)
    {
        if ($e instanceof ModelNotFoundException) {
            $modelClass = $e->getModel();
            $modelClassPath = preg_split('/\\\\/', $modelClass);
            $modelName = $modelClassPath[count($modelClassPath) - 1];

            $message = "$modelName not found.";

            $e = new NotFoundHttpException($message, $e);
        }
        elseif ($e instanceof \ErrorException &&
            preg_match("|Missing argument \\d+ for App\\\\Http\\\\Controllers|", $e->getMessage()))
        {
            $e = new BadRequestHttpException("URL is incomplete.", $e);
        }
        elseif ($e instanceof NotFoundHttpException && $e->getMessage() == "Controller method not found.")
        {
            $e = new NotFoundHttpException("Page not found.", $e);
        }

        return parent::render($request, $e);
    }
}
