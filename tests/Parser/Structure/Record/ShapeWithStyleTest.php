<?php

namespace Arakne\Tests\Swf\Parser\Structure\Record;

use Arakne\Swf\Parser\Structure\Record\Color;
use Arakne\Swf\Parser\Structure\Record\Shape\FillStyle;
use Arakne\Swf\Parser\Structure\Record\Shape\LineStyle;
use Arakne\Swf\Parser\Structure\Record\Shape\ShapeRecord;
use Arakne\Swf\Parser\Structure\Record\Shape\ShapeWithStyle;
use Arakne\Swf\Parser\SwfReader;
use Arakne\Tests\Swf\Parser\ParserTestCase;
use PHPUnit\Framework\Attributes\Test;
use PHPUnit\Framework\TestCase;

use function file_get_contents;

class ShapeWithStyleTest extends ParserTestCase
{
    #[Test]
    public function read()
    {
        $reader = $this->createReader(__DIR__.'/../../../Fixtures/1317.swf', 39);

        $data = ShapeWithStyle::read($reader, 3);

        $this->assertCount(1, $data->fillStyles);
        $this->assertCount(1, $data->lineStyles);

        $this->assertEquals(new FillStyle(type: FillStyle::SOLID, color: new Color(54, 109, 97, 255)), $data->fillStyles[0]);
        $this->assertEquals(new LineStyle(width: 10, color: new Color(0, 0, 0, 105)), $data->lineStyles[0]);

        $this->assertCount(6, $data->shapeRecords);
        $this->assertContainsOnlyInstancesOf(ShapeRecord::class, $data->shapeRecords);
    }
}
