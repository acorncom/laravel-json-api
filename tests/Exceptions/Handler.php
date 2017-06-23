<?php

namespace CloudCreativity\LaravelJsonApi\Tests\Exceptions;

use CloudCreativity\LaravelJsonApi\Exceptions\HandlesErrors;
use Exception;
use Illuminate\Contracts\Container\Container;
use Orchestra\Testbench\Exceptions\Handler as BaseHandler;

class Handler extends BaseHandler
{

    use HandlesErrors;

    /**
     * Handler constructor.
     *
     * @param Container $container
     */
    public function __construct(Container $container)
    {
        parent::__construct($container);
    }

    /**
     * @param Exception $e
     */
    public function report(Exception $e)
    {
        parent::report($e);
    }

    /**
     * @param \Illuminate\Http\Request $request
     * @param \Exception $e
     * @return \Illuminate\Http\Response
     */
    public function render($request, \Exception $e)
    {
        if ($this->isJsonApi()) {
            return $this->renderJsonApi($request, $e);
        }

        return parent::render($request, $e);
    }
}
