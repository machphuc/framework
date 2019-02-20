<?php

namespace Themosis\Route;

use Illuminate\Container\Container;
use Illuminate\Contracts\Events\Dispatcher;
use Illuminate\Routing\Router as IlluminateRouter;

class Router extends IlluminateRouter
{
    /**
     * WordPress conditions.
     *
     * @var array
     */
    protected $conditions = [];

    public function __construct(Dispatcher $events, Container $container = null)
    {
        parent::__construct($events, $container);
        $this->routes = new RouteCollection();
    }

    /**
     * Create a new Route object.
     *
     * @param array|string $methods
     * @param string       $uri
     * @param mixed        $action
     *
     * @return \Illuminate\Routing\Route
     */
    protected function newRoute($methods, $uri, $action)
    {
        // WordPress condition could have been already applied.
        // We only try one more time to fetch them if no conditions
        // are registered. This avoids the overwrite of any pre-existing rules.
        if (empty($this->conditions)) {
            $this->setConditions();
        }

        return (new Route($methods, $uri, $action))
            ->setRouter($this)
            ->setContainer($this->container)
            ->setConditions($this->conditions);
    }

    /**
     * Setup WordPress conditions.
     *
     * @param array $conditions
     */
    public function setConditions(array $conditions = [])
    {
        $config = $this->container->has('config') ? $this->container->make('config') : null;

        if (! is_null($config)) {
            $this->conditions = array_merge(
                $config->get('app.conditions', []),
                $conditions
            );
        } else {
            $this->conditions = $conditions;
        }
    }

    /**
     * Add WordPress default parameters if WordPress route.
     *
     * @param \Themosis\Route\Route $route
     *
     * @return \Themosis\Route\Route
     */
    public function addWordPressBindings($route)
    {
        global $post, $wp_query;

        foreach (compact('post', 'wp_query') as $key => $value) {
            $route->setParameter($key, $value);
        }

        return $route;
    }

    /**
     * Register the typical authentication routes for an application.
     * Avoid WordPress default endpoints.
     *
     * @param array $options
     */
    public function auth(array $options = [])
    {
        // Authentication routes.
        $this->get('auth/login', 'Auth\LoginController@showLoginForm')->name('login');
        $this->post('auth/login', 'Auth\LoginController@login');
        $this->post('auth/logout', 'Auth\LoginController@logout')->name('logout');

        // Registration routes.
        if ($options['register'] ?? true) {
            $this->get('auth/register', 'Auth\RegisterController@showRegistrationForm')->name('register');
            $this->post('auth/register', 'Auth\RegisterController@register');
        }

        // Password reset routes.
        if ($options['reset'] ?? true) {
            $this->resetPassword();
        }

        // Email verifications routes.
        if ($options['verify'] ?? false) {
            $this->emailVerification();
        }
    }
}