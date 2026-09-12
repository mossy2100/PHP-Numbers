# Changelog

All notable changes to this project will be documented in this file.

The format is based on [Keep a Changelog](https://keepachangelog.com/en/1.0.0/), and this project adheres to
[Semantic Versioning](https://semver.org/spec/v2.0.0.html).

---

## [Unreleased]

### Added

- **`OceanMoon\Core\Console`** — new singleton class for ANSI/SGR-styled console output, replacing the removed
  `println()`/`inspect()` global functions and adding a horizontal-rule helper (see Changed, below). Every method
  echoes its escape code immediately and returns `$this` for chaining, and tracks current style state so it can be
  snapshotted (`getStyle()`) and restored (`setStyle()`). Includes:
  - Foreground/background color and 16-color palette constants (`setColor()`, `setBackground()`, `resetColor()`).
  - Attribute toggles: `bold()`/`boldOff()`, `dim()`/`dimOff()`, `italic()`/`italicOff()`, `underline()`/
    `underlineOff()`, `strikethrough()`/`strikethroughOff()`, `reverse()`/`reverseOff()`.
  - Output helpers: `print()`/`println()` (successors to the removed global `println()`), and `dump()` (successor
    to the removed global `inspect()`), which colors its output by value type via `Types::getBasicType()`.
  - Semantic message helpers `success()`/`error()`/`warn()`/`info()`, each printing a glyph-prefixed message with a
    conventional foreground/background pairing.
  - `link()` — emits a clickable OSC 8 hyperlink, falling back to plain text in unsupported terminals.
  - `bell()` and `hr()` (a horizontal rule).
- **`Stringify::toString()`** — successor to the removed global `to_string()` function; converts any value to a
  string without warnings or errors, falling back to `Stringify::stringify()` for values a plain `(string)` cast
  can't handle (arrays, `NAN`, non-`Stringable` objects).
- **`Stringify::prepEx()`** — successor to the removed global `ex()` function; prepares an exception message from a
  template, substituting each `'?'` placeholder, left to right, with `Stringify::abbrev()` of the matching value
  (e.g. `Stringify::prepEx('Invalid range: [?, ?]. Min must not exceed max.', $min, $max)`). Supports multiple
  placeholders in one call, unlike `ex()`, which only ever produced one abbreviated value at a time.
- **`Types::getBasicType()`** now recognizes closures, returning `'closure'` (a new case between `'enum'` and
  `'object'`). **`Types::getUniqueString()`** follows suit with a `'c:{object_id}'` format for closures (matching
  `'o:{object_id}'` for plain objects) — previously a closure fell through to the `unknown`-type case and threw
  `UnexpectedValueException`.
- **`Stringify::stringifyClosure()`** — stringifies a closure by reading back its original source code from the file
  it was declared in (via `ReflectionFunction` plus `token_get_all()` to precisely locate the closure's own token
  range), returning it exactly as written, whitespace and comments included; the surrounding statement (e.g. a
  trailing `;`) is excluded. Returns `''` if the source isn't available (e.g. a closure wrapping an internal
  function). `Stringify::stringify()` now dispatches closures to this method.
- **`OceanMoon\Core\Enums\FloatFormat`** — enum selecting `Floats::format()`'s notation: `FixedPoint`, `Scientific`,
  or `Auto` (whichever produces the more useful string).
- **`OceanMoon\Core\Enums\ExponentFormat`** — enum selecting how `Floats::format()` renders an exponent, if present:
  `AsciiLowerCaseE`/`AsciiUpperCaseE` (`e+23`/`E+23`), `AsciiMath` (`*10^23`), `UnicodeMath` (`×10²³`, the default),
  or `HtmlMath` (`&times;10<sup>23</sup>`). Has a public `format(int $exponent): string` method, independently
  usable and testable.
- **`Floats::getExponent()`** — returns a float's base-10 exponent as if written in normalized scientific notation
  (a single non-zero digit before the decimal point). Computed via `floor(log10(abs($value)))`, verified and
  adjusted by one if needed to guard against `log10()` rounding error at exact powers of 10. Throws
  `DomainException` for a non-finite value.
- **`Environment::getLocale()`** — auto-detects the locale from the HTTP `Accept-Language` header, falling back to
  PHP's current default (`Locale::getDefault()`, which always returns a value). Also adds
  **`Environment::INVARIANT_LOCALE`** (`'en_US_POSIX'`), used internally by `Floats::format()` for deterministic,
  locale-independent output.

### Changed

- **`Numbers` restored as a class**, reversing the 3.0.0 change that replaced it with plain functions in
  `Globals/numbers.php`. `isNumber()`, `sign()`, `copySign()` are back as `Numbers::` static methods, plus a new
  **`Numbers::equal()`** — exact `int`/`float` equality that never coerces types-as-equal by accident (two floats
  compare bitwise, so `NAN` never equals anything including itself, and `-0.0` equals `0.0`; a mixed `int`/`float`
  pair is equal only if the float represents that exact integer losslessly via `Floats::toInt()`, not merely "close
  enough" — correctly handles values beyond `Floats::MAX_SAFE_INT` that `Floats::isSafeInt()` would wrongly reject,
  and the `PHP_INT_MIN`/`PHP_INT_MAX` asymmetry, since only `PHP_INT_MIN` casts to `float` exactly). `isZero()` was
  not restored — `equal($value, 0)` covers it, since `equal()` already treats `-0.0` and `0.0` as equal to integer
  `0`. Callers that used the free functions (`Floats::approxCompare()`, `Comparable`'s docblock) now call
  `Numbers::` for it. `src/Globals/numbers.php` and `tests/Globals/NumbersTest.php` are removed.
- **`src/Globals/constants.php` and `src/Globals/strings.php` consolidated into a single `src/globals.php`** —
  `M_TAU`, `RECURSION`, `println()`, `inspect()`, `to_string()`, `ex()`, `write()`, `writeln()` are now declared
  directly in `OceanMoon\Core` (not the nested `OceanMoon\Core\Globals` sub-namespace) in one file, loaded via one
  `files` autoload entry, replacing the earlier plan (tried briefly in this Unreleased cycle) of keeping the three
  separate `Globals/*.php` files and just dropping the `Globals\` namespace segment from each. A `use const
  OceanMoon\Core\Globals\M_TAU;`/`use function OceanMoon\Core\Globals\ex;`-style import needs to drop the `Globals\`
  segment either way.
- **`dump_var()`** renamed to **`inspect()`**; gained a `bool $return = false` parameter to return the stringified
  value instead of printing it (returns `?string`: the value when `$return` is `true`, `null` otherwise).
- **`writeln()`**: `$value` now defaults to `''`, so calling it with no arguments prints just a newline instead of
  throwing `ArgumentCountError`.
- **`src/globals.php` reduced further to just `M_TAU` and `RECURSION`** — `println()`, `inspect()`, `to_string()`,
  `ex()`, and `write()`/`writeln()` are all removed as global functions. `println()`/`inspect()` are superseded by
  the new `Console::print()`/`println()`/`dump()` (see Added, above), which add ANSI styling; `to_string()` and
  `ex()` are superseded by the new `Stringify::toString()` and `Stringify::prepEx()`.
- Exception messages reworded throughout the package to consistently report the invalid value/type (via the new
  `Stringify::prepEx()` helper) and the expected constraint, instead of a fixed generic string:
  - `Arrays::quoteValues()`/`toSerialList()`: `'Invalid array value type: {type}. Must be string.'`.
  - `Floats::approxEqual()`/`approxCompare()`: tolerance validation extracted into a shared, private
    `validateTolerances()` helper; also now rejects non-finite tolerances, not just negative ones. Messages are now
    `'Invalid relative/absolute tolerance: {value}. Must be finite and non-negative.'`.
  - `Floats::rand()`/`randUniform()`: separate `'Invalid minimum: ...'`/`'Invalid maximum: ...'`/`'Invalid range:
    [...]'` messages replace the previous combined `'Min and max must be finite.'`/`'Min must be less than or equal
    to max.'`.
  - `Integers::pow()`: `'Overflow in integer exponentiation.'`, consistent with the other arithmetic overflow
    messages.
  - `Integers::fromSubscript()`/`fromSuperscript()`: the invalid character is no longer wrapped in quotes.
  - `Stringify::stringifyResource()`: `'Invalid value type: {type}. Must be a resource.'`.
  - `Stringify::stringifyString()`: undetectable/unconvertible encoding messages simplified to `'String encoding
    cannot be detected.'` / `'String cannot be converted to UTF-8.'` (previously repeated "was not UTF-8" in both).
  - `Types::usesTrait()`/`getTraits()`: `"Invalid class name: '{name}'. Must be a class, interface, or trait."`
    (previously didn't state the constraint).
- **`Stringify::setIndent()`** now accepts `0` (previously required `> 0`); the indent must simply be non-negative.
  Both `setIndent()` and **`Stringify::setMaxLineLength()`** now throw `DomainException` instead of
  `InvalidArgumentException` for invalid values, consistent with the package's exception conventions (see
  `docs/guidelines/EXCEPTIONS.md`).
- **`Stringify::stringifyFloat()`**: non-finite values are now stringified via `var_export()` instead of a
  warning-suppressed cast; output is unchanged (`'NAN'`, `'INF'`, `'-INF'`).
- **`Integers::gcd()`**:
  - No-arguments case now throws `BadMethodCallException` (`'At least one integer is required.'`) instead of
    `ArgumentCountError`, consistent with the package's convention for invalid-argument-count errors (see
    `docs/guidelines/EXCEPTIONS.md`).
  - `PHP_INT_MIN` is no longer rejected outright. Euclid's algorithm now runs on the raw signed values (`abs()` is
    only applied once, to the final result), so `PHP_INT_MIN` combined with any other non-zero, non-`PHP_INT_MIN`
    value now returns the correct result (e.g. `gcd(PHP_INT_MIN, 8) === 8`) instead of always throwing. Only the
    genuinely unrepresentable case — every argument is `0` or `PHP_INT_MIN`, so the true result is `PHP_INT_MIN`'s
    own magnitude (`2^63`) — still throws, now as `OverflowException` rather than `DomainException`, consistent with
    `Integers::pow()`'s overflow handling.
- **`Stringify::stringifyString()`**: strings are now rendered single-quoted (only `\` and `'` are escaped) instead
  of double-quoted. Control characters like `\n`/`\t` are no longer escaped and are embedded as literal characters —
  still valid, parseable single-quoted PHP, just multi-line for values containing a real newline.
- **`Floats::format()`** overhauled:
  - The old `string $specifier` parameter (one of the 8 raw sprintf letters `e/E/f/F/g/G/h/H`) is replaced by the
    new `FloatFormat`/`ExponentFormat` enums (see Added, above). The old boolean `$ascii` flag is gone —
    `ExponentFormat` now offers five styles instead of just ASCII vs. Unicode. Invalid enum values are rejected by
    PHP's own type system, so the old `DomainException` for an invalid specifier letter no longer applies.
  - New signature: `format(float $value, int $precision = 6, bool $trimZeros = true, FloatFormat $format =
    FloatFormat::Auto, ExponentFormat $expFormat = ExponentFormat::UnicodeMath, RoundingMode $roundingMode =
    RoundingMode::HalfAwayFromZero)`. `$precision` is no longer nullable (default `6`); its meaning depends on
    `$format` — decimal places for `FixedPoint`, significant digits for `Scientific`/`Auto`. `$trimZeros` is no
    longer a three-state nullable "auto" flag — it's a plain bool, now defaulting to `true`.
  - **`$roundingMode`** is new — implemented via `NumberFormatter` internally, but exposed through the same
    `RoundingMode` enum used by `round()`, `Rational::round()`, and `Complex::round()`, defaulting to
    `HalfAwayFromZero` rather than sprintf's round-half-to-even.
  - **`FloatFormat::Auto`**'s selection logic no longer mimics sprintf's `%g`/`%h` cutover rule. It now formats the
    value both ways and picks whichever preserves more real information and avoids excessive leading/trailing
    zeros — comparing actual digit counts (via a private `analyzeDigits()` helper) rather than comparing the
    value's exponent against `$precision`, so it won't switch to scientific notation and needlessly discard
    precision when fixed-point can show the value just as compactly and more exactly (e.g. `1234567.89` stays
    `'1234567.89'` rather than becoming a lossy `'1.23457E+6'`).
  - Non-finite values (`NAN`, `±INF`) are now returned via their default PHP string representation regardless of
    the other parameters, rather than passing through `sprintf()`.
  - **Known follow-up (not included in this change)**: `Quantities\Quantity::format()` delegates positionally to
    `Floats::format()`'s old signature and will need a matching update in a subsequent Quantities release.

### Fixed

- **`M_TAU`** (`src/globals.php`) was defined via `define('M_TAU', ...)` inside `namespace OceanMoon\Core`, which —
  unlike `const` — always defines a *global* constant regardless of the surrounding namespace. This meant
  `OceanMoon\Core\M_TAU` never actually existed as a symbol, so the documented `use const OceanMoon\Core\M_TAU;`
  import (used by `Floats::wrap()`'s own tests) threw `Undefined constant`. Now a plain `const M_TAU = 2 * M_PI;`:
  correctly namespaced, and immune to any future PHP core addition of a global `M_TAU` (a namespaced constant can't
  collide with one in the global namespace, so the earlier `defined()` guard is no longer needed either).
- **`Stringify::stringifyListArray()`'s pretty-print grid format** — eligibility was based on whether the compact
  form contained a newline, which a list of nested arrays (e.g. `[[1, 2], [3, 4]]`) never does on its own, so it was
  wrongly collapsed onto one line via grid format instead of printing one array per line. Now checks whether every
  element is `null` or a scalar directly, matching the format's actual intent.
- **`Stringify::abbrev()`**: the closing character appended after truncating a `string` value was still hardcoded to
  `"`, left over from before `stringifyString()` switched to single-quoted output. Now correctly uses `'`.
- **`Stringify::stringifyListArray()`'s pretty-print grid format**: the items-per-line calculation only guarded
  against exactly `0`, not negative values (possible when an item's width leaves less than one column's worth of
  space), so it could still break the grid layout instead of falling back to one item per line. Now guards against
  any non-positive value.
- **`Environment::getLocale()`**: the `Accept-Language` header check used `!empty(...)`, which would silently treat
  a non-string header value (e.g. an array, in principle possible via `$_SERVER`) as usable input. Now explicitly
  checks `isset(...) && is_string(...)`.
- **`Floats::wrap()`**: a non-finite or non-positive `$unitsPerTurn` (e.g. `0.0`, a negative value, or `NAN`) was
  silently accepted, producing `NAN` or an output outside either documented range instead of failing loudly. Now
  throws `DomainException`, consistent with the other parameter-validating methods in this class.

### Removed

- **`NUMBER_REGEX`** removed from `src/Globals/constants.php`. It wasn't a safe drop-in fragment for every consumer's
  needs — it bakes in its own optional leading sign, which conflicts with callers (like `OceanMoon\Math\Complex::
  fromString()`) that need to track a sign separately from the numeric magnitude via their own surrounding capture
  group.
- **`FloatAssertions::assertApproxZero()`** removed. It had no call sites anywhere across the packages — tests
  checking a near-zero result use `assertApproxEqual(0.0, $actual, absTol: ...)` directly instead.

### Documentation

- **`docs/Numbers.md` restored**, reversing 3.0.0's removal, and merged with the newer edge-case examples
  (`equal()`'s `2^60`/`Floats::isSafeInt()` caveat and the `PHP_INT_MIN`/`PHP_INT_MAX` asymmetry) written for the
  short-lived `Globals/numbers.php` version of the docs.
- **`docs/Globals/{Constants,Strings,Numbers}.md` replaced by a single `docs/Globals.md`**, matching the source
  consolidation into `src/globals.php`. Two broken relative links carried over from the old, one-level-deeper
  `docs/Globals/` location (`../Arrays.md`, `../Stringify.md`) are fixed to `Arrays.md`/`Stringify.md`, and
  `docs/Floats.md`'s "See Also" links updated to match.
- **`README.md`**: `Numbers` moved from the `## Globals` section into `## Classes` (next to `Integers`), with its
  description updated for the restored class's actual methods instead of the removed free functions; the `## Globals`
  section collapsed from three separate `Constants`/`Strings`/`Numbers` links down to one, pointing at the merged
  `docs/Globals.md`.
- **`tests/NumbersTest.php` restored** alongside the class, with its `#region` headers updated to match the current
  `Method methodName() tests.` convention (previously one region covered both `sign()` and `copySign()`; now split),
  two new boundary-case tests for `equal()` (the `2^60` and `PHP_INT_MIN`/`PHP_INT_MAX` cases mentioned above), and
  its `@phpstan-ignore` comments updated from `function.alreadyNarrowedType`/`function.impossibleType` to
  `staticMethod.alreadyNarrowedType`/`staticMethod.impossibleType`, matching the switch from free-function to
  static-method calls.
- **`docs/Arrays.md`**: documented `removeRecursion()`, which was missing entirely.
- **`docs/Stringify.md`**: corrected `abbrev()`'s documented defaults (`$maxLen` default is `32`, not `30`; minimum
  is `3`, not `10`), added its missing `stringifyString()` `@throws DomainException` documentation, and added a
  `stringifyClosure()` section plus a "Closure support" mention in Key Features.
- **`docs/Floats.md`**: added missing `**Throws:** RuntimeException` blocks for `toHex()` and `ulp()` (both require
  a 64-bit system); added a new `getExponent()` section (previously undocumented); rewrote the `format()` section
  for the new `FloatFormat`/`ExponentFormat`/`RoundingMode`-based signature, replacing the stale string-specifier/
  `$ascii` documentation.
- **`docs/Globals.md`**: removed the `println()`/`inspect()` sections (moved to `Console`, see Added, above); "See
  Also" updated to point to their replacements (`Console`, `Stringify::toString()`/`prepEx()`).
- Test coverage added for `Types::getBasicType()`'s closure detection and `Stringify::stringifyClosure()` (arrow vs.
  brace-style syntax, closures embedded inline as call/array arguments, nested-bracket boundary detection, and the
  no-source fallback).
- Test coverage added for `Floats::getExponent()`'s `log10()`-rounding-error correction (the one branch reachable on
  this platform; the other is marked `@codeCoverageIgnore`, being unreachable with this libm), all 8 `RoundingMode`
  cases via `Floats::format()`, and `Console::getInstance()`'s singleton-reuse branch.
- **`docs/Console.md`** (new) — full documentation for the `Console` class, previously entirely undocumented despite
  being a 692-line class: the 16 color constants and `TYPE_COLOR`'s exact type-to-color mapping, singleton
  construction, and the Color/Attribute/Style/Output method groups. Linked from a new `Console` entry in
  `README.md`'s `## Classes` section.
- **`docs/Enums/FloatFormat.md`, `docs/Enums/ExponentFormat.md`** (new) — full documentation for both enums.
  Linked from a new `## Enums` section in `README.md`.
- **`docs/Environment.md`**: added a `## Constants` section documenting `INVARIANT_LOCALE` (including why
  `Floats::format()` uses it instead of `getLocale()` — so a request's `Accept-Language` header can't change
  whether formatted output uses `.` or `,` as its decimal separator), and documented `getLocale()` itself, neither
  of which had been written up before. `Environment` was also missing from `README.md`'s `## Classes` list
  entirely; added.
- **`docs/Traits/Asserts/FloatAssertions.md`**: updated for `assertApproxZero()`'s removal (see Removed, above) —
  its two examples that used it now call `assertApproxEqual(0.0, ..., absTol: ...)` directly.
- Accuracy pass across `docs/Floats.md`, `docs/Integers.md`, `docs/Stringify.md`, and `docs/Types.md`, checked
  against current source: two `Floats.md` example results that were wrong at a floating-point precision boundary
  (`approxEqual()`/`approxCompare()`); `Integers.md`'s `pow()` and `gcd()` sections describing behavior that no
  longer matches the source (`pow()`'s ±1-base exception, a stale `gcd()` bullet contradicting its own examples);
  `Stringify.md` missing `stringifyBool()`/`stringifyInt()` entirely, a wrong pretty-print grid example, two false
  claims about `stringifyEnum()`/`stringifyObject()` behavior, and three missing `@throws`; `Types.md` missing the
  `closure` case in `getBasicType()`'s docs (see `Types::getBasicType()`, Added, above). `docs/Numbers.md` was
  audited too and found already accurate.

### Tests

- **`tests/Floats/FloatsTest.php`** (1562 lines) split into one file per region, matching `Floats.php`'s own
  structure: `FloatsComparisonTest`, `FloatsTransformationTest`, `FloatsConversionTest`, `FloatsIntegerTest`,
  `FloatsInspectionTest` (`FloatsRandomTest`/`FloatsBitOperationsTest` were already split out and untouched).
  Along the way, several oversized `#region` blocks that lumped multiple methods' tests together (e.g. one region
  covering `frac()`, `wrap()`, `toHex()`, and `toInt()` all at once) were split into one region per method, per
  this project's test-writing convention. All 100 original test methods carried over with no loss or duplication.
- **`tests/Traits/FloatAssertionsTest.php`**: removed the `assertApproxZero()` test region, matching its removal
  from the trait.
- **`tests/TypesTest.php`**: added `testGetStringKeyClosure()` for `getUniqueString()`'s new `closure` case
  (identity-based key, no collision with `object` keys), and added a closure to `testGetStringKeyUniqueness()`.

## [3.0.0] - 2026-07-17

### Added

- **`ArithmeticException`** (`OceanMoon\Core\Exceptions\ArithmeticException`, extends `DomainException`) — thrown when
  an arithmetic operation has no defined result for its operands (division by zero, logarithm of a non-positive number
  or with base 0 or 1, etc.). Displaces `DivisionByZeroError` for this kind of failure in userland value types: `Error`
  types are reserved for engine-emitted conditions, not something userland code should throw. Not to be confused with
  PHP's own `ArithmeticError`, which is a genuinely different, engine-level thing.
- **`OceanMoon\Core\Globals` namespace** — new home for free functions and constants shared across the package family,
  split into `src/Globals/constants.php`, `src/Globals/strings.php`, and `src/Globals/numbers.php`:
  - `is_number()`, `is_zero()`, `sign()`, `copy_sign()` — see Removed (`Numbers` class) below.
  - `println()`, `dump_var()`, `to_string()`, `write()`, `writeln()` — see Removed (`Strings`/`functions.php`) below.
  - `M_TAU`, `RECURSION`, `NUMBER_REGEX` constants.
- **`Arrays::removeRecursion()`** — returns a copy of an array with any circular (self-referencing) sub-arrays replaced
  by the `RECURSION` marker, so the result can be safely inspected, iterated, or serialized without triggering infinite
  recursion. Detects cycles by parsing `print_r()`'s own recursion-detection output (which preserves the position of
  each recursive reference) rather than reimplementing cycle detection from scratch — `===` compares arrays by value,
  not reference identity, so there's no other built-in way to identify "is this the same array instance as an
  ancestor?".
- **`Stringify::stringifyBool()`, `stringifyInt()`, `stringifyEnum()`, `stringifyObject()`, `stringifyResource()`** —
  promoted to public, individually-usable methods (previously internal to the `stringify()` dispatcher).

### Changed

- **Comparison traits (`Equatable`, `Comparable`, `ApproxEquatable`, `ApproxComparable`) type policy finalized**: every
  method is typed `mixed $other`, never `self` — `self` is invariant across both trait composition and inheritance, so a
  subclass overriding e.g. `equal()` would narrow the type and PHP would reject it as an incompatible override.
  Implementations must check `$other`'s type explicitly (typically `instanceof self`) and **throw** — typically
  `InvalidArgumentException` — for anything that isn't a deliberate, documented exception to same-type-only comparison,
  rather than attempting a conversion. This mirrors why `==`/`!=` are avoided in favor of `===`/`!==`: implicit type
  juggling in comparisons is a recurring source of bugs. Widening to accept a related type (e.g. `Complex` accepting
  `int`/`float`) should be rare and mathematically justified on a case-by-case basis, not a general "convert whatever
  you're given" policy.
- **`Stringify::abbrev()`**: default `$maxLen` changed from 30 to 32; now throws `DomainException` for `$maxLen < 3`
  (previously unvalidated).
- **`Stringify::stringifyArray()`/`stringifyObject()`**: circular references are now detected and replaced via the new
  `Arrays::removeRecursion()` (see Added) instead of requiring callers to track cleanliness themselves.
- Internal renames for clarity: `stringifyList()` → `stringifyListArray()`, `stringifyDictionary()` →
  `stringifyAssociativeArray()`.
- **`Floats::TAU`** removed; use the `OceanMoon\Core\Globals\M_TAU` constant directly.
- **`Stringify::stringifyResource()`** — Now combines the resource id (via `get_resource_id()`) with the resource type
  from `get_debug_type()`, e.g. `'resource #5 (stream)'`, instead of just `'resource (stream)'`.
- **Region comments** — Switched from `// region` / `// endregion` to `#region` / `#endregion` (VS Code-compatible)
  throughout the package.
- Cast spacing normalized to PSR-12 style throughout (e.g. `(int)$x` → `(int) $x`).

### Removed

- **`Numbers` class** (`src/Numbers.php`) — replaced by plain functions in `Globals/numbers.php` (see Added).
- **`Numbers::REGEX`** — moved to `OceanMoon\Core\Globals\NUMBER_REGEX`.
- **`Strings` class** (`src/Strings.php`) and **`src/functions.php`** — superseded by plain functions in
  `Globals/strings.php` (see Added).
- **`IncomparableTypesException`** — removed. Comparison methods now throw plain `InvalidArgumentException` for
  incompatible types instead, consistent with the rest of the package's exception conventions (see
  `docs/guidelines/EXCEPTIONS.md`); a dedicated exception type for this one case stopped earning its keep once the
  comparison methods were narrowed to a small, fixed set of accepted types (see Changed).
- **`docs/Numbers.md`, `docs/Functions.md`, `docs/Exceptions/IncomparableTypesException.md`, `docs/Strings.md`** —
  removed along with the code they documented; replaced by `docs/Globals/Numbers.md`, `docs/Globals/Strings.md`,
  `docs/Globals/Constants.md`, and `docs/Exceptions/ArithmeticException.md`.

### Fixed

- **`Floats::frac()`** — Docblock corrected: the identity `x = trunc(x) + frac(x)` is described as holding "even for
  non-finite numbers" (previously said "even for infinities", which didn't mention `NAN`).
- **`Equatable`'s own docblock contradicted its real-world usage**: it said implementations should "return false (not
  throw)" for incomparable types, while every actual implementation across the package family throws. Corrected to
  document the throwing contract.

### Documentation

- Rewrote `Equatable.md`, `Comparable.md`, `ApproxEquatable.md`, `ApproxComparable.md`, and `ComparisonTraits.md` for
  the finalized `mixed`-not-`self`, strict-throw type policy; removed all `identical()`/`IncomparableTypesException`
  references (including the now-broken external link some of these files carried to a different, older repo). Trimmed
  the inline `<code>` usage examples from the trait `.php` docblocks in favor of the `.md` files, which already covered
  the same ground — the `.php` docblocks now carry only the contract information relevant while actually implementing
  the abstract methods (parameter/return meaning, the `mixed`-not-`self` rationale), with a plain-text pointer to the
  corresponding `.md` file for full examples.
- New `docs/Exceptions/ArithmeticException.md`, `docs/Globals/Numbers.md`, `docs/Globals/Strings.md`,
  `docs/Globals/Constants.md`.
- Updated `Stringify.md`: added a section documenting `to_string()`, updated the `stringifyResource()` example, and
  reorganized method sections to match the source file's new grouping (Main Stringification Methods / Type-Specific
  Stringification Methods).
- `README.md`: "Functions" section replaced with "Globals" (Constants / Strings / Numbers); Exceptions section updated
  for `ArithmeticException`, removed `IncomparableTypesException`.
- All markdown files rewrapped to a consistent 120-character line width.

### Tests

- `tests/Globals/NumbersTest.php` — full coverage for all four functions (`is_number()`, `is_zero()`, `sign()`,
  `copy_sign()`; 21 tests), using inline `// @phpstan-ignore function.alreadyNarrowedType` / `function.impossibleType`
  comments (matching the convention already used in `Integers.php`) in place of the blanket `ignoreErrors` block
  previously in `phpstan.neon`.
- New `Stringify` test (`testStringifyArrayPrettyPrintMultilineItem`) exercising the multiline-fallback branch in
  `stringifyListArray()`'s pretty-print formatting — a list containing a nested associative array (which always
  pretty-prints multiline) alongside a scalar.
- Fixed `testStringifyObjectPrettyPrint`: a stale regex used `+` as a quantifier ("4-or-more spaces") where the actual
  output has a literal `+` character (the UML visibility marker for public properties) — the test's own bug, not
  `Stringify`'s; the sibling `testStringifyComplexNesting` already correctly asserted the `+` marker.
- New `tests/ArraysTest.php` coverage for `removeRecursion()`.
- Updated exception-message assertions across the test suite for the removal of `IncomparableTypesException`.

---

## [2.0.0] - 2026-06-18

### Changed

- **Renamed package** from `galaxon/core` to `oceanmoon/core` — update your `composer.json` require accordingly.
- **Renamed PHP namespaces** from `Galaxon\Core\*` to `OceanMoon\Core\*` throughout all source and test files.
- Updated dev dependency `galaxon/coding-standard` → `oceanmoon/coding-standard: ^2.0`.
- `composer.json`: updated author email, homepage, and support URLs to Ocean Moon Software.

---

## [1.6.0] - 2026-04-09

### Added

- **Floats::format()** — New method for formatting floats as strings with control over precision, notation, and trailing
  zeros. Supports Unicode scientific notation (e.g. `1.50×10³`) as well as ASCII. Moved from `Quantity::formatValue()`.
  When `$precision` is `null`, defaults to 6 for `e`/`E`/`f`/`F` and 7 for `g`/`G`/`h`/`H` so that `g` is genuinely "the
  shorter of `e` and `f` at matching precision". Format string is always explicit `%.Nspec`.
- **Traits reorganisation** — Traits split into `Traits/Asserts/` (testing assertion traits) and `Traits/Comparison/`
  (value comparison traits).

### Changed

- **Integers::fromSubscript() / fromSuperscript()** — Now throw `FormatException` instead of `DomainException` when the
  input contains characters that are not valid sub/superscript digits. Invalid input is a parsing/format problem, not a
  domain-of-values problem.

### Removed

- **NullArgumentException** — Removed before its first release. The downstream Quantities use case that motivated it
  ended up not needing it, and no other consumers materialised. Never shipped, so not a breaking change.

### Fixed

- **Floats::frac()** — Returns `0.0` for ±INF instead of `NAN`, so the identity `x = trunc(x) + frac(x)` now holds for
  all values.

---

## [1.5.1] - 2026-04-02

### Added

- **Strings** — New static utility class for string conversion and output:
  - `toString()` — Convert any value to a string. Strings pass through, Stringable objects use `__toString()`, all other
    types go through `Stringify::stringify()`.
  - `print()` — Print a value to stdout via `toString()`.
  - `println()` — Print a value to stdout with a newline via `toString()`.
- **docs/Strings.md** — Documentation for the new Strings class.
- **StringsTest** — 12 tests covering toString, print, and println.

### Changed

- **functions.php** — `println()` now delegates to `Strings::println()`.

### Removed

- **Stringify::print()** and **Stringify::println()** — replaced by `Strings::print()` and `Strings::println()`.

### Documentation

- Added Strings to README Classes section.
- Removed print/println from Stringify.md.

---

## [1.5.0] - 2026-03-30

### Added

- **functions.php** - New namespaced convenience functions with `files` autoload entry:
  - `println()` - Print a value with a newline. Strings output as-is, `Stringable` objects use `__toString()`, all other
    types go through `Stringify::stringify()`.
- **Numbers::isNumber()** - Strict type check for `int` or `float` (rejects numeric strings unlike `is_numeric()`).
- **Numbers::isZero()** - Check if a number is zero (`0`, `0.0`, or `-0.0`).
- **Types::getBasicType()** - Now returns `'enum'` for `UnitEnum` instances (previously returned `'object'`).
- **Types::getUniqueString()** - Now supports enums with format `"e:{ClassName}::{CaseName}"`.

### Changed

- **Stringify** - Indent and max line length are now configurable:
  - `setIndent()` / `getIndent()` - Configure spaces per indentation level.
  - `setMaxLineLength()` / `getMaxLineLength()` - Configure pretty-print line wrapping.
  - `resetDefaults()` - Reset both to defaults.
  - Renamed constant `NUM_SPACES_INDENT` → `DEFAULT_INDENT`.
  - Skip grid format when items are too wide for 2 per line.
- **Floats::floatToBits()** - Simplified from byte-array loop to single `pack`/`unpack` call.
- **Floats::bitsToFloat()** - Same simplification.
- **Exception messages** - Standardised across all classes to follow the guidelines in `EXCEPTIONS.md`: "state what went
  wrong" format with offending values and concise constraints where useful.
- **composer.json** - Updated `galaxon/coding-standard` dependency to `^1.0`.

### Fixed

- **Floats::tryConvertToInt()** - Fixed overflow bug where floats near `PHP_INT_MAX` could silently overflow to
  `PHP_INT_MIN` during `(int)` cast. Now detects and returns `null`.

### Removed

- **`dump()`** function removed from `functions.php`.
- **`_dev/`** directory removed from version control.

### Documentation

- **Functions.md** - New documentation page for `println()` and `is_number()`, including autoloading setup.
- **README.md** - Added Functions section linking to new docs.
- **Types.md** - Added `enum` to `getBasicType()` return values and examples. Added `enum` format to
  `getUniqueString()`. Fixed `throws` annotation (`TypeError` → `UnexpectedValueException`).
- **Floats.md** - Fixed `bitsToFloat()` examples that used overflowing hex literals. Added note about PHP signed int
  overflow.
- **Stringify.md** - Updated for configurable indent/max line length, new constant name.

### Tests

- **FunctionsTest** - Tests for `println()` (strings, ints, floats, booleans, null, empty, stringable objects).
- **NumbersTest** - Tests for `isNumber()` and `isZero()`.
- **FloatsBitOperationsTest** - 15 new tests for `floatToBits()` and `bitsToFloat()` covering known bit patterns,
  special values (±0, ±INF, NAN), and round-trip verification.
- **TypesTest** - Added `testGetBasicTypeEnum` (unit and backed enums) and `testGetStringKeyEnum` (unique string format,
  different cases, different enum classes).
- **StringifyTest** - Additional tests for configurable indent and max line length.

---

## [1.4.0] - 2026-02-25

### Changed

- **Stringify** - Major overhaul of output formatting:
  - Strings now use single quotes with backslash and single-quote escaping instead of `json_encode()` double quotes.
    Unicode characters are preserved as-is.
  - Arrays use PHP square bracket syntax for both lists and associative arrays (`['key' => 'value']`) instead of
    JSON-style formatting with curly braces and colons.
  - Objects use `ClassName {+prop => 'value'}` format instead of `<ClassName +prop: "value">`.
  - Resources use `get_debug_type()` output (e.g. `resource (stream)`, `resource (closed)`) instead of custom format
    with type and id. Closed resources are now supported.
  - `null`, `bool`, and `int` are now rendered inline instead of via `json_encode()`.
  - `echo()` renamed to `print()` and now accepts a `$prettyPrint` parameter.
  - `stringifyFloat()` uses `(string)` cast instead of `sprintf('%.16H')`, with early return for non-finite values.
    Suppresses PHP 8.5 NAN cast warning.
  - `abbrev()` now uses `mb_strlen()`/`mb_substr()` for multibyte-safe truncation.
  - `stringifyObject()` now detects enums via `instanceof UnitEnum` and delegates to `stringifyEnum()`. Property names
    are aligned in pretty-print mode.
  - `stringifyArray()` now supports three pretty-print layout strategies for scalar lists: single-line, grid, and
    one-per-line. Associative arrays align keys. New `$maxLineLen` parameter.

### Added

- **Stringify** - New methods:
  - `stringifyString()` - Single-quoted output with backslash/single-quote escaping and UTF-8 normalisation.
  - `stringifyEnum()` - Renders enums as `Fully\Qualified\ClassName::CaseName`.
  - `println()` - Like `print()` but appends a newline.
  - Constants `NUM_SPACES_INDENT` (4) and `DEFAULT_MAX_LINE_LENGTH` (120).

- **Arrays** - New methods:
  - `toSerialList()` - Convert an array of strings to a serial list with Oxford comma (e.g. `'a, b, and c'`). Supports
    custom conjunctions.
  - `removeValue()` - Remove all instances of a value from an array using strict comparison. Keys are preserved.

### Fixed

- **Floats::toHex()** - Added missing `@throws RuntimeException` to docblock for 64-bit requirement.

### Documentation

- **Stringify.md** - Completely rewritten for new output format, new methods, and updated examples.
- **Arrays.md** - Added documentation for `toSerialList()` and `removeValue()`. Updated overview to include String
  Methods and Transformation Methods sections.

### Tests

- **StringifyTest** - Completely rewritten (36 tests, 100+ assertions) covering single-quoted strings, escaping, UTF-8
  conversion, undetectable encoding, open/closed resources, enums, grid layout, and all other formatting changes.
- **ArraysTest** - Added 15 tests for `toSerialList()` (empty, one/two/three/four items, custom conjunction, non-string
  validation) and `removeValue()` (existing/missing values, multiple instances, key preservation, strict comparison,
  null removal).

---

## [1.3.0] - 2026-01-30

### Added

- **FloatAssertions trait** - PHPUnit assertions for approximate floating-point comparison
  - `assertApproxEqual()` - Assert two floats are approximately equal with informative failure messages
  - `assertApproxZero()` - Assert a float is approximately zero
  - Produces detailed failure messages showing expected/actual values and differences
  - Replaces uninformative `assertTrue(Floats::approxEqual(...))` pattern

### Documentation

- **FloatAssertions.md** - New documentation for the FloatAssertions trait
- **Traits.md** - Added "Testing Trait" section for FloatAssertions
- **README.md** - Added FloatAssertions to the Traits section

### Tests

- Added 12 tests for FloatAssertions trait covering passing/failing assertions, error messages, infinity handling, and
  assertApproxZero

---

## [1.2.0] - 2026-01-27

### Added

- **Arrays class** - New extraction methods (PHP 8.5 polyfills):
  - `first()` - Get the first value in an array, throws `LengthException` if empty
  - `last()` - Get the last value in an array, throws `LengthException` if empty

- **FormatException** - New exception class for string format validation errors
  - Extends `DomainException`
  - For use when a string has an invalid format for the desired operation

### Fixed

- **Floats::normalizeZero()** - Simplified logic using `=== 0.0` comparison
- **Floats::tryConvertToInt()** - Added bounds check for `PHP_INT_MIN`/`PHP_INT_MAX` before conversion to prevent
  overflow
- **Stringify::stringify()** - Added error suppression for non-finite float string conversion

### Tests

- Added 11 tests for `Arrays::first()` and `Arrays::last()`
- Added 18 tests for `Integers::isSubscript()`, `isSuperscript()`, `fromSubscript()`, `fromSuperscript()`
- Added region comments to ArraysTest and IntegersTest for better organization

### Documentation

- **Arrays.md** - Added "Extraction Methods" section with `first()` and `last()` documentation
- **Integers.md** - Renamed "Conversion Methods" to "Subscript/Superscript Methods", added documentation for validation
  and parsing methods

---

## [1.1.0] - 2026-01-18

### Added

- **Floats class** - New methods for integer/fractional part operations:
  - `isApproxInt()` - Check if a float is approximately an integer within tolerance
  - `trunc()` - Truncate a float towards zero (integer part)
  - `frac()` - Get the fractional part of a float (satisfies x = trunc(x) + frac(x))

- **Integers class** - New methods for Unicode sub/superscript conversion:
  - `isSubscript()` - Check if a string is a valid subscript integer
  - `isSuperscript()` - Check if a string is a valid superscript integer
  - `fromSubscript()` - Convert subscript string to integer (e.g., ₁₂₃ → 123)
  - `fromSuperscript()` - Convert superscript string to integer (e.g., ¹²³ → 123)

- **Numbers class**:
  - `REGEX` constant - Regular expression pattern for matching numbers

### Documentation

- **Floats.md** - Added documentation for `isApproxInt()`, `trunc()`, and `frac()`
- **Trait documentation** - Updated to use `IncomparableTypesException`
- Minor documentation updates to Arrays.md and IncomparableTypesException.md

### Tests

- Added 7 tests for `isApproxInt()` covering exact integers, near-integers, fractions, logarithms, custom tolerance, and
  non-finite values
- Added 6 tests for `trunc()` and `frac()` covering positive/negative values, zero, non-finite values, and the identity
  property

---

## [1.0.1] - 2026-01-05

### Added

- **IncomparableTypesException** - Documentation and tests for the custom exception
  - Added PHPDoc comments to the exception class
  - Added `docs/Exceptions/IncomparableTypesException.md`
  - Added `tests/Exceptions/IncomparableTypesExceptionTest.php` (10 tests)
  - Added Exceptions section to README.md

### Fixed

- Fixed GitHub URLs in README.md (`PHP-Core` → `Galaxon-PHP-Core`)
- Removed `Types::createError()` from documentation (method was removed)

---

## [1.0.0] - 2026-01-04

### First Stable Release

This is the first stable release of Galaxon Core, ready for publication on Packagist.

### Breaking Changes

- **Exception types standardized** - All domain validation errors now throw `DomainException` consistently:
  - `Floats::approxEqual()` - Throws `DomainException` for negative tolerances (was `ValueError`)
  - `Floats::approxCompare()` - Throws `DomainException` for NAN or negative tolerances (was `ValueError`)
  - `Floats::rand()` - Throws `DomainException` for non-finite min/max (was `ValueError`)
  - `Floats::randUniform()` - Throws `DomainException` for non-finite min/max (was `ValueError`)
  - `Floats::assemble()` - Throws `DomainException` for invalid components (was `ValueError`)
  - `Integers::pow()` - Throws `DomainException` for negative exponents (was `UnderflowException`)
  - `Integers::gcd()` - Throws `DomainException` for `PHP_INT_MIN` (was `RangeException`)
  - `Numbers::copySign()` - Throws `DomainException` for NAN (was `ValueError`)
  - `Stringify::stringify()` - Throws `DomainException` for circular references (was `ValueError`)
  - `Stringify::stringifyArray()` - Throws `DomainException` for circular references (was `ValueError`)
  - `Stringify::abbrev()` - Throws `DomainException` for maxLen < 10 (was `ValueError`)
  - `Types::usesTrait()` - Throws `DomainException` for invalid class name (was `ValueError`)
  - `Types::getTraits()` - Throws `DomainException` for invalid class name (was `ValueError`)

### Changed

- **composer.json** - Updated for Packagist publication:
  - Added keywords for discoverability
  - Added author information
  - Added homepage and support URLs
  - Improved description

### Documentation

- Updated all class documentation to reflect new exception types

---

## [0.6.0] - 2025-12-27

### Added

- **Environment class** - New utility class for runtime environment detection
  - `is64Bit()` - Check if the system is 64-bit
  - `require64Bit()` - Throw `RuntimeException` if not 64-bit (used by Floats bit operations)

- **Integers formatting methods** - Convert integers to Unicode sub/superscript
  - `SUBSCRIPT_CHARACTERS` constant - Unicode subscript character mappings
  - `SUPERSCRIPT_CHARACTERS` constant - Unicode superscript character mappings
  - `toSubscript()` - Convert integer to subscript characters (e.g., 123 → ₁₂₃)
  - `toSuperscript()` - Convert integer to superscript characters (e.g., 123 → ¹²³)

### Changed

- **Floats::ulp()** - Now uses `next()` for exact ULP calculation instead of approximation
- **Integers::pow()** - Now throws `UnderflowException` instead of `ValueError` for negative exponents
- **Floats class** - Reorganized with region markers for better code navigation

### Tests

- **Floats::wrap() tests** - Expanded from 1 test with 4 assertions to 9 tests with 57 assertions
  - Added tests for degrees (360) with signed and unsigned ranges
  - Added boundary condition tests (included/excluded bounds)
  - Added tests for radians (default), gradians, turns, and hours
  - Fixed deprecated `assertEquals` with delta to use `assertEqualsWithDelta`

### Documentation

- **New documentation**: `docs/Environment.md`
- **Floats.md** - Comprehensive rewrite of `wrap()` documentation
  - Added Returns, Behavior, and Use Cases sections
  - Added examples with degrees, radians, gradians, turns, and hours
  - Added table explaining boundary inclusion/exclusion rules
- **Integers.md** - Added documentation for formatting methods
- **Various docs** - Minor updates to Numbers.md, Stringify.md, Types.md, and trait documentation

---

## [0.5.0] - 2025-12-10

### Breaking Changes

- **`Types::haveSameType()` renamed to `Types::same()`**
  - Shorter, cleaner name for checking if two values have the same type
  - Update any code using `Types::haveSameType($a, $b)` to `Types::same($a, $b)`

- **`Comparable::checkSameType()` removed**
  - Type checking is now the responsibility of the `compare()` implementation
  - Classes using this trait should handle type checking within their `compare()` method

### Changed

- **`Numbers::equal()`** - Improved int/float comparison logic
  - Now uses `Types::same()` for same-type comparison
  - Uses `Floats::tryConvertToInt()` for lossless cross-type comparison
  - More accurate than previous float casting approach

- **`Floats::compare()`** - Simplified implementation to single expression

- **Comparable trait** - Streamlined comparison methods
  - `greaterThan()` and `greaterThanOrEqual()` now rely on `compare()` for type checking
  - Reduces redundant type checks

- **ApproxComparable trait** - Removed redundant type check from `approxCompare()`

### Documentation

- Updated trait documentation to reflect new type checking approach
- README.md minor fixes

---

## [0.4.0] - 2025-02-08

### Breaking Changes

- **Equatable converted from interface to trait**
  - Changed from `interface Equatable` to `trait Equatable`
  - Classes must now use `use Equatable;` instead of `implements Equatable`
  - Provides better composition with other comparison traits
  - Namespace unchanged: `Galaxon\Core\Traits\Equatable`

- **Comparable namespace changed and converted to abstract trait**
  - Moved from `Galaxon\Core\Comparable` to `Galaxon\Core\Traits\Comparable`
  - No longer provides default `compare()` implementation
  - Now requires implementing class to provide `compare()` method
  - Uses Equatable trait via composition
  - Method `equals()` renamed to `equal()` for consistency

- **Floats::approxEqual() signature changed**
  - Now mimics Python's `math.isclose()` behavior
  - Parameters changed from `($f1, $f2, $epsilon, $relative)` to `($a, $b, $relTol, $absTol)`
  - Default relative tolerance: `1e-9` (new constant `DEFAULT_RELATIVE_TOLERANCE`)
  - Default absolute tolerance: `PHP_FLOAT_EPSILON` (new constant `DEFAULT_ABSOLUTE_TOLERANCE`)
  - Removed `$relative` parameter - now uses combined relative and absolute tolerance
  - IEEE-754 special value handling: `INF === INF`, `-INF === -INF`, `NAN` never equals anything
  - Removed `approxEqualAbsolute()` and `approxEqualRelative()` methods

- **Comparison method names standardized**
  - `equals()` → `equal()` across all traits
  - `isLessThan()` → `lessThan()`
  - `isGreaterThan()` → `greaterThan()`
  - `isLessThanOrEqual()` → `lessThanOrEqual()`
  - `isGreaterThanOrEqual()` → `greaterThanOrEqual()`

### Added

- **ApproxEquatable trait** (`Galaxon\Core\Traits\ApproxEquatable`)
  - Extends Equatable with tolerance-based comparison
  - Abstract `approxEqual()` method for floating-point equality within tolerances
  - For types without natural ordering (e.g., Complex numbers)

- **ApproxComparable trait** (`Galaxon\Core\Traits\ApproxComparable`)
  - Combines Comparable and ApproxEquatable for complete comparison suite
  - Provides `approxCompare()` method for approximate ordering comparison
  - For types with natural ordering that contain floating-point values (e.g., Rational numbers)

- **Floats constants**
  - `DEFAULT_RELATIVE_TOLERANCE` (1e-9) - Default relative tolerance for `approxEqual()`
  - `DEFAULT_ABSOLUTE_TOLERANCE` (PHP_FLOAT_EPSILON) - Default absolute tolerance
  - `MAX_EXACT_INT` (2⁵³) - Maximum integer exactly representable as float

- **Types::same()** - Check if two values have the same type using `get_debug_type()`

- **Comparable::checkSame()** - Public method to verify type compatibility, throws `TypeError` if types don't match

### Changed

- **Floats class reorganized**
  - Methods grouped by functionality: comparison, conversion, precision, rounding, random
  - Improved PHPDoc comments throughout
  - Better separation of concerns

- **Numbers::approxEqual()** - Updated to use new Floats::approxEqual() signature

- **Traits now use composition**
  - Comparable uses Equatable
  - ApproxEquatable uses Equatable
  - ApproxComparable uses both Comparable and ApproxEquatable
  - PHP automatically resolves diamond inheritance of Equatable

### Documentation

- **New comprehensive trait documentation**:
  - `docs/Traits/Traits.md` - Complete overview with hierarchy diagram and usage guide
  - `docs/Traits/Equatable.md` - Rewritten for trait (previously interface)
  - `docs/Traits/Comparable.md` - Updated for new namespace and behavior
  - `docs/Traits/ApproxEquatable.md` - New documentation for approximate equality
  - `docs/Traits/ApproxComparable.md` - New documentation for approximate comparison

- **Updated class documentation**:
  - `docs/Floats.md` - Completely reorganized to match new class structure
  - `docs/Numbers.md` - Updated for new comparison method signatures
  - `docs/Types.md` - Added `same()` documentation

- **README.md** - Added Traits section with links to all four traits and overview

### Tests

- **FloatsTest** - Comprehensive rewrite for new `approxEqual()` behavior
  - Tests for relative and absolute tolerance
  - Tests for IEEE-754 special values (INF, -INF, NAN)
  - Tests for combined tolerance behavior
  - Reduced from 478 to ~280 lines (test consolidation)

- **NumbersTest** - Updated for new `approxEqual()` signature
- **TypesTest** - Added tests for `same()`

---

## [0.3.0] - 2025-01-15

### Added

- **Arrays::quoteValues()** - Wrap string array values in quotes for formatting
  - Supports both single quotes (default) and double quotes
  - Useful for formatting lists in error messages or output
  - Throws `TypeError` if array contains non-string values
  - Preserves array keys

- **Floats::ulp()** - Calculate Unit in Last Place (ULP) for floating-point precision analysis
  - Returns the spacing between adjacent representable floats at a given magnitude
  - Useful for understanding floating-point precision limits and calculating error bounds
  - Moved from `NumberWithError` in Units package to provide general-purpose float utility

- **Floats::isExactInt()** - Check if a float represents an exact integer without rounding error
  - Validates integers are within IEEE-754 double's exact integer range (±2⁵³)
  - Returns `true` for whole numbers that can be exactly represented as floats
  - Moved from `NumberWithError::isExactFloat()` in Units package with improved naming

### Tests

- Added 19 comprehensive tests for `Arrays::quoteValues()`:
  - Both single and double quote modes
  - Empty arrays and strings
  - Special characters, whitespace, and unicode
  - Type validation (TypeError for non-string values)
  - Key preservation and immutability

- Added 18 comprehensive tests for Floats precision methods:
  - `ulp()` tests: standard values, zero handling, negative values, large/small magnitudes, non-finite values,
    relationship with `next()`
  - `isExactInt()` tests: whole numbers, fractional values, boundary cases (±2⁵³), non-finite values, comparison with
    `tryConvertToInt()`

### Documentation

- **Arrays.md** - Added documentation for `quoteValues()`
- **Floats.md** - Added documentation for `ulp()` and `isExactInt()`

---

## [0.2.0] - 2025-01-29

### Breaking Changes

- **Angle class removed** - Moved to `galaxon/units` package
  - Use `Galaxon\Units\Angle` instead of `Galaxon\Core\Angle`
  - All Angle functionality now available in the separate Units package

- **Floats::approxEqual() behavior changed**
  - Now uses relative comparison by default instead of absolute comparison
  - New 4th parameter `$relative` (defaults to `true`)
  - Relative comparison scales epsilon with magnitude, better for comparing values across different scales
  - To maintain old behavior, pass `false` for the `$relative` parameter

### Added

- **Floats** - New constants and methods for float operations
  - `TAU` constant - The mathematical constant τ (tau) = 2π, useful for angular calculations
  - `EPSILON` constant - Default epsilon value (1e-10) for approximate comparisons
  - `approxEqualAbsolute()` - Explicit absolute epsilon comparison
  - `approxEqualRelative()` - Explicit relative epsilon comparison (scales with magnitude)
  - `compare()` - Three-way comparison with approximate equality support

- **Numbers** - New comparison methods
  - `equal()` - Exact equality check for int|float values
  - `approxEqual()` - Approximate equality with relative/absolute mode selection

### Changed

- **Floats::compare()** - Now uses `Numbers::sign()` to guarantee exactly -1, 0, or 1 return values
- **Floats::approxEqual()** - Added 4th parameter `$relative` (defaults to `true`)
- **Numbers::approxEqual()** - Added 3rd parameter `$epsilon` and 4th parameter `$relative`

### Documentation

- **Enhanced PHPDoc comments** in `Equatable` and `Comparable` with detailed implementation guidelines
- **Comprehensive documentation updates**:
  - `docs/Floats.md` - Added TAU, wrap(), compare(), and all three approxEqual variants
  - `docs/Numbers.md` - Added equal() and updated approxEqual() documentation
  - `docs/Equatable.md` - Updated epsilon examples and best practices
  - `docs/Comparable.md` - Corrected implementation details and added TypeError documentation
  - Removed `docs/Angle.md` (moved to Units package)

### Tests

- Added comprehensive tests for new Floats methods (approxEqualAbsolute, approxEqualRelative, compare, wrap)
- Added comprehensive tests for Numbers methods (equal, approxEqual)
- All tests passing with 100% code coverage maintained

---

## [0.1.0] - 2025-01-16

### Added

- **Angle** - Class for working with angles in radians and degrees
  - `wrapRadians()`, `wrapDegrees()` - Normalize angles to standard ranges
  - `fromDegrees()`, `fromRadians()` - Factory methods
  - Conversion between radians and degrees

- **Floats** - Utility methods for floating-point operations
  - `approxEqual()` - Compare floats with epsilon tolerance
  - `sign()` - Get sign of a float (-1, 0, or 1)
  - Constants for common epsilon values

- **Integers** - Utility methods for integer operations
  - `sign()` - Get sign of an integer
  - `gcd()` - Greatest common divisor
  - `lcm()` - Least common multiple
  - `absExact()` - Absolute value with overflow detection
  - `mulExact()`, `addExact()` - Arithmetic with overflow detection

- **Numbers** - Utility methods for numeric operations
  - Common operations that work with both int and float

- **Arrays** - Utility methods for array operations

- **Stringify** - Utilities for converting values to strings
  - `value()` - Convert any PHP value to a readable string representation

- **Types** - Utility methods for type checking and manipulation
  - `isNumber()` - Check if value is int or float
  - `isUint()` - Check if value is unsigned integer
  - `getBasicType()` - Get canonical type name
  - `getUniqueString()` - Convert any value to unique string
  - `createError()` - Create TypeError with helpful message
  - `usesTrait()` - Check if class/object uses a trait
  - `getTraits()` - Get all traits used by class/interface/trait

- **Equatable** - Interface for value equality comparison
  - `equals(mixed $other): bool` - Check equality with another value

- **Comparable** - Trait providing comparison methods
  - `equals()`, `isLessThan()`, `isGreaterThan()`
  - `isLessThanOrEqual()`, `isGreaterThanOrEqual()`
  - Requires implementing class to provide `compare()` method

### Requirements

- PHP ^8.4

### Development

- PSR-12 coding standards
- PHPStan level 9 static analysis
- PHPUnit test coverage
- Comprehensive test suite with 100% code coverage
