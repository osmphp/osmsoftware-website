<?php

namespace Osm\Data\Indexing;

use Osm\Core\Object_;

/**
 * Specified the scope of invalidated data in a source of an indexing operation,
 * and how it should be processed by indexers. The `#[Name]` attribute
 * specifies the data store, be it a directory in the file system, a database
 * table, a search index, o an external service.
 *
 * Note that properties that define the scope of invalidated data (`pattern`,
 * and other properties, defined in the derived classes), and its status
 * (`invalidated`, `rebuild`) are writable.
 *
 * @property string $name The name of the source, filled in from the
 *      `#[Name]` attribute
 * @property bool $rebuild Specifies how the invalidated data is processed.
 *      If `true`, indexers should dump the existing data, and compute
 *      it all anew. Otherwise, indexers should process only invalidated data,
 *      which is usually faster. The default is incremental processing.
 * @property ?string $pattern The unparsed scope of invalidated data
 *      specified in the `osm index {source}/{scope}` parameter. The meaning
 *      varies across different sources. If the `rebuild` is `true`,
 *      it's ignored.
 * @property bool $invalidated Signals whether the source has any invalidated
 *      data
 */
class Source extends Object_
{
}