<?php


namespace NovemBit\CCA\wp;


use NovemBit\CCA\common\Container;
use RuntimeException;

abstract class Plugin extends Container
{
    /**
     * Main plugin file
     *
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
     *
     * @var static
     * */
    private static $_instances;


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
    private function __construct($plugin_file)
    {
        $this->plugin_file = $plugin_file;

        if (function_exists('register_activation_hook')) {
            register_activation_hook($this->getPluginFile(), [$this, 'onActivate']);
        }

        if (function_exists('register_deactivation_hook')) {
            register_deactivation_hook($this->getPluginFile(), [$this, 'onDeactivate']);
        }

        $this->initComponents();

        $this->main();
    }

    /**
     * Main plugin run method
     *
     * @return void
     */
    abstract protected function main():void;

    /**
     * Trigger on plugin install
     *
     * @return void
     */
    public function onActivate(): void
    {
        if ($this->early_init) {
            $this->generateMUPluginFile();
        }
    }

    /**
     * Trigger on plugin uninstall
     *
     * @return void
     */
    public function onDeactivate(): void
    {
        if ($this->early_init) {
            $this->removeMUPluginFile();
        }
    }

    /**
     * Generate MU plugin file
     *
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
     * Remove Generated MU plugin file
     *
     * @return bool
     */
    protected function removeMUPluginFile(): bool
    {
        $mu = WPMU_PLUGIN_DIR . '/' . $this->getMUPluginName() . '.php';
        return unlink($mu);
    }

    /**
     * @return mixed
     */
    public function getPluginFile():string
    {
        return $this->plugin_file;
    }

    /**
     * @return mixed
     * @see getPluginBasename
     */
    public function getPluginDirUrl():string
    {
        return plugin_dir_url($this->getPluginFile());
    }

    /**
     * @return mixed
     */
    public function getPluginBasename():string
    {
        return plugin_basename($this->getPluginFile());
    }

    /**
     * @return string
     */
    public function getMUPluginName(): string
    {
        return $this->getName();
    }

    /**
     * Plugin unique name | slug
     *
     * @return string
     */
    abstract public function getName(): string;

}
