<?php

namespace Arakne\Tests\Swf;

use Arakne\Swf\Error\Errors;
use Arakne\Swf\SwfFile;

use function file_put_contents;
use function pack;
use function strlen;
use function tempnam;

class SwfBuilder
{
    private array $tempFiles = [];

    public function createSwfFile(array $tags, int $errors = Errors::ALL): SwfFile
    {
        $this->tempFiles[] = $f = tempnam('/tmp', 'swf');
        file_put_contents($f, $this->buildSwf($tags));

        return new SwfFile($f, errors: $errors);
    }

    public function buildSwf(array $tags): string
    {
        $body = $this->buildTags($tags);
        $body .= "\x00\x00"; // End of tags

        return "FWS\x05" . pack('V', strlen($body) + 8) . "\x00\x01\x00\x01\x00" . $body;
    }

    public function buildTags(array $tags): string
    {
        $body = '';

        foreach ($tags as $params) {
            $code = $params[0];
            $content = $params[1] ?? '';
            $len = strlen($content);

            if ($len < 0x3f) {
                $body .= pack('v', $len | ($code << 6));
            } else {
                $body .= pack('vV', 0x3f | ($code << 6), $len);
            }

            $body .= $content;
        }

        return $body;
    }

    public function __destruct()
    {
        foreach ($this->tempFiles as $file) {
            @unlink($file);
        }
    }
}
