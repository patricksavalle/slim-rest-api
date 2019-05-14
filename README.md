## Ready-to-go REST API for PHP SLIM

_Uses PHP 7.x syntax_

Turns the default SLIM App-class into a production-grade JSON REST-API base-class:
- adds (GET, POST and header) parameter validation for added security and self-documentation
- adds robust exception and assert handling / sets-up the PHP interpreter for exceptions so 'normal' errors will throw exceptions that you can catch.
- translates unhandled exceptions into 'normal' JSON-responses with the correct HTTP-STATUS
- translates unknown routes/URL's and methods into 'normal' 403 and 404 JSON-responses
- CLI support to accept and protect for instance cronjob calls from the server
- CORS / cross-origin resource sharing support
- Middleware for database optimisation and memcache support

Very simple to use, just use the SlimRestApi-class instead of the standard Slim App-class.

Edit the _**`slim-rest-api.ini`**_ for correct (database) configuration. 

Example _**`index.php`**_:

    <?php
    
    declare(strict_types = 1);
    
    namespace YourApi;
    
    define("BASE_PATH", dirname(__FILE__));
    
    require BASE_PATH . '/vendor/autoload.php';
    
    use SlimRequestParams\BodyParameters;
    use SlimRequestParams\QueryParameters;
    use SlimRestApi\Middleware\CliRequest;
    use SlimRestApi\Middleware\Memcaching;
    use SlimRestApi\Middleware\ReadOnly;
    use SlimRestApi\SlimRestApi;
    use Psr\Http\Message\ResponseInterface;
    use Psr\Http\Message\ServerRequestInterface;
    
    class YourApi extends SlimRestApi
    {
        public function __construct()
        {
            // call parent ctor before anything else!
            parent::__construct();
    
            // Add a route
            $this->get("/echo", function (
                ServerRequestInterface $request,
                ResponseInterface $response,
                \stdClass $args)
            : ResponseInterface
            {
                return $response->withJson(QueryParameters::get());
            })
                ->add(new QueryParameters([
                    '{string:.{30}},the lazy fox',
                    '{mandatory_int:\d{1,10}}',
                    '{char:[[:alnum:]]{1}},a',
                    '{int:\int},1',
                    '{bool:\bool},true',]))
                ->add(new ReadOnly);
        }
    }

    // instantiate and run
    (new YourApi)->run();

Start the PHP server:

    php -S localhost:8000

Open this URL's in your browser (see what happens):

    http://localhost:8000/echo 
    http://localhost:8000/echo?mandatory_int=1
    http://localhost:8000/echo?mandatory_int=1&bool=false

### Setup

- Install with Composer
- Update your composer.json to require patricksavalle/slim-rest-api.
- Run composer install to add slim-rest-api your vendor folder.

        {
          "require": 
          {
            "patricksavalle/slim-rest-api": "^1.0"
          }
        }

- Include in your source.

        <?php
        require './vendor/autoload.php';

- Copy the `slim-rest-api.ini` file to the project-root and edit (put in your settings)

### Available Middleware

This packages comes with a minimum of (optional) helpers and middleware.

#### Validate parameters of a route

See: https://github.com/patricksavalle/slim-request-params 

    use SlimRequestParams\BodyParameters;
    use SlimRequestParams\QueryParameters;
    
#### Set route to read-only
    
Sets route to read-only, optimising the database engine, adding a layer of robustness by preventing unwanted updates to the database.

    use SlimRestApi\Middleware\ReadOnly;
    $YourApp->get(...)->add( new ReadOnly );
    
If you use other middleware (e.g. authentication) that needs write-access, chain them AFTER this method, like so

    use SlimRestApi\Middleware\ReadOnly;
    $YourApp->get(...)
        ->add( new ReadOnly )
        ->add( new Authentication )
        -> ...
    
#### Set route to CLI / command-line only

Very usefull for functions that should not be exposed over HTTP (such as cronjob callbacks or configuration methods).
For examples see: https://github.com/pavlakis/slim-cli
  
    use SlimRestApi\Middleware\CliRequest;
    $YourApp->get(...)->add( new CliRequest );

  
#### Add response of a route to memcache
  
So the webserver (Apache, NGINX, etc.) can first check memcache before activating the API. Uses the methods URL (path- and query-part) as key. You need to setup memcache and Apache accordingly.
    
    use SlimRestApi\Middleware\Memcaching;
    $YourApp->get(...)->add( new Memcaching );
    
### Available helpers

#### Database / PDO access

Makes database access as simple as possible. Automatically handles prepared-statement usage. Adds supersimple transaction support.

    use SlimRestApi/Db;
    $obj = Db::transaction(function(){
        $obj = Db::execute("SELECT * FROM yourtable LIMIT 1")->fetch();
        $obj->some_field = 'changed';
        Db::update($obj);
        return $obj;
    });
    
All PDO-statements are accessible through the Db-singleton (magic method). To prevent warnings use:

    /** @noinspection PhpUndefinedMethodInspection */

#### INI-file / configuration

Makes INI's as simple as possible. Looks for a 'slim-rest-api.ini' file in the webroot, see project for axeample.

    Uue SlimeRestApi/Ini;
    $value = Ini::get('key');

#### Memcaching of methods and functions

Provides a memcached version of call_user_func_array(). Use only for true functions (i.e. code without side-effects so the result depends only on the function-argument).

    use SlimRestApi/Memcache;
    $value = Memcache::call_user_func_array(...);
     
### Contributing

Fork it.

- Create your feature branch (git checkout -b my-new-feature).
- Commit your changes (git commit -am 'Added some feature').
- Push to the branch (git push origin my-new-feature).
- Create a new Pull Request.

