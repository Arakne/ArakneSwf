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
