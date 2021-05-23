<?php

declare(strict_types=1);

namespace My\Tests;

use My\Posts\Files\Markdown;
use Osm\Framework\TestCase;

class test_01_markdown_parsing extends TestCase
{
    public string $app_class_name = \My\Samples\App::class;

    public function test_something() {
        // GIVEN a `welcome.md` file

        // WHEN you parse it
        $file = Markdown::new(['path' => '21/05/18-welcome.md']);

        // THEN its properties return parsed data
        $this->assertTrue($file->exists);
        $this->assertEquals('Welcome!', $file->title);
    }
}