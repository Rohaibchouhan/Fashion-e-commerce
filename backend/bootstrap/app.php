<?php

use App\Http\Middleware\AdminStaffMiddleware;
use App\Http\Middleware\AuthenticateSession;
use App\Http\Middleware\EnsureUserIsActive;
use App\Http\Middleware\PermissionMiddleware;
use App\Http\Middleware\RoleMiddleware;
use Illuminate\Database\Eloquent\ModelNotFoundException;
use Illuminate\Database\QueryException;
use Illuminate\Foundation\Application;
use Illuminate\Foundation\Configuration\Exceptions;
use Illuminate\Foundation\Configuration\Middleware;
use Illuminate\Http\Request;
use Illuminate\Session\TokenMismatchException;
use Illuminate\Support\Facades\Log;
use Symfony\Component\HttpKernel\Exception\AccessDeniedHttpException;
use Symfony\Component\HttpKernel\Exception\HttpException;
use Symfony\Component\HttpKernel\Exception\MethodNotAllowedHttpException;
use Symfony\Component\HttpKernel\Exception\NotFoundHttpException;

return Application::configure(basePath: dirname(__DIR__))
    ->withRouting(
        web: __DIR__.'/../routes/web.php',
        api: __DIR__.'/../routes/api.php',
        commands: __DIR__.'/../routes/console.php',
        channels: __DIR__.'/../routes/channels.php',
        health: '/up',
    )
    ->withMiddleware(function (Middleware $middleware): void {
        $middleware->validateCsrfTokens(except: [
            'api/*',
        ]);
        $middleware->authenticateSessions();
        $middleware->redirectGuestsTo(fn () => route('admin.login'));

        $middleware->alias([
            'auth.session' => AuthenticateSession::class,
            'role' => RoleMiddleware::class,
            'permission' => PermissionMiddleware::class,
            'admin.staff' => AdminStaffMiddleware::class,
            'active.user' => EnsureUserIsActive::class,
        ]);
    })
    ->withExceptions(function (Exceptions $exceptions): void {
        $handleExpired = function (Request $request) {
            if ($request->is('admin*')) {
                return redirect()->route('admin.login')->withErrors([
                    'email' => 'Your security session expired. Please sign in again.',
                ]);
            }

            if ($request->is('api/*') || $request->expectsJson()) {
                return response()->json([
                    'success' => false,
                    'message' => 'CSRF session token expired. Please reload and try again.',
                ], 419);
            }

            return redirect()->back()
                ->withInput($request->except('_token', 'password', 'password_confirmation'))
                ->withErrors(['session' => 'Your session expired. Please try again.']);
        };

        $exceptions->render(function (TokenMismatchException $e, Request $request) use ($handleExpired) {
            return $handleExpired($request);
        });

        $exceptions->render(function (HttpException $e, Request $request) use ($handleExpired) {
            if ($e->getStatusCode() === 419) {
                return $handleExpired($request);
            }

            if ($e->getStatusCode() === 403 && ($request->is('api/*') || $request->expectsJson())) {
                return response()->json([
                    'success' => false,
                    'message' => $e->getMessage() ?: 'Unauthorized access.',
                ], 403);
            }
        });

        $exceptions->render(function (NotFoundHttpException $e, Request $request) {
            if ($request->is('api/*') || $request->expectsJson()) {
                return response()->json([
                    'success' => false,
                    'message' => 'Resource not found.',
                ], 404);
            }
        });

        $exceptions->render(function (MethodNotAllowedHttpException $e, Request $request) {
            if ($request->is('api/*') || $request->expectsJson()) {
                return response()->json([
                    'success' => false,
                    'message' => 'The HTTP method for this request is not allowed.',
                ], 405);
            }

            if ($request->is('admin*')) {
                return redirect()->route('admin.products')->with('error', 'The requested action or method is not allowed.');
            }
        });

        $exceptions->render(function (AccessDeniedHttpException $e, Request $request) {
            if ($request->is('api/*') || $request->expectsJson()) {
                return response()->json([
                    'success' => false,
                    'message' => $e->getMessage() ?: 'Unauthorized access.',
                ], 403);
            }
        });

        $exceptions->render(function (ModelNotFoundException $e, Request $request) {
            if ($request->is('api/*') || $request->expectsJson()) {
                return response()->json([
                    'success' => false,
                    'message' => 'The requested record could not be found.',
                ], 404);
            }

            if ($request->is('admin*')) {
                return redirect()->route('admin.users')->with('error', 'The requested record could not be found.');
            }
        });

        $exceptions->render(function (QueryException $e, Request $request) {
            Log::error('Database Query Exception: ' . $e->getMessage());

            if ($request->is('api/*') || $request->expectsJson()) {
                $isDuplicate = str_contains($e->getMessage(), 'Duplicate entry') || $e->getCode() === '23000';
                return response()->json([
                    'success' => false,
                    'message' => $isDuplicate
                        ? 'A record with these unique details already exists in the system.'
                        : 'A database error occurred while processing your request.',
                ], 422);
            }

            if ($request->is('admin*')) {
                return redirect()->back()
                    ->withInput()
                    ->with('error', 'A database operation error occurred. Details have been logged securely.');
            }
        });
    })->create();
