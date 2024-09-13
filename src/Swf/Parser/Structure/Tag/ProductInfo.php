<?php

namespace Arakne\Swf\Parser\Structure\Tag;

/**
 * Attach information about the product that created the SWF file.
 *
 * Note: this tag is not documented in the official SWF documentation.
 *
 * @see https://www.m2osw.com/swf_tag_productinfo
 */
final readonly class ProductInfo
{
    public function __construct(
        public int $productId,
        public int $edition,
        public int $majorVersion,
        public int $minorVersion,
        public int $buildNumber,
        public int $compilationDate,
    ) {
    }
}
