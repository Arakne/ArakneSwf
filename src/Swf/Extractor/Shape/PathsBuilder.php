<?php

namespace Arakne\Swf\Extractor\Shape;

use function array_push;
use function array_values;

/**
 * Build paths of a shape
 * This builder will associate styles to paths and merge them when possible
 */
final class PathsBuilder
{
    /**
     * @var array<string, Path>
     */
    private array $openPaths = [];

    /**
     * @var list<Path>
     */
    private array $closedPaths = [];

    /**
     * @var list<PathStyle|null>
     */
    private array $activeStyles = [];

    /**
     * Active styles that will be used to draw paths
     * If null is passed, the style will be ignored
     *
     * @param PathStyle|null ...$styles
     * @return void
     */
    public function setActiveStyles(?PathStyle ...$styles): void
    {
        $this->activeStyles = $styles;
    }

    /**
     * Merge the given path to all open paths with the active styles
     */
    public function merge(Path $path): void
    {
        foreach ($this->activeStyles as $style) {
            if ($style === null) {
                continue;
            }

            $lastPath = $this->openPaths[$style->hash()] ?? null;

            if (!$lastPath) {
                $this->openPaths[$style->hash()] = $path->withStyle($style);
            } else {
                $this->openPaths[$style->hash()] = $lastPath->merge($path);
            }
        }
    }

    /**
     * Close all active paths
     * This method should be called when the drawing context changes (e.g. new styles)
     *
     * Note: this method is automatically called by {@see export()}
     */
    public function close(): void
    {
        array_push($this->closedPaths, ...array_values($this->openPaths));
        $this->openPaths = [];
    }

    /**
     * Export all built paths
     *
     * @return list<Path>
     */
    public function export(): array
    {
        $this->close();

        $paths = [];

        foreach ($this->closedPaths as $path) {
            $paths[] = $path->fix();
        }

        return $paths;
    }
}
