0.5.0
-----

- (Extractor) Handle morph shapes:
  - Add `MorphShapeDefinition` class and all its dependencies
  - Add `SwfExtractor::morphShapes()` method to extract morph shapes from SWF
  - Update `SwfFile` and `SwfExtractor` to handle `MorphShapeDefinition` return types
  - Handle morph shapes on `ExtractCommand`
  - Introduce `interpolate()` methods on fill types and edge implementations to support morphing
  - Add `ratio` property to `FrameObject`
  - Add `RatioDrawableInterface` for drawables that support ratio
- PHP 8.5 support

**BC breaks:**
- (Parser) `MorphLineStyle2::miterLimitFactor` is now an int (UI16) instead of float (fixed 8.8)
- (Extractor) Add `CharacterModifierInterface::applyOnMorphShape()` method to modify morph shapes after extraction

0.4.0
-----

- (Extractor) Introduce `CharacterModifierInterface` and `DrawableInterface::modify()` to allow modifying drawable objects after extraction
- (Extractor) Add `Timeline::withAttachment()` and `SpriteDefinition::withAttachment()` to attach a movie clip to a timeline or sprite
- (Extractor) Add `Timeline::keepFrameByLabel()`, `Timeline::keepFrameByNumber()`, and `GotoAndStop` modifier to allow keeping only specific frames in a timeline
- (Extractor) Add `Timeline::frameByLabel()`
- (Extractor) Add `Frame::objectsByName()` and `Frame::run()`
- (Extractor) Handle AS3 `SymbolClassTag` to extract characters from its class name
- (Extractor) `SwfExtractor::character()` will return the main timeline if 0 is provided as character ID

**BC breaks:**
- (Extractor) Remove `FrameObject::characterId` property

0.3.0
-----

- (Drawer) Add option to set a minimum stroke with of 1px on SVG to approximate flash rendering

0.2.3
-----

- (Image) Ignore fully transparent pixels when applying color transform
- (Image) Fix alpha channel handling when applying color transform

0.2.2
-----

- (Drawer) Allow use of sprite with clip depth

0.2.1
-----

- (Parser) Fix invalid end of stream detection during uncompress SWF file
- (Parser) Use `EXTRA_DATA` error instead of `INVALID_DATA` when uncompressed data is larger than expected
- Enable PHP-CS-Fixer for the project

0.2.0
-----

- New parser library
- Fully LGPL v3 compliant
- New error handling

**BC breaks:**
- The parser library APIs and structures have changed
- Strict error handling by default

0.1.0
-----

- Initial release of the project.
