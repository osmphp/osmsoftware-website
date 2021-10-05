<?php

namespace Osm\Data\Markdown\Attributes;

#[\Attribute(\Attribute::TARGET_CLASS)]
final class In_
{
    public function __construct(public string $class_name)
    {
    }
}