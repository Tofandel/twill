<?php

namespace A17\Twill\Exceptions;

use A17\Twill\Facades\TwillRoutes;
use Illuminate\Contracts\Container\Container;
use Illuminate\Foundation\Exceptions\Handler as ExceptionHandler;
use Illuminate\Validation\ValidationException;
use Symfony\Component\HttpKernel\Exception\HttpExceptionInterface;

/** @deprecated It is not needed anymore and will be removed in v4 */
class Handler extends ExceptionHandler
{
    public function __construct(Container $container)
    {
        parent::__construct($container);

        trigger_deprecation('area17/twill', '3.4', 'The Twill Exception handler is deprecated and will be removed in v4');
    }

    /**
     * Get the view used to render HTTP exceptions.
     *
     * @return string
     */
    protected function getHttpExceptionView(HttpExceptionInterface $e)
    {
        return $this->getTwillErrorView($e->getStatusCode(), !TwillRoutes::isTwillRequest());
    }

    /**
     * Get the Twill error view used to render a specified HTTP status code.
     *
     * @param  integer $statusCode
     * @return string
     */
    protected function getTwillErrorView($statusCode, $frontend = false)
    {
        if ($frontend) {
            $view = config('twill.frontend.views_path') . ".errors.$statusCode";

            return view()->exists($view) ? $view : "errors::{$statusCode}";
        }

        $view = "twill.errors.$statusCode";

        return view()->exists($view) ? $view : "twill::errors.$statusCode";
    }

    protected function invalidJson($request, ValidationException $exception)
    {
        return response()->json($exception->errors(), $exception->status);
    }
}
