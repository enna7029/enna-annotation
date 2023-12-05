<?php

namespace Enna\Annotation;

use ReflectionObject;
use Enna\Framework\App;

trait InteractsWithInject
{
    /**
     * Note: 自动注入
     * User: enna
     * Date: 2023-12-01
     * Time: 16:46
     */
    protected function autoInject()
    {
        if ($this->app->config->get('annotation.inject.enable', true)) {
            $this->app->resolving(function ($object, $app) {
                if ($this->isInjectClass(get_class($object))) {
                    $refObject = new ReflectionObject($object);
                    foreach ($refObject->getProperties() as $refProperty) {
                        if ($refObject->isDefault() && !$refProperty->isStatic()) {
                            $attrs = $refProperty->getAttributes(Inject::class);
                            if (!empty($attrs)) {
                                if (!empty($attrs[0]->getArguments()[0])) {
                                    $type = $attrs[0]->getArguments()[0];
                                } elseif ($refProperty->getType() && $refProperty->getType()->isBuiltin()) {
                                    $type = $refProperty->getType()->getName();
                                }

                                if (isset($type)) {
                                    $value = $app->make($type);
                                    if (!$refProperty->isPublic()) {
                                        $refProperty->setAccessible(true);
                                    }
                                    $refProperty->setValue($object, $value);
                                }
                            }
                        }
                    }

                    if ($refObject->hasMethod('__injected')) {
                        $app->invokeMethod([$object, '__injected']);
                    }
                }
            });
        };
    }

    /**
     * Note: 是否属于正确的命名空间
     * User: enna
     * Date: 2023-12-01
     * Time: 17:46
     * @param $name
     * @return bool
     */
    protected function isInjectClass($name)
    {
        $namespaces = ['app\\'] + $this->app->config->get('annotation.inject.namespaces', []);

        foreach ($namespaces as $namespace) {
            $namespaces = rtrim($namespace, '\\') . '\\';

            if (stripos(rtrim($name, '\\') . '\\', $namespace) === 0) {
                return true;
            }
        }
    }
}