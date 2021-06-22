<?php

declare(strict_types=1);

namespace My\Posts;

use My\Categories\Category;
use Osm\Core\Object_;

/**
 * @property bool $ignore_search_parameter
 * @property bool $ignore_category_parameter
 * @property bool $ignore_date_parameter
 * @property ?Category $category Only set on category pages
 * @property ?int $year Only set on category and month pages
 * @property ?int $month Only set on month pages
 */
class PageType extends Object_
{

}