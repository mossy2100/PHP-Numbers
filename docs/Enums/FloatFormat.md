# FloatFormat

Notation styles for [`Floats::format()`](../Floats.md).

---

## Overview

`FloatFormat` selects how a float is rendered: with a plain decimal point, always in scientific notation, or
automatically choosing whichever is more compact.

This is a pure (unbacked) enum. Its cases carry no value — they're purely selectors passed to `Floats::format()`'s
`$format` parameter.

---

## Cases

### FixedPoint

```php
case FixedPoint;
```

Use a decimal point, with no exponent (e.g. `'1234.5'`).

### Scientific

```php
case Scientific;
```

Always include an exponent (e.g. `'1.2345×10³'`), rendered per the [`ExponentFormat`](ExponentFormat.md) passed to
`Floats::format()`.

### Auto

```php
case Auto;
```

Whichever of `FixedPoint` or `Scientific` produces the more useful string. This is `Floats::format()`'s default.

`FixedPoint` is preferred unless `Scientific` has fewer significant figures, or `FixedPoint` would need more than 3
leading or trailing zeros.

---

## Usage Examples

```php
use OceanMoon\Core\Enums\FloatFormat;
use OceanMoon\Core\Floats;

Floats::format(1234.5, format: FloatFormat::FixedPoint);  // '1234.5'
Floats::format(1234.5, format: FloatFormat::Scientific);  // '1.2345×10³'
Floats::format(1234.5, format: FloatFormat::Auto);        // '1234.5' (more compact than scientific here)
Floats::format(0.0000012345, format: FloatFormat::Auto);  // '1.2345×10⁻⁶' (fixed-point would need 6 leading zeros)
```

---

## See Also

- **[Floats](../Floats.md)** — `format()` is where `FloatFormat` is used.
- **[ExponentFormat](ExponentFormat.md)** — Controls how the exponent is rendered when `Scientific` (or `Auto` choosing
  scientific) is used.
