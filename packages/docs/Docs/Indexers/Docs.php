<?php

namespace Osm\Docs\Docs\Indexers;

use Osm\Data\Indexing\Attributes\Source;
use Osm\Data\Indexing\Attributes\Target;
use Osm\Data\Indexing\Indexer;

#[Source('docs'), Target('docs.index')]
class Docs extends Indexer
{

}