# Constants

Shared constants used by Core, Math, and other packages.

---

## Overview

`src/globals.php` provides a small set of namespaced constants (`OceanMoon\Core`) that are more useful as globals than
as class members. The number of items is deliberately kept small to minimise the number of `use const` statements, which
aren't that common or well-known in PHP.

---

## Autoloading

Since these are namespaced global identifiers rather than class members, PSR-4 autoloading won't discover them
automatically. The Core package's `composer.json` therefore includes a `files` autoload entry that loads `globals.php`.

To use a constant without qualifying the namespace every time, add a `use const` import:

**Example:**

```php
use const OceanMoon\Core\M_TAU;
```

---

## Constants

### M_TAU

```php
const M_TAU = 2 * M_PI;
```

The circle constant tau τ = 2π (≈ 6.2831853). Equal to the number of radians in a full circle. Named to match PHP's own
naming pattern for mathematical constants, such as `M_PI`, `M_E`, etc.

```php
use const OceanMoon\Core\M_TAU;

$radius = 10;
$circumference = M_TAU * $radius;  // ≈ 62.83185307179586
```

### RECURSION

```php
const RECURSION = '*RECURSION*';
```

The marker string used by `Arrays::removeRecursion()` and `Stringify` to represent a circular (self-referencing)
reference, in place of infinitely recursing. Intended to match the recursion marker text PHP's own `print_r()` function
uses.

```php
use const OceanMoon\Core\RECURSION;

$arr = ['x' => 1];
$arr['self'] = &$arr;

$cleaned = Arrays::removeRecursion($arr);
// $cleaned['self'] === RECURSION
```

---

## See Also

- **[Floats](Floats.md)** - Float-related methods, including `wrap()`, which uses `M_TAU`.
- **[Arrays](Arrays.md)** - `removeRecursion()`, which uses `RECURSION`.
- **[Stringify](Stringify.md)** - Value-to-string conversion class, which also uses `RECURSION`.
