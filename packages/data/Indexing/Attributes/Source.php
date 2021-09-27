<?php

namespace Osm\Data\Indexing\Attributes;

#[\Attribute(\Attribute::TARGET_CLASS)]
final class Source
{
    public function __construct(public string $source_name)
    {
    }
}