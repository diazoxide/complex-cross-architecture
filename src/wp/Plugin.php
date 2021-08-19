<?php


namespace NovemBit\CCA\wp;


use NovemBit\CCA\wp\components\Preloader;
use RuntimeException;

abstract class Plugin extends Container
{
    /**
     * Main plugin file
     * @var string
     * */
    private $plugin_file;

    /**
     * Run plugin on mu plugins loaded action
     * Generate MU plugin and run plugin instance earlier
     *
     * @var bool
     */
    protected $early_init = false;

    /**
     * Main singleton instance of class
     * @var array
     * */
    private static $_instances = [];

    /**
     * @param null $plugin_file
     *
     * @return static
     */
    public static function instance($plugin_file = null)
    {
        
        if (!isset(self::$_instances[static::class])) {
            self::$_instances[static::class] = new static($plugin_file);
        }

        return self::$_instances[static::class];
    }

    /**
     * Bootstrap constructor.
     *
     * @param $plugin_file
     */
    protected function __construct($plugin_file)
    {
        // Configure MU components
        unset($this->components['preloader']);

        // Setup plugin main file
        $this->plugin_file = $plugin_file;

        // Setup assets data
        $this->assets_root_uri = $this->getPluginDirUrl();
        $this->assets_root_path = $this->getPluginDirPath();

        // Setup hooks
        if (function_exists('register_activation_hook')) {
            register_activation_hook($this->getPluginFile(), [$this, 'onActivate']);
        }

        if (function_exists('register_deactivation_hook')) {
            register_deactivation_hook($this->getPluginFile(), [$this, 'onDeactivate']);
        }

        parent::__construct();
    }

    /**
     * Plugin unique name
     * @return string
     */
    abstract public function getName(): string;

    /**
     * Get preloader instance
     * @return Preloader
     */
    final public function getPreloader(): Preloader
    {
        return Preloader::instance();
    }

    /**
     * @return string
     */
    public function getMUPluginName(): string
    {
        return $this->getName();
    }

    /**
     * Generate MU plugin file
     * @return bool
     */
    protected function generateMUPluginFile(): bool
    {
        if (!file_exists(WPMU_PLUGIN_DIR)
            && !mkdir($concurrentDirectory = WPMU_PLUGIN_DIR, 0777, true)
            && !is_dir($concurrentDirectory)
        ) {
            throw new RuntimeException(sprintf('Directory "%s" was not created', $concurrentDirectory));
        }
        $mu = WPMU_PLUGIN_DIR . '/' . $this->getMUPluginName() . '.php';
        $content = '<?php' . PHP_EOL;
        $content .= ' // This is auto generated file' . PHP_EOL;
        $content .= 'include_once WP_PLUGIN_DIR."/' . $this->getPluginBasename() . '";';
        return file_put_contents($mu, $content) ? true : false;
    }

    /**
     * Remove MU plugin file
     * @return bool
     */
    protected function removeMUPluginFile(): bool
    {
        $mu = WPMU_PLUGIN_DIR . '/' . $this->getMUPluginName() . '.php';
        return unlink($mu);
    }

    /**
     * Gets the basename of a plugin
     * @return string The name of a plugin
     */
    public function getPluginBasename(): string
    {
        return plugin_basename($this->getPluginFile());
    }

    /**
     * Get plugin main file
     * @return mixed
     */
    public function getPluginFile(): string
    {
        return $this->plugin_file;
    }

    /**
     * Get URL to plugin specific file
     * @param string $relative File url relative to plugin main URL
     * @return string
     */
    public function getPluginDirUrl(string $relative = ''): string
    {
        return plugin_dir_url($this->getPluginFile()) . $relative;
    }

    /**
     * Get path to plugin specific file
     * @param string $relative File path relative to plugin main URL
     * @return string
     */
    public function getPluginDirPath($relative = ''): string
    {
        return wp_normalize_path(plugin_dir_path($this->getPluginFile()) . $relative);
    }

    /**
     * Callback to execute on plugin activation
     * @return void
     */
    public function onActivate(): void
    {
        if ($this->early_init) {
            $this->generateMUPluginFile();
        }
    }

    /**
     * Callback to execute on plugin deactivation
     * @return void
     */
    public function onDeactivate(): void
    {
        if ($this->early_init) {
            $this->removeMUPluginFile();
        }
    }

}
