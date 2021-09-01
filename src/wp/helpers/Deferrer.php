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
     * Check whether handle is already defered
     * @param  string  $handle  Handle name
     *
     * @return bool
     */
    private static function isDeferred(string $handle): bool
    {
        return in_array($handle, self::$scripts);
    }

    /**
     * Setup component
     */
    public static function init(): void
    {
        if (!is_admin()) {
            add_filter('script_loader_tag', [__CLASS__, 'editScriptLoaderTag'], (PHP_INT_MAX - 10), 2);
        }
    }

    /**
     * Add specific callback to deferring detection
     *
     * @param  callable  $callback  Callback method
     */
    public static function addCallback(callable $callback): void
    {
        self::$callbacks[] = $callback;
    }

    /**
     * Check script for the possibility to be deferred
     *
     * @param  string  $handle  Handle name
     */
    public static function check(string $handle, callable $callback): bool
    {
        $return = false;
        if (self::isDeferred($handle)) {
            $return = true;
        } else {
            if (call_user_func($callback, $handle)) {
                self::$scripts[] = $handle;
                $return          = true;
            }

            $deps = isset(wp_scripts()->registered[$handle]) ? wp_scripts()->registered[$handle]->deps : [];
            foreach ($deps as $dep) {
                if (!self::isDeferred($dep)) {
                    return self::check($dep, $callback);
                }
            }
        }

        return $return;
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
            if (self::check($handle, $callback)) {
                break;
            }
        }
        if (self::isDeferred($handle)) {
            $tag = preg_replace('/><\/script>/', ' defer$0', $tag);
        }

        return $tag;
    }

}

Deferrer::init();