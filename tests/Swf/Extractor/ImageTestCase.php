<?php

namespace Arakne\Tests\Swf\Extractor;

use GdImage;
use phpDocumentor\Reflection\DocBlock\Tags\Var_;
use PHPUnit\Framework\TestCase;

use function chmod;
use function file_put_contents;
use function imagealphablending;
use function imagecolorallocatealpha;
use function imagecolorsforindex;
use function imagecreatetruecolor;
use function imagepng;
use function imagesavealpha;
use function imagesx;
use function var_dump;

class ImageTestCase extends TestCase
{
    public function assertImageStringEqualsImageFile(string $expectedFile, string $imageString, float $delta = 0): void
    {
        $this->assertFileExists($expectedFile);
        $expectedGd = match ($ext = pathinfo($expectedFile, PATHINFO_EXTENSION)) {
            'png' => imagecreatefrompng($expectedFile),
            'jpg' => imagecreatefromjpeg($expectedFile),
            'gif' => imagecreatefromgif($expectedFile),
            default => throw new \InvalidArgumentException('Unsupported image format'),
        };

        file_put_contents($testfile = __DIR__.'/Fixtures/test.'.$ext, $imageString);
        chmod($testfile, 0666);

        $actualGd = imagecreatefromstring($imageString);
        $this->assertInstanceOf(GdImage::class, $actualGd, 'The actual image is not a valid image');
        $this->assertInstanceOf(GdImage::class, $expectedGd, 'The expected image is not a valid image');

        $width = imagesx($expectedGd);
        $height = imagesy($expectedGd);

        $this->assertSame($width, imagesx($actualGd), 'The images have different width');
        $this->assertSame($height, imagesy($actualGd), 'The images have different height');

        $diffImage = imagecreatetruecolor($width, $height);
        imagealphablending($diffImage, false);
        imagesavealpha($diffImage, true);

        $diffCount = 0;
        $transparentColor = imagecolorallocatealpha($diffImage, 0, 0, 0, 127);

        for ($x = 0; $x < $width; $x++) {
            for ($y = 0; $y < $height; $y++) {
                $expectedColor = imagecolorat($expectedGd, $x, $y);
                $actualColor = imagecolorat($actualGd, $x, $y);

                if ($expectedColor !== $actualColor) {
                    ++$diffCount;

                    $expectedColorArr = imagecolorsforindex($expectedGd, $expectedColor);
                    $actualColorArr = imagecolorsforindex($actualGd, $actualColor);

                    $diffColor = imagecolorallocatealpha(
                        $diffImage,
                        abs($expectedColorArr['red'] - $actualColorArr['red']),
                        abs($expectedColorArr['green'] - $actualColorArr['green']),
                        abs($expectedColorArr['blue'] - $actualColorArr['blue']),
                        abs(($expectedColorArr['alpha'] ?? 0) - ($actualColorArr['alpha'] ?? 0))
                    );

                    imagesetpixel($diffImage, $x, $y, $diffColor);
                } else {
                    imagesetpixel($diffImage, $x, $y, $transparentColor);
                }
            }
        }

        $diffRatio = $diffCount / ($width * $height);

        if ($diffRatio > $delta) {
            imagepng($diffImage, __DIR__.'/Fixtures/diff.png');
            $this->fail('The images are different (diff ratio: '.$diffRatio.')');
        }
    }
}
