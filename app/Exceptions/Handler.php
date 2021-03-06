<?php

namespace App\Exceptions;

use Exception;
use Illuminate\Database\Eloquent\ModelNotFoundException;
use Illuminate\Http\JsonResponse;
use Illuminate\Session\TokenMismatchException;
use Illuminate\Support\Facades\Config;
use Symfony\Component\Debug\Exception\FlattenException;
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
            $response = [
                'success' => false,
                'status' => $status,
                'statusName' => $status == 419 ? "Authentication Timeout" : Response::$statusTexts[$status],
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
        elseif ($e instanceof TokenMismatchException){
            $e = new HttpException(419, "Session token mismatched. Please refresh the page and try again.", $e);
        }
        elseif ($e instanceof \CAS_AuthenticationException){
            // PHPCas likes to output a ton of its own crap. ob_end_clean clears the output buffer so it won't actually get output.
            ob_end_clean();
            $e = new HttpException(Response::HTTP_BAD_GATEWAY, "There was an error contacting Eastern SSO. It may be down, or it may be misconfigured.", $e);
        }

        return parent::render($request, $e);
    }

    /**
     * Create a Symfony response for the given exception.
     *
     * @param  \Exception  $e
     * @return \Symfony\Component\HttpFoundation\Response
     */
    protected function convertExceptionToResponse(Exception $e)
    {
        try{
            $exception = FlattenException::create($e);


            $response = [
                'success' => false,
                'status' => Response::HTTP_INTERNAL_SERVER_ERROR,
                'statusName' => Response::$statusTexts[Response::HTTP_INTERNAL_SERVER_ERROR],
                'message' => config('app.debug') ? $e->getMessage() : "An unexpected error occurred.",
                'flattenException' => config('app.debug') ? $exception : null,
            ];

            if (\Request::ajax()){
                if ($response['flattenException'])
                {
                    $response['messages'] = [];
                    foreach ($response['flattenException']->getTrace() as $t) {
                        $response['messages'][] =
                            (isset($t['file']) && isset($t['line']) ? $t['file'] . ':' . $t['line'] : '') .
                            ' in function ' .
                            ($t['function'] ? $t['function'] : '???');
                    }
                }

                return new JsonResponse($response, $response['status'], []);
            }
            else{
                $content = view('error', ['response' => $response])->render();

                return new Response($content, $response['status'], []);
            }
        }
        catch(Exception $renderException){
            return parent::convertExceptionToResponse($e);
        }
    }
}
