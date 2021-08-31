<?php

namespace NovemBit\CCA\wp\helpers;

/**
 * Class Preloader
 * @package NovemBit\CCA\wp\helpers
 */
final class Preloader
{

    /**
     * Associated data
     * @var array[]
     */
    private static array $data = [
        'preload'    => [],
        'preconnect' => []
    ];

    /**
     * Inject single item
     *
     * @param  array  $item  Item configuration
     */
    private static function injectItem(array $item): void
    {
        unset($item['order']);
        $tag = '<link';
        foreach ($item as $prop => $value) {
            $tag .= $value ? sprintf(' %s="%s"', $prop, $value) : sprintf(' %s', $prop);
        }
        $tag .= " />\n";

        echo $tag;
    }

    /**
     * @param  array  $a  First data item to compare
     * @param  array  $b  Second data item to compare
     *
     * @return int
     */
    private static function sort(array $a, array $b): int
    {
        return absint($a['order']) - absint($b['order']);
    }

    /**
     * Initialize
     */
    public static function init(): void
    {
        add_action('wp_head', [__CLASS__, 'inject'], 2);
    }

    /**
     * Inject data
     * @hooked in "wp_head" action
     */
    public static function inject(): void
    {
        foreach (self::$data as $data) {
            usort($data, [__CLASS__, 'sort']);
            array_map([__CLASS__, 'injectItem'], $data);
        }
    }

    /**
     * Add item for preload
     *
     * @param  string  $href  Preload URL
     * @param  string  $as  Preload type
     * @param  array  $attr  Optional: Additional attributes
     */
    public static function addPreload(string $href, string $as, array $attr = []): void
    {
        if ($href) {
            unset($attr['href'], $attr['rel'], $attr['as']);
            self::$data['preload'][md5($href)] = array_merge(
                [
                    'rel'   => 'preload',
                    'as'    => $as,
                    'href'  => $href,
                    'order' => 100
                ],
                $attr
            );
        }
    }

    /**
     * Add item for preconnect
     *
     * @param  string  $href  Preconnect URL
     * @param  array  $attr  Optional: Additional attributes
     */
    public static function addPreconnect(string $href, array $attr = []): void
    {
        if ($href) {
            unset($attr['href'], $attr['rel']);
            self::$data['preconnect'][md5($href)] = array_merge(
                [
                    'rel'   => 'preconnect',
                    'href'  => $href,
                    'order' => 100
                ],
                $attr
            );
        }
    }
}

Preloader::init();