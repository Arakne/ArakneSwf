<?php

namespace Arakne\Tests\Swf\Extractor;

use Exception;
use GdImage;
use Imagick;
use PHPUnit\Framework\TestCase;
use SapientPro\ImageComparator\ImageComparator;

use function chmod;
use function count;
use function file_get_contents;
use function file_put_contents;
use function imagecreatefromstring;
use function imagesx;
use function imagesy;
use function is_string;
use function sprintf;
use function var_dump;

class ImageTestCase extends TestCase
{
    public function assertImageStringEqualsImageFile(string|array $expectedFiles, string $imageString, float $delta = 0): void
    {
        file_put_contents($testfile = __DIR__.'/Fixtures/test.png', $imageString);
        chmod($testfile, 0666);

        $bestDiff = 1;
        $lastError = null;

        foreach ((array) $expectedFiles as $expectedFile) {
            $lastError = null;
            $this->assertFileExists($expectedFile);

            try {
                $diff = $this->imageDiff($imageString, file_get_contents($expectedFile));
            } catch (Exception $e) {
                $lastError = $e;
                continue;
            }

            if ($diff <= $delta) {
                return;
            }

            if ($diff < $bestDiff) {
                $bestDiff = $diff;
            }
        }

        if ($lastError !== null) {
            $this->fail($lastError->getMessage());
        }

        $this->fail('The images are different (diff ratio: '.$bestDiff.')');
    }

    public function assertAnimatedImageStringEqualsImageFile(string|array $expectedFiles, string $imageString, float $delta = 0): void
    {
        file_put_contents($testfile = __DIR__.'/Fixtures/test.gif', $imageString);
        chmod($testfile, 0666);

        $actualFrames = $this->getFrames($imageString);

        $bestDiff = 1;
        $lastError = null;

        foreach ((array) $expectedFiles as $expectedFile) {
            $lastError = null;
            $this->assertFileExists($expectedFile);

            $expectedImageString = file_get_contents($expectedFile);

            if ($expectedFile === $imageString) {
                return;
            }

            $expectedFrames = $this->getFrames($expectedImageString);

            if (count($expectedFrames) !== count($actualFrames)) {
                $this->fail(sprintf('The number of frames differ. Expected: %d Actual: %d', count($expectedFrames), count($actualFrames)));
            }

            $diff = 0;

            try {
                foreach ($expectedFrames as $i => $expectedFrame) {
                    $frameDiff = $this->imageDiff($actualFrames[$i], $expectedFrame);

                    if ($frameDiff > $diff) {
                        $diff = $frameDiff;
                    }
                }
            } catch (Exception $e) {
                $lastError = $e;
                continue;
            }

            if ($diff <= $delta) {
                return;
            }

            if ($diff < $bestDiff) {
                $bestDiff = $diff;
            }
        }

        if ($lastError !== null) {
            $this->fail($lastError->getMessage());
        }

        $this->fail('The images are different (diff ratio: '.$bestDiff.')');
    }

    protected function imageDiff(string|GdImage $imageString, string|GdImage $expected): float
    {
        $imageComparator = new ImageComparator();

        $expectedGd = is_string($expected) ? imagecreatefromstring($expected) : $expected;
        $actualGd = is_string($imageString) ? imagecreatefromstring($imageString) : $imageString;

        $this->assertInstanceOf(GdImage::class, $expectedGd, 'The expected image is not a valid image');
        $this->assertInstanceOf(GdImage::class, $actualGd, 'The actual image is not a valid image');

        $expectedSize = [imagesx($expectedGd), imagesy($expectedGd)];
        $actualSize = [imagesx($actualGd), imagesy($actualGd)];

        if ($expectedSize !== $actualSize) {
            $this->fail(sprintf('Image size differ. Expected: %dx%d Actual: %dx%d', $expectedSize[0], $expectedSize[1], $actualSize[0], $actualSize[1]));
        }

        $similarity = $imageComparator->compare($expectedGd, $actualGd);
        return (100 - $similarity) / 100;
    }

    /**
     * @param string $imageString
     * @return list<GdImage>
     */
    protected function getFrames(string $imageString): array
    {
        $actualImage = new Imagick();
        $actualImage->readImageBlob($imageString);

        $frames = [];

        foreach ($actualImage as $frame) {
            $frame->setImageFormat('png');
            $frames[] = imagecreatefromstring($frame->getImageBlob());
        }

        return $frames;
    }
}
