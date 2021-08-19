<?php
namespace NovemBit\CCA\wp;

use NovemBit\CCA\wp\components\AssetsManager;

abstract class Container extends \NovemBit\CCA\common\Container
{
    /**
     * Component styles
     * @var array
     */
    protected $styles = [];

    /**
     * Get style handle configuration
     * @param  string  $handle  Handle name
     * @return array
     */
    final public function getStyle(string $handle): array
    {
        return $this->styles[$handle] ?? [];
    }

    /**
     * Get all styles
     * @return array
     */
    final public function getStyles(): array
    {
        return $this->styles;
    }

    /**
     * Add single style
     * @param  string  $handle  Handle name
     * @param  array  $config  Style configuration
     */
    final public function addStyle(string $handle, array $config): void
    {
        $this->styles[$handle] = $config;
    }

    /**
     * Add bulk styles
     * @param  array  $styles  Array of styles configuration
     */
    final public function addBulkStyles(array $styles): void
    {
        $this->styles = array_merge($this->styles, $styles);
    }

    /**
     * Component scripts
     * @var array
     */
    protected $scripts = [];

    /**
     * Get script handle configuration
     * @param  string  $handle  Handle name
     * @return array
     */
    final public function getScript(string $handle): array
    {
        return $this->scripts[$handle] ?? [];
    }

    /**
     * Get all scripts
     * @return array
     */
    final public function getScripts(): array
    {
        return $this->scripts;
    }

    /**
     * Localize specific script
     * @param  string  $handle  Script handle name
     * @param  string  $name  Variable name
     * @param mixed  $data  Localize data
     */
    final public function localizeScript(string $handle, string $name, $data)
    {
        if (isset($this->scripts[$handle])) {
            $this->scripts[$handle]['data'][] = [
                'name' => $name,
                'data' => $data
            ];
        }
    }

    /**
     * Add single script
     * @param  string  $handle  Handle name
     * @param  array  $config  Script configuration
     */
    final public function addScripts(string $handle, array $config): void
    {
        $this->scripts[$handle] = $config;
    }

    /**
     * Add bulk scripts
     * @param  array  $scripts  Array of scripts configuration
     */
    final public function addBulkScripts(array $scripts): void
    {
        $this->scripts = array_merge($this->scripts, $scripts);
    }

    /**
     * Component version
     * @var null
     */
    protected $version = null;

    /**
     * Component assets root URI
     * @var null|string
     */
    protected $assets_root_uri = null;

    /**
     * Get component assets URI
     * @param  string  $relative  Optional: relative URI to specific file
     * @return string
     */
    public function getAssetsRootURI( string $relative = '' ): string
    {
        $uri = '';
        if (isset($this->assets_root_uri)) {
            $uri = trailingslashit($this->assets_root_uri) . ltrim($relative, '/');
        } elseif($this->getParent()) {
            $uri = $this->getParent()->getAssetsRootURI($relative);
        }
        return $uri;
    }

    /**
     * Component assets root path
     * @var null|string
     */
    protected $assets_root_path = null;

    /**
     * Get component assets path
     * @param  string  $relative  Optional: relative path to specific file
     * @return string
     */
    public function getAssetsRootPath( string $relative = '' ): string
    {
        $path = '';
        if (isset($this->assets_root_path)) {
            $path = wp_normalize_path($this->assets_root_path . '/' . $relative);
        } elseif ($this->getParent()) {
            $path = $this->getParent()->getAssetsRootPath($relative);
        }
        return $path;
    }

    /**
     * Get component version
     * @return string
     */
    public function getVersion()
    {
        return $this->version ?? ($this->getParent() ? $this->getParent()->getVersion() : null) ?? 'N/A';
    }

    /**
     * Execute additional functionality before component initialize
     */
    public function beforeInit(): void
    {
        if (!$this instanceof AssetsManager) {
            $this->components['assetsManager'] = AssetsManager::class;
        }
    }

    /**
     * Execute additional functionality after component full initialize
     */
    public function afterInit(): void
    {
        if ($this->hasComponent('assetsManager')) {
            $this->getComponent('assetsManager')->run();
        }
    }

}