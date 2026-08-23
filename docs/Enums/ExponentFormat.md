# ExponentFormat

Rendering styles for the exponent portion of scientific-notation float output.

---

## Overview

`ExponentFormat` controls how the exponent is rendered when [`Floats::format()`](../Floats.md) produces scientific
notation (`FloatFormat::Scientific`, or `FloatFormat::Auto` choosing scientific). It ranges from plain ASCII `e`/`E`
notation through to Unicode and HTML mathematical notation.

This is a pure (unbacked) enum, but unlike most enums in this library it carries behavior: `format()` renders a signed
exponent as a string in the case's style.

---

## Cases

### AsciiLowerCaseE

```php
case AsciiLowerCaseE;
```

ASCII lower-case `e` notation (e.g. `'e+42'`). The exponent is not padded to a fixed width, and the sign is always
included.

### AsciiUpperCaseE

```php
case AsciiUpperCaseE;
```

ASCII upper-case `E` notation (e.g. `'E+42'`). The exponent is not padded to a fixed width, and the sign is always
included — matching the scientific notation PHP itself falls back to when casting a float to string (e.g.
`(string) 1.5e20 === '1.5E+20'`).

### AsciiMath

```php
case AsciiMath;
```

ASCII mathematical notation (e.g. `'*10^42'`). Uses ASCII operators and digits.

### UnicodeMath

```php
case UnicodeMath;
```

Unicode mathematical notation (e.g. `'×10⁴²'`). Uses the multiplication sign and superscript digits. This is
`Floats::format()`'s default.

### HtmlMath

```php
case HtmlMath;
```

HTML mathematical notation (e.g. `'&times;10<sup>42</sup>'`).

---

## Cases Summary

| Case              | Example                  |
| ----------------- | ------------------------ |
| `AsciiLowerCaseE` | `e+42`                   |
| `AsciiUpperCaseE` | `E+42`                   |
| `AsciiMath`       | `*10^42`                 |
| `UnicodeMath`     | `×10⁴²` **Default.**     |
| `HtmlMath`        | `&times;10<sup>42</sup>` |

---

## Methods

### format()

```php
public function format(int $exponent): string
```

Render a signed exponent as a string, ready to append directly after a (possibly trimmed) mantissa.

`AsciiLowerCaseE` and `AsciiUpperCaseE` always include an explicit sign (`'+'` or `'-'`), matching `sprintf()`'s own
`'e'`/`'E'` conventions (`AsciiUpperCaseE` also matches PHP's own float-to-string cast, which always signs the
exponent). The three mathematical notations (`AsciiMath`, `UnicodeMath`, `HtmlMath`) omit the `'+'` for positive
exponents and show only `'-'` for negative ones, matching how scientific notation is conventionally written by hand
(e.g. `'×10³'`, not `'×10+3'`).

**Parameters:**

- `$exponent` (`int`) — The exponent (may be negative, zero, or positive).

**Returns:**

- `string` — The rendered exponent, e.g. `'e+42'`, `'E-42'`, `'*10^42'`, `'×10⁴²'`, `'&times;10<sup>42</sup>'`.

**Examples:**

```php
use OceanMoon\Core\Enums\ExponentFormat;

ExponentFormat::AsciiLowerCaseE->format(42);   // 'e+42'
ExponentFormat::AsciiUpperCaseE->format(-42);  // 'E-42'
ExponentFormat::AsciiMath->format(42);         // '*10^42'
ExponentFormat::UnicodeMath->format(42);       // '×10⁴²'
ExponentFormat::UnicodeMath->format(-42);      // '×10⁻⁴²' (no '+' for positive, only '-' for negative)
ExponentFormat::HtmlMath->format(42);          // '&times;10<sup>42</sup>'
```

---

## Usage Examples

`ExponentFormat` is normally passed to `Floats::format()`'s `$expFormat` parameter rather than called directly:

```php
use OceanMoon\Core\Enums\ExponentFormat;
use OceanMoon\Core\Enums\FloatFormat;
use OceanMoon\Core\Floats;

Floats::format(1500.0, precision: 2, format: FloatFormat::Scientific);
// '1.5×10³' (UnicodeMath, the default)

Floats::format(1500.0, precision: 2, format: FloatFormat::Scientific, expFormat: ExponentFormat::AsciiLowerCaseE);
// '1.5e+3'
```

---

## See Also

- **[Floats](../Floats.md)** — `format()` is where `ExponentFormat` is used, via the `$expFormat` parameter.
- **[FloatFormat](FloatFormat.md)** — Selects whether an exponent is used at all.
- **[Integers](../Integers.md)** — `toSuperscript()` is used internally by `UnicodeMath` to render the exponent's
  digits.
