<?php

namespace Arakne\Tests\Swf\Parser\Structure\Tag;

use Arakne\Swf\Parser\Structure\Tag\DefineSceneAndFrameLabelDataTag;
use Arakne\Swf\Parser\SwfReader;
use PHPUnit\Framework\Attributes\Test;
use PHPUnit\Framework\TestCase;

use function var_dump;

class DefineSceneAndFrameLabelDataTagTest extends TestCase
{
    #[Test]
    public function readShouldStopAtEndOfData()
    {
        $reader = new SwfReader("\xFF\xFF\xFF\xFF\xFFtest\x00\xFF\xFF\xFF\xFF\xFFtest\x00\xFF\xFF\xFF\xFF\xFFtest\x00\xFF\xFF\xFF\xFF\xFFtest\x00", errors: 0);
        $tag = DefineSceneAndFrameLabelDataTag::read($reader);

        $this->assertCount(4, $tag->sceneOffsets);
        $this->assertCount(4, $tag->sceneNames);
        $this->assertCount(0, $tag->frameNumbers);
        $this->assertCount(0, $tag->frameLabels);
    }
}
