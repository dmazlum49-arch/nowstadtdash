<?php

namespace App\Exceptions;

use App\Helpers\ResponseHelper;
use Illuminate\Database\Eloquent\ModelNotFoundException;
use Illuminate\Foundation\Exceptions\Handler as ExceptionHandler;
use Illuminate\Session\TokenMismatchException;
use Symfony\Component\HttpKernel\Exception\MethodNotAllowedHttpException;
use Symfony\Component\HttpKernel\Exception\NotFoundHttpException;
use Illuminate\Validation\ValidationException;
use Throwable;

class Handler extends ExceptionHandler
{
    /**
     * A list of exception types with their corresponding custom log levels.
     *
     * @var array<class-string<\Throwable>, \Psr\Log\LogLevel::*>
     */
    protected $levels = [
        //
    ];

    /**
     * A list of the exception types that are not reported.
     *
     * @var array<int, class-string<\Throwable>>
     */
    protected $dontReport = [
        //
    ];

    /**
     * A list of the inputs that are never flashed to the session on validation exceptions.
     *
     * @var array<int, string>
     */
    protected $dontFlash = [
        'current_password',
        'password',
        'password_confirmation',
    ];

    /**
     * Register the exception handling callbacks for the application.
     */
    public function register(): void
    {
        $this->reportable(function (Throwable $e) {
            //
        });
    }

    /**
     * Render an exception into an HTTP response.
     *
     * @param \Illuminate\Http\Request $request
     * @param \Throwable $exception
     * @return \Symfony\Component\HttpFoundation\Response
     *
     * @throws \Throwable
     */

    public function render($request, Throwable $exception)
    {
        // Check if the request is for an API (e.g., URL contains 'api/')
        if (strpos($request->path(), 'api/') === 0) {

            // Handle different exception types
            if ($exception instanceof MethodNotAllowedHttpException) {
                return $this->apiResponse(405, 'The HTTP method used is not allowed for this endpoint.');
            }

            if ($exception instanceof NotFoundHttpException) {
                return $this->apiResponse(404, 'The requested resource or URL was not found. Please check the base URL.');
            }

            // Handle Token Mismatch (CSRF Token Expired)
            if ($exception instanceof TokenMismatchException) {
                return $this->apiResponse(419, 'Your session has expired. Please try again.');
            }

            // Handle Validation exceptions
            if ($exception instanceof ValidationException) {
                return $this->apiResponse(422, 'There were validation errors with your request.', $exception->errors());
            }

            if ($exception instanceof ModelNotFoundException) {
                return $this->apiResponse(404, 'The requested resource could not be found in the database');
            }

            // Generic fallback for unhandled exceptions
            return $this->apiResponse(500, 'Internal server error: ' . $exception->getMessage());
        }

        // If it's not an API request, handle it the normal way
        return parent::render($request, $exception);
    }

    /**
     * Format and return a JSON response for the API.
     *
     * @param int $status HTTP status code
     * @param string $message A human-readable message
     * @param array|null $data Any additional data to send in the response
     * @return \Illuminate\Http\JsonResponse
     */
    private function apiResponse($status, $message, $data = null)
    {
        return ResponseHelper::send($status, $message, $data ?? new \stdClass());
    }
}
