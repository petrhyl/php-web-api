# REST Web API - PHP

This is a framework with simple core functionalities from creating RESTful web API in PHP language.

## Core functionalities
1. DI container
   - enables binding factory function to call new instance of a class
   - factory function contains 'ServiceProvider' class as a parameter to call already bound classes or create new class instance independent of the container

2. Endpoint routes builder
   - maps url path to an endpoint's class that will be called on request - using methods named as same as http methods
   - endpoint class has to contain __invoke() magic method - may contain three parameters:
     1. parameter that has to be named as 'payload' - this is request object
     2. parameter that has to be named as 'query' - this is object created from url query params
     3. parameter that has to be named as parameter given in endpoint path definition
   - enables attaching middleware classes to each endpoint
  
3. App class
   - enables attaching middleware's instances or classes for every incoming requests - (it can be used for user authentication)
   - contains method 'run()' for processing an incoming request
   - finds particular endpoint for the incoming request
   - resolves endpoint class - its constructor's parameters and '__invoke()' magic method parameters
   - initials request object that is passed through all attached middlewares
