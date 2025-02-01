<?php

namespace Arakne\Swf\Extractor\Shape;

use function array_map;
use function array_pop;
use function array_push;
use function array_reverse;
use function spl_object_id;

final class Path
{
    public function __construct(
        public array $edges = [],
        public ?PathStyle $style = null,
    ) {}

    public function merge(Path $other): self
    {
        array_push($this->edges, ...$other->edges);

        return $this;
    }

    public function withStyle(PathStyle $style): self
    {
        return new Path($this->edges, $style);
    }

    /**
     * Try to reconnect edges that are not connected
     * Flash seems to allow this, but SVG does not
     */
    public function fix(): self
    {
        $edgeSet = [];

        foreach ($this->edges as $edge) {
            // @todo use a better key
            $edgeSet[spl_object_id($edge)] = $edge;
        }

        $edges = [];

        // @todo optimize
        while ($edgeSet) {
            $currentEdge = array_pop($edgeSet);
            $edges[] = $currentEdge;

            while ($currentEdge) {
                $found = false;

                foreach ($edgeSet as $other) {
                    if ($currentEdge->toX === $other->fromX && $currentEdge->toY === $other->fromY) {
                        $edges[] = $other;
                        unset($edgeSet[spl_object_id($other)]);
                        $currentEdge = $other;
                        $found = true;
                        break;
                    }

                    if ($currentEdge->toX === $other->toX && $currentEdge->toY === $other->toY) {
                        $reverse = $other->reverse();
                        $edges[] = $reverse;
                        unset($edgeSet[spl_object_id($other)]);
                        $currentEdge = $reverse;
                        $found = true;
                        break;
                    }
                }

                if (!$found) {
                    break;
                }
            }
        }

        $fixed = clone $this;
        $fixed->edges = $edges;

        return $fixed;
    }

    public function reverse(): self
    {
        $reversed = clone $this;
        $reversed->edges = array_map(fn($edge) => $edge->reverse(), array_reverse($this->edges));

        return $reversed;
    }

    public function export(): string
    {
        $str = '';
        $lastX = null;
        $lastY = null;

        foreach ($this->edges as $edge) {
            if ($str) {
                $str .= ' ';
            }

            if (!$edge->matchFrom($lastX, $lastY)) {
                $str .= 'M'.($edge->fromX/20).' '.($edge->fromY/20);
            }

            if ($str) {
                $str .= ' ';
            }

            $str .= $edge->export();
            $lastX = $edge->toX;
            $lastY = $edge->toY;
        }

        return $str;
    }

    public function draw(PathDrawerInterface $drawer): void
    {
        $lastX = null;
        $lastY = null;

        foreach ($this->edges as $edge) {
            if (!$edge->matchFrom($lastX, $lastY)) {
                $drawer->move($edge->fromX, $edge->fromY);
            }

            $edge->draw($drawer);

            //$str .= $edge->export();
            $lastX = $edge->toX;
            $lastY = $edge->toY;
        }
    }
}
