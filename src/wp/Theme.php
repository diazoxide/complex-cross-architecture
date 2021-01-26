<?php


namespace NovemBit\CCA\wp;


use NovemBit\CCA\common\Container;
use RuntimeException;

abstract class Theme extends Container
{

    /**
     * Main singleton instance of class
     *
     * @var static
     * */
    private static $instance;

    /**
     * @param null $plugin_file
     *
     * @return static
     */
    public static function instance(): self
    {
        if (!isset(self::$instance)) {
            self::$instance = new static();
        }

        return self::$instance;
    }

    /**
     * Bootstrap constructor.
     *
     * @param $plugin_file
     */
    private function __construct()
    {
        if (function_exists('add_action')) {
            add_action("after_switch_theme", [$this, 'onActivate'], 10, 2);
            add_action("switch_theme", [$this, 'onDeactivate'], 10, 2);
        }

        $this->initComponents();

        $this->main();
    }

    /**
     * Main plugin run method
     *
     * @return void
     */
    abstract protected function main(): void;

    /**
     * Trigger on plugin install
     *
     * @return void
     */
    protected function onActivate($oldname, $oldtheme = false): void
    {
    }

    /**
     * Trigger on plugin uninstall
     *
     * @return void
     */
    protected function onDeactivate($newname, $newtheme): void
    {
    }

    /**
     * @return string
     */
    public function getDirectory()
    {
        return get_stylesheet_directory();
    }

    /**
     * @return string
     */
    public function getDirectoryUri()
    {
        return get_stylesheet_directory_uri();
    }

    /**
     * @return string
     */
    public function getParentDirectory()
    {
        return get_template_directory();
    }

    /**
     * @return string
     */
    public function getParentDirectoryUri()
    {
        return get_template_directory_uri();
    }

    /**
     * Plugin unique name | slug
     *
     * @return string
     */
    abstract public function getName(): string;

}