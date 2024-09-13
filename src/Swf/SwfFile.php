<?php

namespace Arakne\Swf;

use Arakne\Swf\Avm\Processor;
use Arakne\Swf\Avm\State;
use Arakne\Swf\Parser\Swf;

use function array_flip;
use function file_get_contents;

/**
 * Facade for extracting information from a SWF file.
 */
final class SwfFile
{
    private ?Swf $parser = null;

    public function __construct(
        /**
         * The path to the SWF file.
         */
        public readonly string $path,
    ) {
    }

    /**
     * Extract and parse tags from the SWF file.
     *
     * @param int ...$tagIds The tag IDs to extract. If empty, all tags are extracted.
     *
     * @return iterable<object>
     */
    public function tags(int ...$tagIds): iterable
    {
        $parser = $this->parser();

        if ($tagIds) {
            $tagIds = array_flip($tagIds);
        }

        foreach ($parser->tags as $tag) {
            if (!$tagIds || isset($tagIds[$tag->type])) {
                yield $parser->parseTag($tag);
            }
        }
    }

    /**
     * Execute DoAction tags and return the final state.
     * The method may be dangerous if the SWF file contains malicious code, so call it only if you trust the source.
     *
     * @param bool $allowFunctionCall Allow to call methods or functions. By default, this is disabled for security reasons.
     *
     * @return State
     */
    public function execute(bool $allowFunctionCall = false): State
    {
        $processor = new Processor($allowFunctionCall);
        $state = new State();

        // @todo handle InitActionTag
        foreach ($this->tags(12) as $tag) {
            $state = $processor->run($tag->actions, $state);
        }

        return $state;
    }

    /**
     * Execute DoAction tags and return all global variables.
     * The method may be dangerous if the SWF file contains malicious code, so call it only if you trust the source.
     *
     * @param bool $allowFunctionCall Allow to call methods or functions. By default, this is disabled for security reasons.
     * @return array<string, mixed>
     */
    public function variables(bool $allowFunctionCall = false): array
    {
        return $this->execute($allowFunctionCall)->variables;
    }

    private function parser(): Swf
    {
        return $this->parser ??= new Swf(file_get_contents($this->path));
    }
}
