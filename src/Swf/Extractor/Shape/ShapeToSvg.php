<?php

namespace Arakne\Swf\Extractor\Shape;

use Arakne\Swf\Parser\Structure\Tag\DefineShape4Tag;
use Arakne\Swf\Parser\Structure\Tag\DefineShapeTag;
use SimpleXMLElement;

use function array_pop;
use function array_push;
use function array_values;
use function spl_object_id;
use function sprintf;

final class ShapeToSvg
{
    public function convert(DefineShapeTag|DefineShape4Tag $tag): string
    {
        $xml = new SimpleXMLElement('<svg xmlns="http://www.w3.org/2000/svg"></svg>');

        $width = ($tag->shapeBounds['xmax'] - $tag->shapeBounds['xmin']) / 20;
        $height = ($tag->shapeBounds['ymax'] - $tag->shapeBounds['ymin']) / 20;

        $xml->addAttribute('width', $width.'px');
        $xml->addAttribute('height', $height.'px');

        $g = $xml->addChild('g');
        $xoffset = -$tag->shapeBounds['xmin'] / 20;
        $yoffset = -$tag->shapeBounds['ymin'] / 20;
        $g->addAttribute('transform', "matrix(1.0, 0.0, 0.0, 1, $xoffset, $yoffset)");

        $fillStyles = $tag->shapes['fillStyles'];
        $lineStyles = $tag->shapes['lineStyles'];

        $x = 0;
        $y = 0;
        $fillStyle0Color = null;
        $fillStyle1Color = null;
        $lineStyleColor = null; // @todo store also width

        $linePaths = [];
        $fillPaths = [];
        $currentPath = new Path();

        $mergePath = function (Path $current) use (&$linePaths, &$fillPaths, &$fillStyle0Color, &$fillStyle1Color, &$lineStyleColor) {
            if ($fillStyle0Color !== null) {
                ($fillPaths[$fillStyle0Color] ??= new ColoredPath($fillStyle0Color))->merge($current);
            }

            if ($fillStyle1Color !== null) {
                ($fillPaths[$fillStyle1Color] ??= new ColoredPath($fillStyle1Color))->merge($current);
            }

            if ($lineStyleColor !== null) {
                ($linePaths[$lineStyleColor] ??= new ColoredPath($lineStyleColor))->merge($current);
            }
        };

        foreach ($tag->shapes['shapeRecords'] as $index => $shape) {
            switch ($shape['type']) {
                case 'StyleChangeRecord':
                    $mergePath($currentPath);
                    $currentPath = new Path();

                    if ($shape['stateNewStyles']) {
                        // Reset styles to ensure that we don't use old styles
                        $fillPaths = array_values($fillPaths);
                        $linePaths = array_values($linePaths);

                        $fillStyles = $shape['fillStyles'];
                        $lineStyles = $shape['lineStyles'];
                    }

                    if ($shape['stateLineStyle']) {
                        $colorArr = $lineStyles[$shape['lineStyle'] - 1]['color'] ?? null;
                        if ($colorArr !== null) {
                            $lineStyleColor = sprintf('#%02x%02x%02x', $colorArr['red'], $colorArr['green'], $colorArr['blue']);
                        } else {
                            $lineStyleColor = null;
                        }
                    }

                    if ($shape['stateFillStyle0']) {
                        $colorArr = $fillStyles[$shape['fillStyle0'] - 1]['color'] ?? null;
                        if ($colorArr !== null) {
                            $fillStyle0Color = sprintf('#%02x%02x%02x', $colorArr['red'], $colorArr['green'], $colorArr['blue']);;
                        } else {
                            $fillStyle0Color = null;
                        }
                    }

                    if ($shape['stateFillStyle1']) {
                        $colorArr = $fillStyles[$shape['fillStyle1'] - 1]['color'] ?? null;
                        if ($colorArr !== null) {
                            $fillStyle1Color = sprintf('#%02x%02x%02x', $colorArr['red'], $colorArr['green'], $colorArr['blue']);;
                        } else {
                            $fillStyle1Color = null;
                        }
                    }

                    if ($shape['stateMoveTo']) {
                        $x = $shape['moveDeltaX'];
                        $y = $shape['moveDeltaY'];
                    }

                    // @todo compléter

                    break;

                case 'StraightEdgeRecord':
                    if ($shape['generalLineFlag']) {
                        $toX = $x + $shape['deltaX'];
                        $toY = $y + $shape['deltaY'];

                        $currentPath->edges[] = new StraightEdge($x, $y, $toX, $toY);

                        $x = $toX;
                        $y = $toY;
                    } elseif (!empty($shape['vertLineFlag'])) {
                        $toY = $y + $shape['deltaY'];

                        $currentPath->edges[] = new StraightEdge($x, $y, $x, $toY);

                        $y = $toY;
                    } else {
                        $toX = $x + $shape['deltaX'];

                        $currentPath->edges[] = new StraightEdge($x, $y, $toX, $y);

                        $x = $toX;
                    }
                    break;

                case 'CurvedEdgeRecord':
                    $fromX = $x;
                    $fromY = $y;
                    $controlX = $x + $shape['controlDeltaX'];
                    $controlY = $y + $shape['controlDeltaY'];
                    $toX = $x + $shape['controlDeltaX'] + $shape['anchorDeltaX'];
                    $toY = $y + $shape['controlDeltaY'] + $shape['anchorDeltaY'];

                    $currentPath->edges[] = new CurvedEdge($fromX, $fromY, $controlX, $controlY, $toX, $toY);

                    $x = $toX;
                    $y = $toY;
                    break;

                case 'EndShapeRecord':
                    $mergePath($currentPath);
                    break 2;

                default:
                    throw new \Exception('Unknown shape type: '.$shape['type']);
            }
        }

        foreach ($fillPaths as $path) {
            $pathElement = $g->addChild('path');
            $pathElement['fill'] = $path->color;
            $pathElement['fill-rule'] = 'evenodd';
            $pathElement['stroke'] = 'none';

            $pathElement['d'] = $path->fix()->export();
        }

        foreach ($linePaths as $path) {
            $pathElement = $g->addChild('path');
            $pathElement['fill'] = 'none';
            $pathElement['stroke'] = $path->color;
            $pathElement['stroke-width'] = 1; // @todo width
            $pathElement['stroke-linecap'] = 'round';
            $pathElement['stroke-linejoin'] = 'round';

            $pathElement['d'] = $path->fix()->export();
        }

        return $xml->asXML();
    }
}

class ColoredPath extends Path
{
    public function __construct(
        public string $color,
        array $edges = []
    ) {
        parent::__construct($edges);
    }
}

class Path
{
    public function __construct(
        public array $edges = [],
    ) {}

    public function merge(Path $other): self
    {
        array_push($this->edges, ...$other->edges);

        return $this;
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
}

readonly class StraightEdge
{
    public function __construct(
        public int $fromX,
        public int $fromY,
        public int $toX,
        public int $toY,
    ) {}

    public function reverse(): self
    {
        return new self($this->toX, $this->toY, $this->fromX, $this->fromY);
    }

    public function matchFrom(?int $x, ?int $y): bool
    {
        return $this->fromX === $x && $this->fromY === $y;
    }

    public function export(): string
    {
        return "L" . ($this->toX/20) . " " . ($this->toY/20);
    }
}

readonly class CurvedEdge
{
    public function __construct(
        public int $fromX,
        public int $fromY,
        public int $controlX,
        public int $controlY,
        public int $toX,
        public int $toY,
    ) {}

    public function reverse(): self
    {
        return new self($this->toX, $this->toY, $this->controlX, $this->controlY, $this->fromX, $this->fromY);
    }

    public function matchFrom(?int $x, ?int $y): bool
    {
        return $this->fromX === $x && $this->fromY === $y;
    }

    public function export(): string
    {
        return "Q" . ($this->controlX/20) . " " . ($this->controlY/20) . " " . ($this->toX/20) . " " . ($this->toY/20);
    }
}
