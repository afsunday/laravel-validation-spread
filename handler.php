<?php

namespace App\Exceptions;

use App\Traits\RespondsWithHttpStatus;
use Illuminate\Auth\Access\AuthorizationException;
use Illuminate\Database\Eloquent\ModelNotFoundException;
use Illuminate\Foundation\Exceptions\Handler as ExceptionHandler;
use Illuminate\Http\Exceptions\ThrottleRequestsException;
use Symfony\Component\HttpKernel\Exception\NotFoundHttpException;
use Symfony\Component\HttpKernel\Exception\AccessDeniedHttpException;

use Illuminate\Validation\ValidationException;
use Symfony\Component\HttpKernel\Exception\HttpException;
use Symfony\Component\HttpKernel\Exception\MethodNotAllowedHttpException;
use Throwable;
class Handler extends ExceptionHandler
{
    use RespondsWithHttpStatus;

    /**
     * The list of the inputs that are never flashed to the session on validation exceptions.
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
     * @param  \Illuminate\Http\Request  $request
     * @param  \Throwable  $e
     * @return \Symfony\Component\HttpFoundation\Response
     *
     * @throws \Throwable
     */
    public function render($request, Throwable $exception)
    {
        if ($request->is('api/*') || $request->wantsJson()) {
            if ($exception instanceof ModelNotFoundException) {
                return $this->failure('The requested resource was not found', 404);
            }

            if ($exception instanceof NotFoundHttpException) {
                return $this->failure('Invalid resource url Path', 404);
            }

            if ($exception instanceof AuthorizationException) {
                return $this->failure($exception->getMessage(), 403);
            }

            if ($exception instanceof AccessDeniedHttpException) {
                return $this->failure('This action is unauthorized.', 403);
            }

            if ($exception instanceof HttpException) {
                return $this->failure('This action is unauthorized', 403);
            }

            if ($exception instanceof ThrottleRequestsException) {
                return $this->failure('Too many attempts was made please try later.', 429);
            }

            if ($exception instanceof MethodNotAllowedHttpException) {
                return $this->failure($exception->getMessage(), 405);
            }

            if ($exception instanceof ValidationException)
                return response()->json([
                    'success' => false,
                'message' => 'Whoops!' . collect($exception->errors())->first(),
                'errors' => $exception->errors(),
            ], 422);
        }

        return parent::render($request, $exception);
    }

    /**
     * Convert a validation exception into a JSON response.
     *
     * @param  \Illuminate\Http\Request  $request
     * @param  \Illuminate\Validation\ValidationException  $exception
     * @return \Illuminate\Http\JsonResponse
     */
    protected function invalidJson($request, ValidationException $exception)
    {
        $jsonResponse = parent::invalidJson($request, $exception);

        $original = (array) $jsonResponse->getData();

        $jsonResponse->setData(array_merge($original, [
            'success' => false,
            'errors' => $original['errors'],
        ]));

        return $jsonResponse;
    }
}
