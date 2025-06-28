<?php

namespace Arakne\Tests\Swf\Parser\Structure\Record;

use Arakne\Swf\Parser\Structure\Record\Matrix;
use Arakne\Swf\Parser\SwfReader;
use Arakne\Tests\Swf\Parser\ParserTestCase;
use PHPUnit\Framework\Attributes\Test;
use PHPUnit\Framework\TestCase;

use function file_get_contents;

class MatrixTest extends ParserTestCase
{
    #[Test]
    public function readOnlyTranslate()
    {
        $reader = $this->createReader(__DIR__.'/../../Fixtures/Examples1.swf', 195);

        $matrix = Matrix::read($reader);

        $this->assertSame(1.0, $matrix->scaleX);
        $this->assertSame(1.0, $matrix->scaleY);
        $this->assertSame(0.0, $matrix->rotateSkew0);
        $this->assertSame(0.0, $matrix->rotateSkew1);
        $this->assertSame(40, $matrix->translateX);
        $this->assertSame(40, $matrix->translateY);
    }

    #[Test]
    public function readEmpty()
    {
        $reader = $this->createReader(__DIR__.'/../../../Fixtures/139.swf', 1895);

        $matrix = Matrix::read($reader);

        $this->assertSame(1.0, $matrix->scaleX);
        $this->assertSame(1.0, $matrix->scaleY);
        $this->assertSame(0.0, $matrix->rotateSkew0);
        $this->assertSame(0.0, $matrix->rotateSkew1);
        $this->assertSame(0, $matrix->translateX);
        $this->assertSame(0, $matrix->translateY);
    }

    #[Test]
    public function readAllParameters()
    {
        $reader = $this->createReader(__DIR__.'/../../../Fixtures/1317.swf', 2075);

        $matrix = Matrix::read($reader);

        $this->assertSame(-0.477874755859375, $matrix->scaleX);
        $this->assertSame(0.477874755859375, $matrix->scaleY);
        $this->assertSame(-0.8749542236328125, $matrix->rotateSkew0);
        $this->assertSame(-0.8749542236328125, $matrix->rotateSkew1);
        $this->assertSame(-102, $matrix->translateX);
        $this->assertSame(-1363, $matrix->translateY);
    }
}
