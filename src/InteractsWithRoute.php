<?php

namespace Enna\Annotation;

use Enna\Framework\Event\RouteLoaded;
use Ergebnis\Classy\Constructs;
use ReflectionClass;
use ReflectionMethod;
use Enna\Framework\Helper\Str;
use Enna\Annotation\Route\Route;
use Enna\Annotation\Route\Validate;
use Enna\Annotation\Route\Middleware;
use Enna\Annotation\Route\Group;
use Enna\Annotation\Route\Resource;

trait InteractsWithRoute
{
    /**
     * 路由对象
     * @var \Enna\Framework\Route
     */
    protected $route;

    /**
     * 控制器目录
     * @var string
     */
    protected $controllerDir;

    /**
     * 控制器后缀
     * @var string
     */
    protected $controllerSuffix;

    /**
     * 已解析的类
     * @var array
     */
    protected $parseClass = [];

    protected function registerAnnotationRoute()
    {
        if ($this->app->config->get('annotation.route.enable', true)) {
            $this->app->event->listen(RouteLoaded::class, function () {
                $this->route = $this->app->route;
                $this->controllerDir = realpath($this->app->getAppPath() . $this->app->config->get('route.controller_layer'));
                $this->controllerSuffix = $this->app->config->get('route.controller_suffix') ? 'Controller' : '';

                $dirs = array_merge(
                    $this->app->config->get('annotation.route.controllers', []),
                    [$this->controllerDir],
                );

                foreach ($dirs as $dir => $options) {
                    if (is_numeric($dir)) {
                        $dir = $options;
                        $options = [];
                    }

                    if (is_dir($dir)) {
                        $this->scanDir($dir, $options);
                    }
                }
            });
        }
    }

    protected function scanDir($dir, $options = [])
    {
        $groups = [];
        foreach (Constructs::fromDirectory($dir) as $construct) {
            $class = $construct->name();

            if (in_array($class, $this->parseClass)) {
                continue;
            }

            $this->parseClass[] = $class;

            $refClass = new ReflectionClass($class);
            if ($refClass->isAbstract() || $refClass->isInterface() || $refClass->isTrait()) {
                continue;
            }

            //得到控制器名称
            $filename = $construct->fileNames()[0];
            $prefix = $class;
            if ($this->controllerDir != '' && mb_strpos($filename, $this->controllerDir) === 0) {
                $filename = Str::substr($filename, strlen($this->controllerDir) + 1);
                $prefix = str_replace($this->controllerSuffix . '.php', '', str_replace('/', '.', $filename));
            }

            //方法
            $routes = [];
            foreach ($refClass->getMethods(ReflectionMethod::IS_PUBLIC) as $refMethod) {
                if ($routeAnn = $this->reader->getAnnotation($refMethod, Route::class)) {

                    $routes[] = function () use ($routeAnn, $prefix, $refMethod) {
                        //路由
                        $rule = $this->route->rule($routeAnn->rule, "{$prefix}/{$refMethod->getName()}", $routeAnn->method);

                        $rule->option($routeAnn->options);

                        //中间件
                        if (!empty($middlewaresAnn = $this->reader->getAnnotation($refMethod, Middleware::class))) {
                            foreach ($middlewaresAnn as $middlewareAnn) {
                                $rule->middleware($middlewareAnn->value, ...$middlewareAnn->params);
                            }
                        }

                        //验证
                        if ($validateAnn = $this->reader->getAnnotation($refMethod, Validate::class)) {
                            $rule->validate($validateAnn->value, $validateAnn->scene, $validateAnn->message, $validateAnn->batch);
                        }
                    };
                }
            }

            //路由分组
            $groups[] = function () use ($routes, $refClass, $prefix) {
                $groupName = '';
                $groupOptions = [];
                if ($groupAnn = $this->reader->getAnnotation($refClass, Group::class)) {
                    $groupName = $groupAnn->name;
                    $groupOptions = $groupAnn->options;
                }

                $group = $this->route->group($groupName, function () use ($refClass, $prefix, $routes) {
                    //注册路由
                    foreach ($routes as $route) {
                        $route();
                    }

                    //注册资源路由
                    if ($resourceAnn = $this->reader->getAnnotation($refClass, Resource::class)) {
                        $this->route->resource($resourceAnn->rule, $prefix)->option($resourceAnn->options);
                    }
                });

                $group->option($groupOptions);

                //中间件
                if (!empty($middlewaresAnn = $this->reader->getAnnotations($refClass, Middleware::class))) {
                    foreach ($middlewaresAnn as $middlewareAnn) {
                        $group->middleware($middlewareAnn->value, ...$middlewareAnn->params);
                    }
                }
            };
        }

        //路由
        if (!empty($groups)) {
            $this->route->group('', function () use ($groups) {
                foreach ($groups as $group) {
                    $group();
                }
            })->option($options);
        }
    }
}