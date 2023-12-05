<?php

namespace Enna\Annotation;

use Attribute;

#[Attribute(Attribute::TARGET_PROPERTY)]
final class Inject
{
    public function __construct(?string $abstract = null)
    {
    }
}