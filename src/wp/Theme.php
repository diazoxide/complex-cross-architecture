<?php
namespace NovemBit\CCA\wp;

use NovemBit\CCA\wp\components\Preloader;

/**
 * Class Theme
 * @package NovemBit\CCA\wp
 */
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
     * Theme constructor.
     */
    protected function __construct()
    {
        // Configure MU components
        unset($this->components['preloader']);

        // Setup assets data
        $this->assets_root_uri = $this->getParentDirectoryUri();
        $this->assets_root_path = $this->getParentDirectory();

        // Setup hooks
        if (function_exists('add_action')) {
            add_action('after_switch_theme', [$this, 'onActivate'], 10, 2);
            add_action('switch_theme', [$this, 'onDeactivate'], 10, 3);
        }

        parent::__construct();
    }

    /**
     * Theme unique name
     * @return string
     */
    abstract public function getName(): string;

    /**
     * Get theme directory
     * @return string
     */
    final public function getDirectory(): string
    {
        return get_stylesheet_directory();
    }

    /**
     * Get theme URI
     * @return string
     */
    final public function getDirectoryUri(): string
    {
        return get_stylesheet_directory_uri();
    }

    /**
     * Get parent theme directory
     * @return string
     */
    final public function getParentDirectory(): string
    {
        return get_template_directory();
    }

    /**
     * Get parent theme URI
     * @return string
     */
    final public function getParentDirectoryUri(): string
    {
        return get_template_directory_uri();
    }

    /**
     * Callback to run on theme activation
     * @hooked in "after_switch_theme" action
     * @param  string           $oldname   Old theme name.
     * @param  \WP_Theme|false  $oldtheme  WP_Theme instance of the old theme.
     */
    public function onActivate($oldname, $oldtheme = false): void
    {
    }

    /**
     * Callback to run on theme deactivation
     * @ooked in "switch_theme" action
     * @param  string     $newname      Name of the new theme.
     * @param  \WP_Theme  $new_theme    WP_Theme instance of the new theme.
     * @param  \WP_Theme  $old_theme    WP_Theme instance of the old theme.
     */
    public function onDeactivate(string $newname, \WP_Theme $new_theme, \WP_Theme $old_theme): void
    {
    }

}