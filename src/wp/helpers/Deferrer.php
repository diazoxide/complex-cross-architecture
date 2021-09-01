<?php

namespace NovemBit\CCA\wp\helpers;

/**
 * Class Deferrer
 * @package NovemBit\CCA\wp\helpers
 */
class Deferrer
{

    /**
     * Scripts handles names for deferring
     * @var array
     */
    private static array $scripts = [];

    /**
     * Callbacks list to use for deferring detection
     * @var array
     */
    private static array $callbacks = [];

    /**
     * Add specific callback to deferring detection
     * @param  callable  $callback  Callback method
     */
    public static function addCallback(callable $callback): void
    {
        self::$callbacks[] = $callback;
    }

    /**
     * Setup component
     *
     * @param  array  $params
     */
    public static function init(): void
    {
        if (!is_admin()) {
            add_filter('script_loader_tag', [__CLASS__, 'editScriptLoaderTag'], (PHP_INT_MAX - 10), 2);
        }
    }

    /**
     * Check script for the possibility to be deferred
     *
     * @param  string  $handle  Handle name
     */
    public static function checkForDeferring(string $handle, callable $callback): void
    {
        $deps = [];
        if (isset(wp_scripts()->registered[$handle])) {
            $deps = wp_scripts()->registered[$handle]->deps;
        }

        if (call_user_func($callback, $handle)) {
            self::$scripts[] = $handle;
        } else {
            foreach ($deps as $dep) {
                if (call_user_func($callback, $dep)) {
                    self::$scripts[] = $handle;
                    break;
                }
            }
        }

        foreach ($deps as $handle) {
            if (!in_array($handle, self::$scripts)) {
                self::checkForDeferring($handle, $callback);
            }
        }

        self::$scripts = array_unique(self::$scripts);
    }

    /**
     * Callback to edit script tag markup
     *
     * @param  string  $tag  Current tag
     * @param  string  $handle  Handle name
     *
     * @return string
     */
    public static function editScriptLoaderTag(string $tag, string $handle): string
    {
        foreach (self::$callbacks as $callback) {
            self::checkForDeferring($handle, $callback);
        }
        if (in_array($handle, self::$scripts)) {
            $tag = preg_replace('/><\/script>/', ' defer$0', $tag);
        }

        return $tag;
    }

}

Deferrer::init();