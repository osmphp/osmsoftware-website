<?php

namespace Osm\Docs\Docs\Traits;

use Osm\Core\Attributes\UseIn;
use Osm\Docs\Docs\Hints\Settings\Docs;
use Osm\Framework\Settings\Hints\Settings;

/**
 * @property Docs $docs
 */
#[UseIn(Settings::class)]
trait SettingsTrait
{

}