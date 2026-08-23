# Console

ANSI/SGR-styled console output, with method chaining and style state tracking.

---

## Overview

`Console` emits raw ANSI/SGR escape codes to color and style terminal output: 16-color foreground/background, bold, dim,
italic, underline, strikethrough, and reverse video. It also tracks the currently-applied style so it can be snapshotted
and restored later, and provides higher-level helpers for common console output tasks (type-colored variable dumps,
severity-colored messages, hyperlinks, horizontal rules).

Every method echoes its escape code immediately and returns `$this`, so calls chain naturally:

```php
$console = new Console();
$console->colors(Console::WHITE, Console::RED)->bold();
echo ' ALERT ';
$console->resetStyle();
```

Call these as statements — don't wrap a call in `echo` yourself, since PHP would try to stringify the returned `Console`
object rather than print what it already echoed.

Color numbers only name the terminal's 16 palette slots (`BLACK`, `RED`, ... `WHITE`). The actual RGB shown depends on
the user's terminal theme — this is standard ANSI behavior, not something `Console` controls.

### Key Features

- Method chaining: every method returns `$this`.
- Style state tracking: `getStyle()`/`setStyle()` snapshot and restore the current foreground, background, and all
  attribute toggles.
- `dump()` — a `var_dump()`/`print_r()` alternative that's concise, type-colored, and never triggers a notice or warning
  for any value type.
- Severity-colored message helpers: `success()`, `error()`, `warn()`, `info()`.
- Clickable OSC 8 hyperlinks via `link()`, with plain-text fallback in unsupported terminals.

---

## Constants

### Color constants

Each names one of the terminal's 16 standard palette slots (the "normal intensity" 30–37 range, and the
"bright"/high-intensity 90–97 range), plus `DEFAULT` for the terminal's own default color. Pass any of these to
`foreground()`/`background()`/`colors()`.

| Constant         | Value | Notes                                            |
| ---------------- | ----- | ------------------------------------------------ |
| `BLACK`          | `30`  |                                                  |
| `RED`            | `31`  |                                                  |
| `GREEN`          | `32`  |                                                  |
| `YELLOW`         | `33`  |                                                  |
| `BLUE`           | `34`  |                                                  |
| `MAGENTA`        | `35`  |                                                  |
| `CYAN`           | `36`  |                                                  |
| `SILVER`         | `37`  |                                                  |
| `GRAY`           | `90`  | a.k.a. "bright black".                           |
| `BRIGHT_RED`     | `91`  |                                                  |
| `BRIGHT_GREEN`   | `92`  |                                                  |
| `BRIGHT_YELLOW`  | `93`  |                                                  |
| `BRIGHT_BLUE`    | `94`  |                                                  |
| `BRIGHT_MAGENTA` | `95`  |                                                  |
| `BRIGHT_CYAN`    | `96`  |                                                  |
| `WHITE`          | `97`  | a.k.a. "bright silver".                          |
| `DEFAULT`        | `39`  | Sentinel for "the terminal's own default color". |

All constants are foreground-space values. `background()` applies the `+10` offset (e.g. `31` → `41`) internally at
emit time — you never need to add it yourself.

### TYPE_COLOR

```php
public const array TYPE_COLOR = [...];
```

Maps each of `Types::getBasicType()`'s type names to a default `Console` color constant. Used internally by `dump()` to
color a value by its type; look it up directly if you want the same color scheme elsewhere.

| Type       | Color            | Notes                         |
| ---------- | ---------------- | ------------------------------ |
| `null`     | `SILVER`         | Muted grey, for "absence".    |
| `bool`     | `BRIGHT_YELLOW`  |                               |
| `int`      | `BRIGHT_BLUE`    |                               |
| `float`    | `BRIGHT_RED`     |                               |
| `string`   | `BRIGHT_GREEN`   | Strings = green, the classic. |
| `array`    | `BRIGHT_MAGENTA` |                               |
| `enum`     | `YELLOW`         |                               |
| `closure`  | `GREEN`          |                               |
| `object`   | `BRIGHT_CYAN`    |                               |
| `resource` | `RED`            |                               |
| `unknown`  | `GRAY`           |                               |

---

## Color Methods

### foreground()

```php
public function foreground(int $foreground): self
```

Set just the foreground color.

**Parameters:**

- `$foreground` (`int`) - A `Console` color constant.

**Returns:**

- `self` - Returns `$this` for chaining.

### background()

```php
public function background(int $background): self
```

Set just the background color.

**Parameters:**

- `$background` (`int`) - A `Console` color constant.

**Returns:**

- `self` - Returns `$this` for chaining.

### colors()

```php
public function colors(int $foreground, int $background): self
```

Set the foreground and background colors together.

**Parameters:**

- `$foreground` (`int`) - A `Console` color constant.
- `$background` (`int`) - A `Console` color constant.

**Returns:**

- `self` - Returns `$this` for chaining.

**Examples:**

```php
new Console()->foreground(Console::RED);
new Console()->colors(Console::WHITE, Console::RED);
```

### resetColors()

```php
public function resetColors(): self
```

Return both the foreground and background colors to the terminal's defaults. Equivalent to
`colors(Console::DEFAULT, Console::DEFAULT)`.

**Returns:**

- `self` - Returns `$this` for chaining.

---

## Attribute Methods

Each attribute method takes a `bool` parameter that defaults to `true`, so calling it with no arguments turns the
attribute on; pass `false` to turn it off. All return `$this` for chaining.

### bold()

```php
public function bold(bool $bold = true): self
```

Turn bold text on or off.

There's no SGR code to clear bold alone, only one that clears bold and dim together, so `bold(false)` re-applies dim
afterwards if it was still active — from the caller's perspective, only bold is affected.

**Parameters:**

- `$bold` (`bool`) - `true` (default) to turn bold on, `false` to turn it off.

**Returns:**

- `self` - Returns `$this` for chaining.

### dim()

```php
public function dim(bool $dim = true): self
```

Turn dim (faint) text on or off. `dim(false)` is the mirror image of `bold(false)`: it re-applies bold afterwards if it
was still active, so only dim is affected from the caller's perspective.

**Parameters:**

- `$dim` (`bool`) - `true` (default) to turn dim on, `false` to turn it off.

**Returns:**

- `self` - Returns `$this` for chaining.

### italic()

```php
public function italic(bool $italic = true): self
```

Turn italic text on or off.

**Parameters:**

- `$italic` (`bool`) - `true` (default) to turn italic on, `false` to turn it off.

**Returns:**

- `self` - Returns `$this` for chaining.

### underline()

```php
public function underline(bool $underline = true): self
```

Turn underlined text on or off.

**Parameters:**

- `$underline` (`bool`) - `true` (default) to turn underline on, `false` to turn it off.

**Returns:**

- `self` - Returns `$this` for chaining.

### strikethrough()

```php
public function strikethrough(bool $strikethrough = true): self
```

Turn strikethrough text on or off.

**Parameters:**

- `$strikethrough` (`bool`) - `true` (default) to turn strikethrough on, `false` to turn it off.

**Returns:**

- `self` - Returns `$this` for chaining.

### reverse()

```php
public function reverse(bool $reverse = true): self
```

Turn reverse video (swap foreground and background colors) on or off.

**Parameters:**

- `$reverse` (`bool`) - `true` (default) to turn reverse video on, `false` to turn it off.

**Returns:**

- `self` - Returns `$this` for chaining.

---

## Style Methods

### getStyle()

```php
public function getStyle(): array
```

Get a detached snapshot of the current style: foreground, background, and every attribute toggle.

**Returns:**

- `array` - Keys `foreground`, `background` (both `int`), and `bold`, `dim`, `italic`, `underline`, `strikethrough`,
  `reverse` (all `bool`). Colors are in foreground-space, so the array round-trips cleanly through `setStyle()`.

### setStyle()

```php
public function setStyle(array $style): self
```

Apply a style array, as produced by `getStyle()`. Only the keys present are applied — a partial array (e.g. just
`['bold' => true]`) works too — and each present key is realised exactly, including turning an attribute off if its
value is `false`.

**Parameters:**

- `$style` (`array`) - Any subset of the keys `getStyle()` returns.

**Returns:**

- `self` - Returns `$this` for chaining.

**Examples:**

```php
$console = new Console();
$saved = $console->getStyle();

$console->foreground(Console::RED)->bold();
$console->println('important');

$console->setStyle($saved); // Restores the exact prior style.
```

### resetStyle()

```php
public function resetStyle(): self
```

The blunt instrument: reset every style property to the terminal default in one call (SGR `0`), rather than restoring a
specific prior snapshot.

**Returns:**

- `self` - Returns `$this` for chaining.

---

## Output Methods

### print()

```php
public function print(mixed $value = ''): self
```

Print a value without triggering warnings or errors, regardless of type. Converts `$value` via `Stringify::toString()`,
which tries a plain `(string)` cast first, and falls back to `Stringify::stringify()`'s pretty-printed format if the
cast would itself trigger a notice, warning, or error — e.g. for arrays, `NAN`, or a non-`Stringable` object. Ordinary
strings, numbers, and `Stringable` objects print exactly as a normal `(string)` cast would.

If a background color is currently set and `$value` contains newlines, the background is reset and re-applied around
each line break, so it doesn't visually bleed onto the next line.

**Parameters:**

- `$value` (`mixed`) - The value to print. Defaults to `''`.

**Returns:**

- `self` - Returns `$this` for chaining.

### println()

```php
public function println(mixed $value = ''): self
```

Same as `print()`, but appends a newline.

### dump()

```php
public function dump(mixed $value): self
```

Print a value's type and stringified form, colored by type (see [`TYPE_COLOR`](#type_color)). An alternative to
`var_dump()`/`var_export()`/`print_r()`:

- Concise, readable single-line-per-call output.
- The value's type is always visible.
- Handles `enum` and `closure` values explicitly, rather than falling back to a generic object dump.
- Handles circular references in arrays/objects gracefully.
- Never triggers a notice, warning, or error, for any value type.

The console's style is restored to what it was before the call, once the dump is printed.

**Parameters:**

- `$value` (`mixed`) - The value to dump.

**Returns:**

- `self` - Returns `$this` for chaining.

**Examples:**

```php
new Console()->dump(3.14); // 'float: 3.14', in TYPE_COLOR['float']
new Console()->dump(['a' => 1]); // "array: ['a' => 1]", in TYPE_COLOR['array']
```

### message()

```php
public function message(string $text, ?int $foreground = null, ?int $background = null, ?bool $bold = null): self
```

Print a message, optionally in a one-off color and/or bold state, with the prior style restored afterwards. The
severity helpers below (`success()`, `error()`, `warn()`, `info()`) are all thin wrappers around this.

**Parameters:**

- `$text` (`string`) - The message text.
- `$foreground` (`?int`) - A `Console` color constant for the message only. `null` (default) leaves the current
  foreground unchanged.
- `$background` (`?int`) - A `Console` color constant for the message only. `null` (default) leaves the current
  background unchanged.
- `$bold` (`?bool`) - The bold state for the message only. `null` (default) leaves the current bold state unchanged.

**Returns:**

- `self` - Returns `$this` for chaining.

### success() / error() / warn() / info()

```php
public function success(string $text): self
public function error(string $text): self
public function warn(string $text): self
public function info(string $text): self
```

Print a glyph-prefixed, padded, bold message at a fixed severity level and color pairing, restoring the prior style
afterwards.

| Method      | Glyph | Colors                 | Severity                                                             |
| ----------- | ----- | ---------------------- | -------------------------------------------------------------------- |
| `success()` | ✓     | White on green         | Least severe — successful operations, confirmations.                 |
| `info()`    | ℹ     | White on blue          | Low — informational messages the user may find useful.               |
| `warn()`    | ⚠     | Black on bright yellow | Medium — non-fatal issues the user should be aware of.               |
| `error()`   | ✗     | White on red           | Most severe — fatal errors that prevent the program from continuing. |

**Parameters:**

- `$text` (`string`) - The message text.

**Returns:**

- `self` - Returns `$this` for chaining.

**Examples:**

```php
new Console()->success('Build finished.');
new Console()->warn('Deprecated option used.');
new Console()->error('Connection refused.');
```

### link()

```php
public function link(string $url, string $text = ''): self
```

Print a clickable hyperlink via the OSC 8 escape sequence, styled bright blue and underlined. Falls back to displaying
the label as plain, unstyled text in terminals that don't support OSC 8 (the link target itself is simply not clickable
there — nothing breaks).

**Parameters:**

- `$url` (`string`) - The URL to link to.
- `$text` (`string`) - The clickable label. Defaults to `$url` itself.

**Returns:**

- `self` - Returns `$this` for chaining.

**Examples:**

```php
new Console()->link('https://php.net');
new Console()->link('https://php.net', 'PHP manual');
```

### bell()

```php
public function bell(): self
```

Ring the terminal bell, by emitting the BEL control character (`0x07`). Note this is `"\x07"` in PHP, not `"\a"` — PHP
doesn't support the `\a` escape sequence. It can be a useful attention-grabber indicating the end of a long-running process.

**Returns:**

- `self` - Returns `$this` for chaining.

### hr()

```php
public function hr(string $ch = '-', int $length = 80): void
```

Print a horizontal rule: `$ch` repeated to fill `$length` characters, followed by a newline. Supports multibyte
characters in `$ch`.

**Parameters:**

- `$ch` (`string`) - The substring to repeat. Defaults to `'-'`.
- `$length` (`int`) - The total length of the rule, in characters. Defaults to `80`.

**Throws:**

- `DomainException` - If `$ch` is an empty string.

**Examples:**

```php
new Console()->hr(); // '--------------------------------------------------------------------------------'
new Console()->hr('=', 20); // '===================='
```

---

## See Also

- **[Stringify](Stringify.md)** - `print()`/`println()`/`dump()` use `Stringify::toString()`/`stringify()` to convert
  values without warnings or errors.
- **[Types](Types.md)** - `dump()` uses `Types::getBasicType()` to pick a `TYPE_COLOR` entry.
