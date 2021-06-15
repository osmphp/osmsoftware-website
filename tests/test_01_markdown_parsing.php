<?php

declare(strict_types=1);

namespace My\Tests;

use My\Posts\MarkdownParser;
use Osm\Framework\TestCase;

class test_01_markdown_parsing extends TestCase
{
    public string $app_class_name = \My\Samples\App::class;

    public function test_title() {
        // GIVEN the `welcome.md` file

        // WHEN you parse it
        $parser = MarkdownParser::new(['path' => '21/05/18-welcome.md']);

        // THEN its title is in a property
        $this->assertTrue($parser->exists);
        $this->assertEquals('Welcome!', $parser->title);
    }

    public function test_created_at_and_url_key() {
        // GIVEN the `welcome.md` file

        // WHEN you parse it
        $parser = MarkdownParser::new(['path' => '21/05/18-welcome.md']);

        // THEN its title is in a property
        $this->assertTrue($parser->exists);
        $this->assertEquals(2021, $parser->created_at->year);
        $this->assertEquals('welcome', $parser->url_key);
    }

    public function test_toc_and_meta() {
        // GIVEN the `welcome.md` file

        // WHEN you parse it
        $parser = MarkdownParser::new(['path' => '21/05/19-requirements.md']);

        // THEN its meta data is in parser's property
        $this->assertTrue($parser->exists);
        $this->assertEquals("Building osmcommerce.com",
            $parser->meta->series);
        $this->assertEquals(1, $parser->meta->series_part);
        $this->assertTrue(isset($parser->meta->summary));

        // AND TOC data is in parser's property, too
        $this->assertTrue(isset($parser->toc->header));
        $this->assertTrue(isset($parser->toc->{"editing-workflow"}));
        $this->assertFalse(isset($parser->toc->meta));

    }
}