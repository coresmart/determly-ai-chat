<?php

declare(strict_types=1);

namespace Determly\Chat\Laravel;

use Determly\Chat\InvalidChat;

/**
 * Persona 4.39.0 appearance: color scheme, brand, and the token tree.
 * An empty theme leaves Persona's own colors.
 */
final class Theme
{
    private const SCHEMES = ['light', 'dark', 'auto'];

    private const RADII = ['none', 'sm', 'md', 'lg'];

    private const DENSITIES = ['compact', 'comfortable', 'spacious'];

    private const ELEVATIONS = ['flat', 'subtle', 'raised'];

    private const BRAND_FIELDS = ['accent', 'neutral', 'font', 'radius', 'density', 'elevation'];

    private const TOKEN_ROOTS = ['palette', 'semantic', 'components'];

    private const COLOR_NAMES = [
        'primary',
        'secondary',
        'accent',
        'surface',
        'background',
        'container',
        'text',
        'textMuted',
        'textInverse',
        'border',
        'divider',
    ];

    /**
     * @param  array<string, string>  $brand
     * @param  array<string, mixed>  $tokens
     * @param  array<string, mixed>  $darkTokens
     */
    private function __construct(
        private readonly string $colorScheme,
        private readonly array $brand,
        private readonly array $tokens,
        private readonly array $darkTokens,
    ) {}

    public static function make(): self
    {
        return new self('light', [], [], []);
    }

    public static function resolve(mixed $value): self
    {
        if ($value instanceof self) {
            return $value;
        }

        return self::fromConfig($value);
    }

    public static function fromConfig(mixed $value): self
    {
        if ($value === null || $value === []) {
            return self::make();
        }
        if (! is_array($value)) {
            throw new InvalidChat('Chat appearance must be an array or a Theme.');
        }

        $known = ['color_scheme', 'colorScheme', 'brand', 'theme', 'dark_theme', 'darkTheme'];
        foreach ($value as $key => $unused) {
            if (! is_string($key) || ! in_array($key, $known, true)) {
                throw new InvalidChat('Chat appearance ['.$key.'] is not a theme field.');
            }
        }

        $theme = self::make()->colorScheme(self::stringOr($value['color_scheme'] ?? $value['colorScheme'] ?? 'light', 'Color scheme'));
        $brand = $value['brand'] ?? [];
        if ($brand !== null && $brand !== []) {
            if (! is_array($brand) || array_is_list($brand)) {
                throw new InvalidChat('Chat brand must be an object.');
            }
            foreach ($brand as $field => $item) {
                if (! is_string($field) || ! in_array($field, self::BRAND_FIELDS, true)) {
                    throw new InvalidChat('Brand ['.$field.'] is not a theme field. Use accent, neutral, font, radius, density, or elevation.');
                }
                if ($item === null || $item === '') {
                    continue;
                }
                if (! is_string($item)) {
                    throw new InvalidChat('Brand '.$field.' must be a string.');
                }
                $theme = $theme->{$field}($item);
            }
        }

        $tokens = $value['theme'] ?? [];
        if ($tokens !== null && $tokens !== []) {
            $theme = $theme->tokens(self::tokenTree($tokens, 'Theme'));
        }
        $dark = $value['dark_theme'] ?? $value['darkTheme'] ?? [];
        if ($dark !== null && $dark !== []) {
            $theme = $theme->darkTokens(self::tokenTree($dark, 'Dark theme'));
        }

        return $theme;
    }

    public function colorScheme(string $scheme): self
    {
        $scheme = strtolower(trim($scheme));
        if (! in_array($scheme, self::SCHEMES, true)) {
            throw new InvalidChat('Chat color scheme must be light, dark, or auto.');
        }

        return new self($scheme, $this->brand, $this->tokens, $this->darkTokens);
    }

    public function accent(string $color): self
    {
        return $this->withBrand('accent', self::opaqueColor($color, 'Brand accent'));
    }

    public function neutral(string $color): self
    {
        return $this->withBrand('neutral', self::opaqueColor($color, 'Brand neutral'));
    }

    public function font(string $family): self
    {
        $family = trim($family);
        if ($family === '' || strlen($family) > 200 || preg_match('/^[\p{L}\p{N} ,."\'\-]+$/u', $family) !== 1) {
            throw new InvalidChat('Brand font must be a CSS font-family name. Load the font on the page.');
        }

        return $this->withBrand('font', $family);
    }

    public function radius(string $radius): self
    {
        return $this->withBrand('radius', self::choice($radius, self::RADII, 'Brand radius', 'none, sm, md, or lg'));
    }

    public function density(string $density): self
    {
        return $this->withBrand('density', self::choice($density, self::DENSITIES, 'Brand density', 'compact, comfortable, or spacious'));
    }

    public function elevation(string $elevation): self
    {
        return $this->withBrand('elevation', self::choice($elevation, self::ELEVATIONS, 'Brand elevation', 'flat, subtle, or raised'));
    }

    /**
     * Semantic color overrides. These win over the brand.
     *
     * @param  array<string, string>  $colors
     */
    public function colors(array $colors): self
    {
        if ($colors === []) {
            return $this;
        }
        if (array_is_list($colors)) {
            throw new InvalidChat('Theme colors must be an object.');
        }
        $next = $this->tokens;
        $semantic = is_array($next['semantic'] ?? null) ? $next['semantic'] : [];
        $existing = is_array($semantic['colors'] ?? null) ? $semantic['colors'] : [];
        foreach ($colors as $name => $color) {
            if (! is_string($name) || ! in_array($name, self::COLOR_NAMES, true)) {
                throw new InvalidChat('Theme color ['.$name.'] is not a semantic color.');
            }
            if (! is_string($color)) {
                throw new InvalidChat('Theme color '.$name.' must be a string.');
            }
            $existing[$name] = self::colorValue($color, 'Theme color '.$name);
        }
        $semantic['colors'] = $existing;
        $next['semantic'] = $semantic;

        return new self($this->colorScheme, $this->brand, $next, $this->darkTokens);
    }

    /**
     * Deep partial of palette, semantic, and components.
     *
     * @param  array<string, mixed>  $tokens
     */
    public function tokens(array $tokens): self
    {
        return new self($this->colorScheme, $this->brand, self::merge($this->tokens, self::tokenTree($tokens, 'Theme')), $this->darkTokens);
    }

    /**
     * @param  array<string, mixed>  $tokens
     */
    public function darkTokens(array $tokens): self
    {
        return new self($this->colorScheme, $this->brand, $this->tokens, self::merge($this->darkTokens, self::tokenTree($tokens, 'Dark theme')));
    }

    /**
     * Keys Persona reads on the widget config: colorScheme, brand, theme, darkTheme.
     *
     * @return array<string, mixed>
     */
    public function persona(): array
    {
        $config = [];
        if ($this->colorScheme !== 'light') {
            $config['colorScheme'] = $this->colorScheme;
        }
        if ($this->brand !== []) {
            $config['brand'] = $this->brand;
        }
        if ($this->tokens !== []) {
            $config['theme'] = $this->tokens;
        }
        if ($this->darkTokens !== []) {
            $config['darkTheme'] = $this->darkTokens;
        }

        return $config;
    }

    /**
     * @param  array<string, string>  $brand
     */
    private function withBrand(string $field, string $value): self
    {
        $brand = $this->brand;
        $brand[$field] = $value;

        return new self($this->colorScheme, $brand, $this->tokens, $this->darkTokens);
    }

    private static function stringOr(mixed $value, string $label): string
    {
        if (! is_string($value)) {
            throw new InvalidChat($label.' must be a string.');
        }

        return $value;
    }

    /**
     * @param  list<string>  $allowed
     */
    private static function choice(string $value, array $allowed, string $label, string $expected): string
    {
        $value = strtolower(trim($value));
        if (! in_array($value, $allowed, true)) {
            throw new InvalidChat($label.' must be '.$expected.'.');
        }

        return $value;
    }

    private static function opaqueColor(string $color, string $label): string
    {
        $color = trim($color);
        if (preg_match('/^#(?:[0-9a-fA-F]{3}|[0-9a-fA-F]{6})$/', $color) === 1) {
            return $color;
        }
        if (preg_match('/^rgb\(\s*(?:[01]?\d?\d|2[0-4]\d|25[0-5])\s*,\s*(?:[01]?\d?\d|2[0-4]\d|25[0-5])\s*,\s*(?:[01]?\d?\d|2[0-4]\d|25[0-5])\s*\)$/', $color) === 1) {
            return $color;
        }
        if (preg_match('/^rgba\(\s*(?:[01]?\d?\d|2[0-4]\d|25[0-5])\s*,\s*(?:[01]?\d?\d|2[0-4]\d|25[0-5])\s*,\s*(?:[01]?\d?\d|2[0-4]\d|25[0-5])\s*,\s*(?:1|1\.0+)\s*\)$/', $color) === 1) {
            return $color;
        }

        throw new InvalidChat($label.' must be an opaque hex, rgb(), or rgba() color.');
    }

    private static function colorValue(string $color, string $label): string
    {
        $color = trim($color);
        if (preg_match('/^(?:palette|semantic)(?:\.[A-Za-z0-9_-]+)+$/', $color) === 1) {
            return $color;
        }
        if (preg_match('/^#(?:[0-9a-fA-F]{3}|[0-9a-fA-F]{6}|[0-9a-fA-F]{8})$/', $color) === 1) {
            return $color;
        }
        if (preg_match('/^rgb\(\s*(?:[01]?\d?\d|2[0-4]\d|25[0-5])\s*,\s*(?:[01]?\d?\d|2[0-4]\d|25[0-5])\s*,\s*(?:[01]?\d?\d|2[0-4]\d|25[0-5])\s*\)$/', $color) === 1) {
            return $color;
        }
        if (preg_match('/^rgba\(\s*(?:[01]?\d?\d|2[0-4]\d|25[0-5])\s*,\s*(?:[01]?\d?\d|2[0-4]\d|25[0-5])\s*,\s*(?:[01]?\d?\d|2[0-4]\d|25[0-5])\s*,\s*(?:0|1|0?\.\d+|1\.0+)\s*\)$/', $color) === 1) {
            return $color;
        }

        throw new InvalidChat($label.' must be a color or a palette or semantic token.');
    }

    /**
     * @return array<string, mixed>
     */
    private static function tokenTree(mixed $value, string $label): array
    {
        if (! is_array($value) || array_is_list($value)) {
            throw new InvalidChat($label.' must be an object with palette, semantic, or components.');
        }
        foreach ($value as $key => $unused) {
            if (! is_string($key) || ! in_array($key, self::TOKEN_ROOTS, true)) {
                throw new InvalidChat($label.' only accepts palette, semantic, and components.');
            }
        }
        self::assertTree($value, $label);

        return $value;
    }

    /**
     * @param  array<mixed, mixed>  $node
     */
    private static function assertTree(array $node, string $path): void
    {
        if (array_is_list($node)) {
            throw new InvalidChat($path.' must be an object.');
        }
        foreach ($node as $key => $value) {
            $name = is_int($key) ? (string) $key : $key;
            if (! is_string($name) || preg_match('/^[A-Za-z0-9_-]+$/', $name) !== 1) {
                throw new InvalidChat($path.' has an invalid token name.');
            }
            $child = $path.'.'.$name;
            if (is_array($value)) {
                self::assertTree($value, $child);

                continue;
            }
            if (is_string($value)) {
                if (strlen($value) > 400 || preg_match('/[<>\r\n]|url\s*\(|expression\s*\(|javascript:/i', $value) === 1) {
                    throw new InvalidChat($child.' is not a safe theme value.');
                }

                continue;
            }
            if (! is_int($value) && ! is_float($value) && ! is_bool($value) && $value !== null) {
                throw new InvalidChat($child.' must be a string, number, boolean, or object.');
            }
        }
    }

    /**
     * @param  array<string, mixed>  $base
     * @param  array<string, mixed>  $extra
     * @return array<string, mixed>
     */
    private static function merge(array $base, array $extra): array
    {
        foreach ($extra as $key => $value) {
            if (is_array($value) && is_array($base[$key] ?? null) && ! array_is_list($value)) {
                $base[$key] = self::merge($base[$key], $value);

                continue;
            }
            $base[$key] = $value;
        }

        return $base;
    }
}
