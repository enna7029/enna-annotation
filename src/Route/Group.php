<?php

namespace Enna\Annotation\Route;

use Attribute;

#[Attribute(Attribute::TARGET_CLASS)]
final class Group
{
    public function __construct(public string $name, public array $options = [])
    {
    }
}
