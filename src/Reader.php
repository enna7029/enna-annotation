<?php

namespace Enna\Annotation;

use ReflectionAttribute;
use ReflectionClass;
use ReflectionMethod;

class Reader
{
    /**
     * Note: 获取注解信息
     * Date: 2023-12-04
     * Time: 16:44
     * @param ReflectionClass|ReflectionMethod $ref 反射类api
     * @param string $name 注解类
     * @return mixed
     */
    public function getAnnotation($ref, $name)
    {
        $attributes = array_map(function (ReflectionAttribute $attribute) {
            return $attribute->newInstance();
        }, $ref->getAttributes($name, ReflectionAttribute::IS_INSTANCEOF));

        foreach ($attributes as $attribute) {
            return $attribute;
        }

        return null;
    }
}