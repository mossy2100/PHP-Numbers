<?php

declare(strict_types=1);

namespace OceanMoon\Core;

use DomainException;

/**
 * Console — emit ANSI/SGR escape codes to style console output, while tracking the current style so it can be
 * snapshotted and restored.
 *
 * Every method echoes its escape code immediately and returns $this, so calls can be chained. Call them as statements —
 * don't wrap a call in echo yourself, or PHP will try to stringify the returned object.
 *
 * ```php
 * $t = new Console();
 * $t->colors(Console::WHITE, Console::RED)->bold();
 * echo ' ALERT ';
 * $t->resetStyle();
 * ```
 *
 * State: colors()/resetColors() and the attribute methods keep the properties below in sync.
 * - getStyle() returns a detached snapshot;
 * - setStyle() re-applies one.
 *
 * Color numbers only name the console window's 16 palette slots; the actual RGB shown depends on the user's theme.
 */
class Console
{
    #region Constants

    /** The ESC control character (octal 33 / decimal 27). */
    private const string ESC = "\033";

    /** The BEL control character (0x07), used to ring the console bell. */
    private const string BEL = "\x07";

    /** The OSC 8 introducer, used to open and close a clickable hyperlink. */
    private const string OSC8 = ']8;;';

    /** The String Terminator (ST) that ends an OSC sequence, paired with ESC. */
    private const string ST = '\\';

    /*
     * Foreground color codes. Backgrounds reuse these: the +10 offset that turns 31->41 and 91->101 is applied only at
     * emit time, so both the arguments and the stored $background stay in this foreground-space.
     * Normal intensity colors are 30-37; bright colors are 90-97.
     */
    public const int BLACK = 30;
    public const int RED = 31;
    public const int GREEN = 32;
    public const int YELLOW = 33;
    public const int BLUE = 34;
    public const int MAGENTA = 35;
    public const int CYAN = 36;
    public const int SILVER = 37;
    public const int GRAY = 90; // a.k.a. "bright black"
    public const int BRIGHT_RED = 91;
    public const int BRIGHT_GREEN = 92;
    public const int BRIGHT_YELLOW = 93;
    public const int BRIGHT_BLUE = 94;
    public const int BRIGHT_MAGENTA = 95;
    public const int BRIGHT_CYAN = 96;
    public const int WHITE = 97; // a.k.a. "bright silver"

    /** Sentinel meaning "the console's default color". */
    public const int DEFAULT = 39;

    /** Added to a foreground color code to get its background counterpart (e.g. 31 -> 41, 91 -> 101). */
    private const int BACKGROUND_OFFSET = 10;

    /*
     * SGR attribute on/off codes, used internally by the attribute methods below. Not exposed publicly — callers
     * use those methods (e.g. bold(), italic()) rather than raw SGR numbers.
     */
    private const int BOLD_ON = 1;
    private const int DIM_ON = 2;
    private const int INTENSITY_OFF = 22; // Clears both bold and dim - there's no way to clear just one.
    private const int ITALIC_ON = 3;
    private const int ITALIC_OFF = 23;
    private const int UNDERLINE_ON = 4;
    private const int UNDERLINE_OFF = 24;
    private const int REVERSE_ON = 7;
    private const int REVERSE_OFF = 27;
    private const int STRIKETHROUGH_ON = 9;
    private const int STRIKETHROUGH_OFF = 29;
    private const int RESET_ALL = 0;

    /** Glyphs for message severity levels. */
    private const string GLYPH_SUCCESS = "\u{2713}";
    private const string GLYPH_ERROR = "\u{2717}";
    private const string GLYPH_WARN = "\u{26A0}";
    private const string GLYPH_INFO = "\u{2139}";

    /** PHP type => Console color. */
    public const array TYPE_COLOR = [
        'null'     => self::SILVER, // muted grey — "absence"
        'bool'     => self::BRIGHT_YELLOW,
        'int'      => self::BRIGHT_BLUE,
        'float'    => self::BRIGHT_RED,
        'string'   => self::BRIGHT_GREEN, // strings = green, the classic
        'array'    => self::BRIGHT_MAGENTA,
        'enum'     => self::YELLOW,
        'closure'  => self::GREEN,
        'object'   => self::BRIGHT_CYAN,
        'resource' => self::RED,
        'unknown'  => self::GRAY,
    ];

    #endregion

    #region Properties

    /* Tracked style state. Colors stored in foreground space. */
    private int $foreground     = self::DEFAULT;

    private int $background     = self::DEFAULT;

    private bool $bold          = false;

    private bool $dim           = false;

    private bool $italic        = false;

    private bool $underline     = false;

    private bool $strikethrough = false;

    private bool $reverse       = false;

    #endregion

    #region Color methods

    /**
     * Set just the foreground color. Pass any color constant.
     *
     * @param int $foreground The foreground color constant.
     * @return self Returns $this for chaining.
     */
    public function foreground(int $foreground): self
    {
        $this->foreground = $foreground;
        return $this->emit($foreground);
    }

    /**
     * Set just the background color. Pass any color constant.
     *
     * @param int $background The background color constant.
     * @return self Returns $this for chaining.
     */
    public function background(int $background): self
    {
        $this->background = $background;
        return $this->emit($background + self::BACKGROUND_OFFSET);
    }

    /**
     * Set foreground and background colors together. Pass any color constants.
     *
     * @param int $foreground The foreground color constant.
     * @param int $background The background color constant.
     * @return self Returns $this for chaining.
     */
    public function colors(int $foreground, int $background): self
    {
        $this->foreground($foreground);
        $this->background($background);

        // Return $this for chaining.
        return $this;
    }

    /**
     * Return both colors to the console defaults.
     *
     * @return self Returns $this for chaining.
     */
    public function resetColors(): self
    {
        return $this->colors(self::DEFAULT, self::DEFAULT);
    }

    #endregion

    #region Attribute methods

    /**
     * Turn bold text on or off.
     *
     * There's no SGR code to clear bold alone — only INTENSITY_OFF (22), which clears both bold and dim. So turning
     * bold off emits that, then re-emits dim if it was still active, to leave dim untouched from the caller's
     * perspective.
     *
     * @param bool $bold True (default) to turn bold on, false to turn it off.
     * @return self Returns $this for chaining.
     */
    public function bold(bool $bold = true): self
    {
        $this->bold = $bold;
        if ($bold) {
            return $this->emit(self::BOLD_ON);
        }
        $this->emit(self::INTENSITY_OFF);
        if ($this->dim) {
            $this->emit(self::DIM_ON);
        }
        return $this;
    }

    /**
     * Turn dim (faint) text on or off.
     *
     * There's no SGR code to clear dim alone — only INTENSITY_OFF (22), which clears both bold and dim. So turning
     * dim off emits that, then re-emits bold if it was still active, to leave bold untouched from the caller's
     * perspective.
     *
     * @param bool $dim True (default) to turn dim on, false to turn it off.
     * @return self Returns $this for chaining.
     */
    public function dim(bool $dim = true): self
    {
        $this->dim = $dim;
        if ($dim) {
            return $this->emit(self::DIM_ON);
        }
        $this->emit(self::INTENSITY_OFF);
        if ($this->bold) {
            $this->emit(self::BOLD_ON);
        }
        return $this;
    }

    /**
     * Turn italic text on or off.
     *
     * @param bool $italic True (default) to turn italic on, false to turn it off.
     * @return self Returns $this for chaining.
     */
    public function italic(bool $italic = true): self
    {
        $this->italic = $italic;
        return $this->emit($italic ? self::ITALIC_ON : self::ITALIC_OFF);
    }

    /**
     * Turn underlined text on or off.
     *
     * @param bool $underline True (default) to turn underline on, false to turn it off.
     * @return self Returns $this for chaining.
     */
    public function underline(bool $underline = true): self
    {
        $this->underline = $underline;
        return $this->emit($underline ? self::UNDERLINE_ON : self::UNDERLINE_OFF);
    }

    /**
     * Turn strikethrough text on or off.
     *
     * @param bool $strikethrough True (default) to turn strikethrough on, false to turn it off.
     * @return self Returns $this for chaining.
     */
    public function strikethrough(bool $strikethrough = true): self
    {
        $this->strikethrough = $strikethrough;
        return $this->emit($strikethrough ? self::STRIKETHROUGH_ON : self::STRIKETHROUGH_OFF);
    }

    /**
     * Turn reverse video (swap foreground and background colors) on or off.
     *
     * @param bool $reverse True (default) to turn reverse video on, false to turn it off.
     * @return self Returns $this for chaining.
     */
    public function reverse(bool $reverse = true): self
    {
        $this->reverse = $reverse;
        return $this->emit($reverse ? self::REVERSE_ON : self::REVERSE_OFF);
    }

    #endregion

    #region Style methods

    /**
     * Return a detached snapshot of the current style. Colors are in foreground-space, so the array round-trips
     * cleanly through setStyle().
     *
     * @return array{
     *      foreground:     int,
     *      background:     int,
     *      bold:           bool,
     *      dim:            bool,
     *      italic:         bool,
     *      underline:      bool,
     *      strikethrough:  bool,
     *      reverse:        bool
     * }
     */
    public function getStyle(): array
    {
        return [
            'foreground'    => $this->foreground,
            'background'    => $this->background,
            'bold'          => $this->bold,
            'dim'           => $this->dim,
            'italic'        => $this->italic,
            'underline'     => $this->underline,
            'strikethrough' => $this->strikethrough,
            'reverse'       => $this->reverse,
        ];
    }

    /**
     * Apply a style array (as produced by getStyle()). Only the keys present are applied, so partial arrays work
     * too; each present key is realised exactly, including turning attributes off.
     *
     * @param array{
     *      foreground?:    int,
     *      background?:    int,
     *      bold?:          bool,
     *      dim?:           bool,
     *      italic?:        bool,
     *      underline?:     bool,
     *      strikethrough?: bool,
     *      reverse?:       bool
     * } $style
     * The style to apply.
     * @return self Returns $this for chaining.
     */
    public function setStyle(array $style): self
    {
        if (isset($style['foreground'])) {
            $this->foreground($style['foreground']);
        }
        if (isset($style['background'])) {
            $this->background($style['background']);
        }
        if (isset($style['bold'])) {
            $this->bold($style['bold']);
        }
        if (isset($style['dim'])) {
            $this->dim($style['dim']);
        }
        if (isset($style['italic'])) {
            $this->italic($style['italic']);
        }
        if (isset($style['underline'])) {
            $this->underline($style['underline']);
        }
        if (isset($style['strikethrough'])) {
            $this->strikethrough($style['strikethrough']);
        }
        if (isset($style['reverse'])) {
            $this->reverse($style['reverse']);
        }

        return $this;
    }

    /**
     * The blunt instrument: reset every style property to the default (SGR 0).
     *
     * @return self Returns $this for chaining.
     */
    public function resetStyle(): self
    {
        $this->foreground = self::DEFAULT;
        $this->background = self::DEFAULT;
        $this->bold = false;
        $this->dim = false;
        $this->italic = false;
        $this->underline = false;
        $this->strikethrough = false;
        $this->reverse = false;

        return $this->emit(self::RESET_ALL);
    }

    #endregion

    #region Output methods

    /**
     * Prints a value but won't trigger warnings or errors like regular echo or print.
     *
     * The value is converted to a string using Stringify::toString(), which handles arrays, NAN, non-Stringable
     * objects, etc. without warnings or errors.
     *
     * The method name mimics Java, Scala, Swift, Rust, Go, Julia, etc., and aligns with PHP's print() construct.
     *
     * @param mixed $value The value to print.
     * @return self Returns $this for chaining.
     * @see Stringify::toString()
     */
    public function print(mixed $value = ''): self
    {
        // Stringify the value.
        $str = Stringify::toString($value);

        // Apply fix for line backgrounds if necessary.
        if ($this->background !== self::DEFAULT) {
            // Reset the background at each newline, then re-apply it. This prevents the background from bleeding into
            // the next line when the user prints a string with newlines in it.
            $resetBg = $this->sgr(self::DEFAULT + self::BACKGROUND_OFFSET);
            $setBg = $this->sgr($this->background + self::BACKGROUND_OFFSET);
            echo str_replace("\n", "$resetBg\n$setBg", $str);
        } else {
            echo $str;
        }

        // Return $this for chaining.
        return $this;
    }

    /**
     * Same as print() but echoes an additional newline.
     *
     * The method name mimics Java, Scala, Rust, Go, Julia, etc.
     *
     * @param mixed $value The value to print.
     * @return self Returns $this for chaining.
     * @see print()
     */
    public function println(mixed $value = ''): self
    {
        return $this->print(Stringify::toString($value) . "\n");
    }

    /**
     * Dumps a variable using stringify and a type-specific color.
     *
     * Prints a stringified value, along with its type. A different color is used for each type.
     * @see TYPE_COLOR
     *
     * This is an alternative to var_dump(), var_export(), and print_r(), with some advantages:
     * 1. Concise, readable format.
     * 2. Value's type is apparent.
     * 3. Handles special types like enum and closure.
     * 4. Handles recursion in arrays and objects gracefully.
     * 5. Doesn't trigger notices, warnings, or errors for any type.
     *
     * @param mixed $value The value to dump.
     * @return self Returns $this for chaining.
     */
    public function dump(mixed $value): self
    {
        $style = $this->getStyle();
        $type = Types::getBasicType($value);
        return $this->colors(self::TYPE_COLOR[$type] ?? self::GRAY, self::DEFAULT)
                    ->println("$type: " . Stringify::stringify($value, true))
                    ->setStyle($style);
    }

    /**
     * Print text to the console, optionally changing the foreground/background color and bold state for the
     * message only.
     *
     * The text style will be restored to the initial state after the message is printed.
     *
     * @param string $text The message text.
     * @param ?int $foreground The message's foreground color. Null (default) leaves it unchanged.
     * @param ?int $background The message's background color. Null (default) leaves it unchanged.
     * @param ?bool $bold The message's bold state. Null (default) leaves it unchanged.
     * @return self Returns $this for chaining.
     */
    public function message(string $text, ?int $foreground = null, ?int $background = null, ?bool $bold = null): self
    {
        // Remember the current style.
        $style = $this->getStyle();

        // Set the message style.
        $this->bold($bold ?? $this->bold)->colors($foreground ?? $this->foreground, $background ?? $this->background);

        // Emit message.
        $this->println($text);

        // Restore the original style.
        return $this->setStyle($style);
    }

    /**
     * Print white text on a green background, with a checkmark glyph prefix.
     *
     * This is the least severe message level, and should be used for successful operations or confirmations.
     *
     * @param string $text The text to print.
     * @return self Returns $this for chaining.
     */
    public function success(string $text): self
    {
        return $this->message(' ' . self::GLYPH_SUCCESS . " $text ", self::WHITE, self::GREEN, true);
    }

    /**
     * Print white text on a red background, with a cross glyph prefix.
     *
     * This is the most severe error level, and should be used for fatal errors that prevent the program from
     * continuing. For non-fatal errors, consider using warn() instead.
     *
     * @param string $text The text to print.
     * @return self Returns $this for chaining.
     */
    public function error(string $text): self
    {
        return $this->message(' ' . self::GLYPH_ERROR . " $text ", self::WHITE, self::RED, true);
    }

    /**
     * Print black text on a yellow background, with a warning glyph prefix.
     *
     * This is a medium-severity message level, and should be used for warnings or non-fatal errors that the user
     * should be aware of. For fatal errors, consider using error() instead.
     *
     * @param string $text The text to print.
     * @return self Returns $this for chaining.
     */
    public function warn(string $text): self
    {
        return $this->message(' ' . self::GLYPH_WARN . " $text ", self::BLACK, self::BRIGHT_YELLOW, true);
    }

    /**
     * Print white text on a blue background, with an info glyph prefix.
     *
     * This is a low-severity message level, and should be used for informational messages that the user may find
     * useful.
     *
     * @param string $text The text to print.
     * @return self Returns $this for chaining.
     */
    public function info(string $text): self
    {
        return $this->message(' ' . self::GLYPH_INFO . " $text ", self::WHITE, self::BLUE, true);
    }

    /**
     * Emit a clickable hyperlink via the OSC 8 sequence, styled light blue and underlined on the default
     * background. Falls back to the URL itself when no label is given. Supported in iTerm2, GNOME Console,
     * Windows Console, etc.; unsupported terminals show the label as plain text.
     *
     * @param string $url The URL to link to.
     * @param string $text The clickable label. Defaults to the URL itself.
     * @return self Returns $this for chaining.
     */
    public function link(string $url, string $text = ''): self
    {
        $label = $text !== '' ? $text : $url;
        $style = $this->getStyle();

        $this->colors(self::BRIGHT_BLUE, self::DEFAULT)->underline();

        echo self::ESC . self::OSC8 . $url . self::ESC . self::ST . $label . self::ESC . self::OSC8 . self::ESC
            . self::ST;

        return $this->setStyle($style);
    }

    /**
     * Ring the console bell (BEL, 0x07). Note: NOT "\a" in PHP.
     *
     * @return self Returns $this for chaining.
     */
    public function bell(): self
    {
        echo self::BEL;
        return $this;
    }

    /**
     * Print a horizontal rule: a line of repeated characters or substrings followed by a newline.
     *
     * Supports multibyte characters.
     *
     * @param string $ch The substring to repeat.
     * @param int $length The total length.
     * @throws DomainException If $ch is an empty string.
     */
    public function hr(string $ch = '-', int $length = 80): void
    {
        // Guard against zero-length substring.
        $chLen = mb_strlen($ch);
        if ($chLen === 0) {
            throw new DomainException('Horizontal rule character must not be empty.');
        }

        $n = (int) ceil($length / $chLen);
        $this->println(mb_substr(str_repeat($ch, $n), 0, $length));
    }

    #endregion

    #region Helper methods

    /**
     * Convert one or more SGR parameters to a single escape sequence.
     *
     * @param int ...$params The SGR parameter codes to emit, joined with ';'.
     * @return string The sequence.
     */
    private function sgr(int ...$params): string
    {
        return self::ESC . '[' . implode(';', $params) . 'm';
    }

    /**
     * Emit one or more SGR parameters as a single escape sequence (e.g. emit(31, 40) -> ESC[31;40m).
     *
     * @param int ...$params The SGR parameter codes to emit, joined with ';'.
     * @return self Returns $this for chaining.
     */
    private function emit(int ...$params): self
    {
        echo $this->sgr(...$params);
        return $this;
    }

    #endregion
}
