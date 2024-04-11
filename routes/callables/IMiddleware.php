<?php

namespace WebApiCore\Routes\Callables;

use WebApiCore\Http\HttpRequest;

interface IMiddleware
{
    /**
     * @param HttpRequest $request contains request's data propagated through all middlewares using `$next` function
     * @param callable $next is used to invoke another middleware within endpoint's request. 
     * The request of type `HttpRequest` is passed as a parameter.
     */
    public function invoke(HttpRequest $request, callable $next);
}
