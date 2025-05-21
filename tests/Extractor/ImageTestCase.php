<?php

namespace Arakne\Tests\Swf\Extractor;

use GdImage;
use PHPUnit\Framework\TestCase;
use SapientPro\ImageComparator\ImageComparator;

use function chmod;
use function file_get_contents;
use function file_put_contents;
use function imagecreatefromstring;
use function imagesx;
use function sprintf;

class ImageTestCase extends TestCase
{
    public function assertImageStringEqualsImageFile(string|array $expectedFiles, string $imageString, float $delta = 0): void
    {
        $bestDiff = 1;

        foreach ((array) $expectedFiles as $expectedFile) {
            $this->assertFileExists($expectedFile);

            $imageComparator = new ImageComparator();

            file_put_contents($testfile = __DIR__.'/Fixtures/test.png', $imageString);
            chmod($testfile, 0666);

            $expectedGd = imagecreatefromstring(file_get_contents($expectedFile));
            $actualGd = imagecreatefromstring($imageString);

            $this->assertInstanceOf(GdImage::class, $expectedGd, 'The expected image is not a valid image');
            $this->assertInstanceOf(GdImage::class, $actualGd, 'The actual image is not a valid image');

            $expectedSize = [imagesx($expectedGd), imagesy($expectedGd)];
            $actualSize = [imagesx($actualGd), imagesy($actualGd)];

            if ($expectedSize !== $actualSize) {
                $this->fail(sprintf('Image size differ. Expected: %dx%d Actual: %dx%d', $expectedSize[0], $expectedSize[1], $actualSize[0], $actualSize[1]));
            }

            $similarity = $imageComparator->compare($expectedFile, $actualGd);
            $diff = (100 - $similarity) / 100;

            if ($diff <= $delta) {
                return;
            }

            if ($diff < $bestDiff) {
                $bestDiff = $diff;
            }
        }

        $this->fail('The images are different (diff ratio: '.$bestDiff.')');
    }
}
