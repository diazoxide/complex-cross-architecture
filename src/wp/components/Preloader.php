<?php
namespace NovemBit\CCA\wp\components;

/**
 * Class Preloader
 * @package NovemBit\CCA\wp\components
 */
class Preloader {

    /**
     * @var Preloader Unique instance
     */
    private static $instance;

    /**
     * @return Preloader|static
     */
    public static function instance(): Preloader
    {
        if (!isset(self::$instance)) {
            self::$instance = new static();
        }

        return self::$instance;
    }

    /**
     * @var array[]
     */
    private $data = [
        'preload' => [],
        'preconnect' => []
    ];

    /**
     * Preloader constructor.
     */
    private function __construct()
    {
        add_action('wp_head', [$this, 'inject'], 2);
    }

    /**
     * Inject single item
     * @param  array  $item  Item configuration
     */
    private function injectItem(array $item): void
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
     * @param array  $a  First data item to compare
     * @param array  $b  Second data item to compare
     *
     * @return int
     */
    private function sort(array $a, array $b): int
    {
        return absint($a['order']) - absint($b['order']);
    }

    /**
     * Inject data
     * @hooked in "wp_head" action
     */
    public function inject(): void
    {
        foreach($this->data as $data) {
            usort($data, [$this, 'sort']);
            array_map([$this, 'injectItem'], $data);
        }
    }

    /**
     * Add item for preload
     * @param  string  $href  Preload URL
     * @param  string  $as    Preload type
     * @param  array  $attr  Optional: Additional attributes
     * @return $this
     */
    public function addPreload(string $href, string $as, array $attr = []): Preloader
    {
        if ($href) {
            unset($attr['href'], $attr['rel'], $attr['as']);
            $this->data['preload'][md5($href)] = array_merge(
                [
                    'rel' => 'preload',
                    'as' => $as,
                    'href' => $href,
                    'order' => 100
                ],
                $attr
            );
        }

        return $this;
    }

    /**
     * Add item for preconnect
     * @param  string  $href  Preconnect URL
     * @param  array  $attr  Optional: Additional attributes
     * @return $this
     */
    public function addPreconnect(string $href, array $attr = []): Preloader
    {
        if ($href) {
            unset($attr['href'], $attr['rel']);
            $this->data['preconnect'][md5($href)] = array_merge(
                [
                    'rel' => 'preconnect',
                    'href' => $href,
                    'order' => 100
                ],
                $attr
            );
        }

        return $this;
    }


}