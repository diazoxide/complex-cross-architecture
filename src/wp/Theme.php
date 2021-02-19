<?php


namespace NovemBit\CCA\wp;


abstract class Theme extends Container
{

    /**
     * Main singleton instance of class
     *
     * @var static
     * */
    private static $instance;

    /**
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
     */
    protected function __construct()
    {
        if (function_exists('add_action')) {
            add_action("after_switch_theme", [$this, 'onActivate'], 10, 2);
            add_action("switch_theme", [$this, 'onDeactivate'], 10, 2);
        }

        parent::__construct(null, []);
    }

    /**
     * Trigger on plugin install
     *
     * @param $oldname
     * @param bool $oldtheme
     * @return void
     */
    public function onActivate($oldname, $oldtheme = false): void
    {
    }

    /**
     * Trigger on plugin uninstall
     *
     * @param $newname
     * @param $newtheme
     * @return void
     */
    public function onDeactivate($newname, $newtheme): void
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