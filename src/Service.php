<?php

namespace Enna\Annotation;

use Enna\Framework\Service as BaseService;

class Service extends BaseService
{
    use InteractsWithInject, InteractsWithRoute;

    /**
     * 读取器
     * @var Reader
     */
    protected Reader $reader;

    public function boot(Reader $reader)
    {
        $this->reader = $reader;

        //自动注入
        $this->autoInject();

        //注解路由
        $this->registerAnnotationRoute();
    }
}