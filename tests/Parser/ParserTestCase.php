<?php

namespace Arakne\Tests\Swf\Parser;

use Arakne\Swf\Parser\SwfReader;
use PHPUnit\Framework\TestCase;

class ParserTestCase extends TestCase
{
    public function createReader(string $file, ?int $offset = null, int $errors = -1): SwfReader
    {
        $reader = new SwfReader($d = file_get_contents($file), errors: $errors);
        $isCompressed = $d[0] === 'C';

        if ($isCompressed) {
            $reader->skipBytes(8);
            $reader = $reader->uncompress();
        }

        if ($offset !== null) {
            $reader->skipBytes($offset - ($isCompressed ? 8 : 0));
        }

        return $reader;
    }
}
