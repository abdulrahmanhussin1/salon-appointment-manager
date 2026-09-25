<?php

namespace App\Translation;

use Illuminate\Translation\Translator;

class CaseInsensitiveTranslator extends Translator
{
    /**
     * Cache of lowercased translation keys per locale.
     *
     * @var array<string, array<string, string>>
     */
    protected array $lowerKeys = [];

    /**
     * Get the translation for the given key.
     *
     * @param  string  $key
     * @param  string|null  $locale
     * @param  bool  $fallback
     * @return string|array
     */
    public function get($key, array $replace = [], $locale = null, $fallback = true)
    {
        $locale = $locale ?: $this->locale;

        // Load JSON translations
        $this->load('*', '*', $locale);

        // 1. Direct exact match
        $line = $this->loaded['*']['*'][$locale][$key] ?? null;

        // 2. Case-insensitive & trimmed match for JSON keys
        if (! isset($line) && is_string($key)) {
            $trimmedKey = trim($key);
            $line = $this->loaded['*']['*'][$locale][$trimmedKey] ?? null;

            if (! isset($line)) {
                $lowerKey = mb_strtolower($trimmedKey);
                if (! isset($this->lowerKeys[$locale])) {
                    $this->buildLowerIndex($locale);
                }
                $line = $this->lowerKeys[$locale][$lowerKey] ?? null;
            }
        }

        // 3. If still not found and fallback is allowed, check fallback locale JSON
        if (! isset($line) && $fallback && $locale !== $this->fallback && is_string($key)) {
            $fallbackLocale = $this->fallback;
            $this->load('*', '*', $fallbackLocale);

            $line = $this->loaded['*']['*'][$fallbackLocale][$key] ?? null;

            if (! isset($line)) {
                $trimmedKey = trim($key);
                $line = $this->loaded['*']['*'][$fallbackLocale][$trimmedKey] ?? null;

                if (! isset($line)) {
                    $lowerKey = mb_strtolower($trimmedKey);
                    if (! isset($this->lowerKeys[$fallbackLocale])) {
                        $this->buildLowerIndex($fallbackLocale);
                    }
                    $line = $this->lowerKeys[$fallbackLocale][$lowerKey] ?? null;
                }
            }
        }

        // 4. Fallback to PHP language files (group/file format like auth.failed)
        if (! isset($line)) {
            [$namespace, $group, $item] = $this->parseKey($key);

            $locales = $fallback ? $this->localeArray($locale) : [$locale];

            foreach ($locales as $languageLineLocale) {
                if (! is_null($line = $this->getLine(
                    $namespace, $group, $languageLineLocale, $item, $replace
                ))) {
                    return $line;
                }
            }

            $key = $this->handleMissingTranslationKey(
                $key, $replace, $locale, $fallback
            );
        }

        return $this->makeReplacements($line ?: $key, $replace);
    }

    /**
     * Build the lowercase index for JSON translations of a locale.
     */
    protected function buildLowerIndex(string $locale): void
    {
        $this->lowerKeys[$locale] = [];
        $loaded = $this->loaded['*']['*'][$locale] ?? [];

        foreach ($loaded as $k => $v) {
            if (is_string($k)) {
                $this->lowerKeys[$locale][mb_strtolower(trim($k))] = $v;
            }
        }
    }
}
