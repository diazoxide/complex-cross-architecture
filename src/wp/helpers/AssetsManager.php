<?php

namespace NovemBit\CCA\wp\helpers;

/**
 * Class AssetsManager
 * @package NovemBit\CCA\wp\helpers
 */
final class AssetsManager
{
    /**
     * Assets root URL
     * @var string
     */
    private string $url;

    /**
     * Assets root path
     * @var string
     */
    private string $path;

    /**
     * All styles
     * @var array
     */
    private array $styles = [];

    /**
     * All scripts
     * @var array
     */
    private array $scripts = [];

    /**
     * Version to use for assets
     * @var string
     */
    private string $version;

    /**
     * AssetsManager constructor.
     *
     * @param  string  $url  Assets root URL
     * @param  string  $path  Assets root path
     * @param  string  $version  Version to use for attached assets
     */
    public function __construct(string $url, string $path, string $version)
    {
        $this->url     = trailingslashit($url);
        $this->path    = $path;
        $this->version = $version;
    }

    /**
     * Get assets root URL
     *
     * @param  string  $relative  Optional: relative url to specific location
     *
     * @return string
     */
    public function getUrl(string $relative = ''): string
    {
        return $this->url ? $this->url . $relative : '';
    }

    /**
     * Get assets root path
     *
     * @param  string  $relative  Optional: relative path to specific location
     *
     * @return string
     */
    public function getPath(string $relative = ''): string
    {
        return $this->path ? wp_normalize_path($this->path . '/' . $relative) : '';
    }

    /**
     * Get basic configuration for assets item
     * @return array
     */
    private function getAssetBasics(): array
    {
        return [
            'action'   => 'init',
            'priority' => 10
        ];
    }

    /**
     * Add a single style
     *
     * @param  string  $handle  Handle name
     * @param  array  $config  Style configuration
     *
     * @return $this
     */
    public function addStyle(string $handle, array $config): AssetsManager
    {
        if (!isset($this->styles[$handle])) {
            $this->styles[$handle] = wp_parse_args($config, $this->getAssetBasics());
        }

        return $this;
    }

    /**
     * Update specific style
     *
     * @param  string  $handle  Handle name
     * @param  array  $config  Style configuration
     *
     * @return $this
     */
    public function updateStyle(string $handle, array $config): AssetsManager
    {
        if (isset($this->styles[$handle])) {
            $this->styles[$handle] = wp_parse_args($config, $this->getAssetBasics());
        }

        return $this;
    }

    /**
     * Remove specific style
     *
     * @param  string  $handle  Handle name
     *
     * @return $this
     */
    public function removeStyle(string $handle): AssetsManager
    {
        unset($this->styles[$handle]);

        return $this;
    }

    /**
     * Get specific style
     *
     * @param  string  $handle  Handle name
     *
     * @return array|null
     */
    public function getStyle(string $handle): ?array
    {
        return $this->styles[$handle] ?? null;
    }

    /**
     * Get all styles
     * @return array
     */
    public function getStyles(): array
    {
        return $this->styles;
    }

    /**
     * Add a single script
     *
     * @param  string  $handle  Handle name
     * @param  array  $config  Script configuration
     *
     * @return $this
     */
    public function addScript(string $handle, array $config): AssetsManager
    {
        if (!isset($this->scripts[$handle])) {
            $this->scripts[$handle] = wp_parse_args($config, $this->getAssetBasics());
        }

        return $this;
    }

    /**
     * Update specific script
     *
     * @param  string  $handle  Handle name
     * @param  array  $config  Script configuration
     *
     * @return $this
     */
    public function updateScript(string $handle, array $config): AssetsManager
    {
        if (isset($this->scripts[$handle])) {
            $this->scripts[$handle] = wp_parse_args($config, $this->getAssetBasics());
        }

        return $this;
    }

    /**
     * Remove specific script
     *
     * @param  string  $handle  Handle name
     *
     * @return $this
     */
    public function removeScript(string $handle): AssetsManager
    {
        unset($this->scripts[$handle]);

        return $this;
    }

    /**
     * Localize script
     *
     * @param  string  $handle  Handle name
     * @param  string  $name  Variable name to use
     * @param  mixed  $data  Localization data
     *
     * @return $this
     */
    public function localizeScript(string $handle, string $name, $data): AssetsManager
    {
        if (isset($this->scripts[$handle])) {
            $this->scripts[$handle]['data'][] = [
                'name' => $name,
                'data' => $data
            ];
        }

        return $this;
    }

    /**
     * Get specific script
     *
     * @param  string  $handle  Handle name
     *
     * @return array|null
     */
    public function getScript(string $handle): ?array
    {
        return $this->scripts[$handle] ?? null;
    }

    /**
     * Get all scripts
     * @return array
     */
    public function getScripts(): array
    {
        return $this->scripts;
    }

    /**
     * Get configured version
     * @return string
     */
    public function getVersion(): string
    {
        return $this->version;
    }

    /**
     * Parse single asset configuration
     *
     * @param  array  $config  Configuration to process
     *
     * @return array
     */
    private function parseAssetConfig(array $config): array
    {
        if (isset($config['callback']) && is_callable($config['callback'])) {
            $config = array_merge(
                $config,
                (array)call_user_func(
                    $config['callback'],
                    array_diff_key(
                        $config,
                        array_flip(['action', 'callback', 'priority'])
                    )
                )
            );
        }
        unset($config['action'], $config['callback'], $config['priority']);

        return $config;
    }

    /**
     * Run component
     */
    public function run(): void
    {
        if ((!empty($this->getStyles()) || !empty($this->getScripts())) && !$this->getUrl()) {
            trigger_error('Asset manager\'s $url property in not configured', E_USER_ERROR);
        }

        $this->executeStyles();
        $this->executeScripts();
    }

    /**
     * Execute styles
     */
    private function executeStyles(): void
    {
        foreach ($this->getStyles() as $handle => $config) {
            add_action(
                $config['action'],
                function () use ($handle) {
                    $config = $this->parseAssetConfig($this->getStyle($handle));
                    $this->updateStyle($handle, $config);
                    $this->enqueueStyle($handle, $config);
                },
                $config['priority']
            );
        }
    }

    /**
     * Execute scripts
     */
    private function executeScripts(): void
    {
        foreach ($this->getScripts() as $handle => $config) {
            add_action(
                $config['action'],
                function () use ($handle) {
                    $config = $this->parseAssetConfig($this->getScript($handle));
                    $this->updateScript($handle, $config);
                    $this->enqueueScript($handle, $config);
                },
                $config['priority']
            );
        }
    }

    /**
     * Callback method to edit style tag and modify its attributes
     * @hooked in "style_loader_tag" filter
     *
     * @param  string  $tag  Current tag
     * @param  string  $handle  Style handle name
     *
     * @return string
     */
    public function editStyleLoaderTag(string $tag, string $handle): string
    {
        $styles = array_filter($this->getStyles(), function ($style) {
            return isset($style['attributes']) && !empty($style['attributes']);
        });

        if (isset($styles[$handle])) {
            $attrs = [];
            foreach ($styles[$handle]['attributes'] as $name => $value) {
                if (!in_array($name, ['id', 'href'])) {
                    if (in_array($name, ['rel', 'media'])) {
                        $tag = preg_replace('/\s' . $name . '=("([^"]+)"|\'([^\']+)\')/', '', $tag);
                    }

                    if (is_null($value)) {
                        $attrs[] = $name;
                    } elseif ($value) {
                        $attrs[] = $name . '="' . $value . '"';
                    }
                }
            }

            if (!empty($attrs)) {
                $attrs[] = '/>';
                $tag     = str_replace('/>', join(' ', $attrs), $tag);
            }
        }

        return $tag;
    }

    /**
     * Enqueue single style
     *
     * @param  string  $handle  Handle name
     * @param  array  $config  Handle configuration
     */
    private function enqueueStyle(string $handle, array $config): void
    {
        $config = wp_parse_args(
            $config,
            [
                'url'        => '',
                'deps'       => [],
                'asset'      => '',
                'version'    => $this->getVersion(),
                'media'      => 'all',
                'external'   => false,
                'attributes' => [],
                'preload'    => [],
                'register'   => false,
                'withPath'   => false,
            ]
        );

        if (!$config['url']) {
            return;
        }

        if (is_array($config['preload']) && !empty($config['preload'])) {
            $preload_config = array_merge($config, [
                'preload'    => [],
                'attributes' => array_merge($config['preload'], [
                    'rel' => 'preload'
                ]),
                'register'   => false,
                'withPath'   => false,
            ]);
            $this->addStyle("{$handle}-preload", $preload_config);
            $this->enqueueStyle("{$handle}-preload", $preload_config);
        }

        if (is_array($config['attributes']) && !empty($config['attributes'])) {
            add_filter('style_loader_tag', [$this, 'editStyleLoaderTag'], 10, 2);
        }

        $config['dependencies'] = $config['deps'];
        unset($config['deps']);
        if ($config['asset']) {
            $assets     = [];
            $asset_file = $this->getPath($config['asset']);
            if (file_exists($asset_file)) {
                $assets = include($asset_file);
            }
            $config = wp_parse_args($assets, $config);
        }

        if (!!$config['register']) {
            wp_register_style(
                $handle,
                (!!$config['external'] ? $config['url'] : $this->getUrl($config['url'])),
                $config['dependencies'],
                $config['version'],
                $config['media']
            );
        } else {
            wp_enqueue_style(
                $handle,
                (!!$config['external'] ? $config['url'] : $this->getUrl($config['url'])),
                $config['dependencies'],
                $config['version'],
                $config['media']
            );
        }

        if (!!$config['withPath'] && !$config['external']) {
            wp_style_add_data($handle, 'path', $this->getUrl($config['url']));
        }
    }

    /**
     * Callback method to edit script tag and modify its attributes
     * @hooked in "script_loader_tag" filter
     *
     * @param  string  $tag  Current tag
     * @param  string  $handle  Script handle name
     *
     * @return string
     */
    public function editScriptLoaderTag(string $tag, string $handle): string
    {
        $scripts = array_filter($this->getScripts(), function ($script) {
            return isset($script['attributes']) && !empty($script['attributes']);
        });

        if (isset($scripts[$handle])) {
            $attrs = [];
            foreach ($scripts[$handle]['attributes'] as $name => $value) {
                if (!in_array($name, ['id', 'src'])) {
                    if (is_null($value)) {
                        $attrs[] = $name;
                    } elseif ($value) {
                        $attrs[] = $name . '="' . $value . '"';
                    }
                }
            }

            if (!empty($attrs)) {
                $tag = str_replace('></script>', ' ' . join(' ', $attrs) . '></script>', $tag);
            }
        }

        return $tag;
    }

    /**
     * Enqueue single script
     *
     * @param  string  $handle  Handle name
     * @param  array  $config  Handle configuration
     */
    private function enqueueScript(string $handle, array $config): void
    {
        $config = wp_parse_args(
            $config,
            [
                'url'        => '',
                'deps'       => [],
                'asset'      => '',
                'version'    => $this->getVersion(),
                'in_footer'  => false,
                'external'   => false,
                'attributes' => [],
                'preload'    => [],
                'data'       => [],
                'register'   => false,
            ]
        );

        if (!$config['url']) {
            return;
        }

        if (is_array($config['preload']) && !empty($config['preload'])) {
            $preload_config = array_merge($config, [
                'preload'    => [],
                'attributes' => array_merge($config['preload'], [
                    'rel' => 'preload'
                ]),
                'register'   => false
            ]);
            $this->addScript("{$handle}-preload", $preload_config);
            $this->enqueueScript("{$handle}-preload", $preload_config);
        }

        if (is_array($config['attributes']) && !empty($config['attributes'])) {
            add_filter('script_loader_tag', [$this, 'editScriptLoaderTag'], 10, 2);
        }

        $config['dependencies'] = $config['deps'];
        unset($config['deps']);
        if ($config['asset']) {
            $assets     = [];
            $asset_file = $this->getPath($config['asset']);
            if (file_exists($asset_file)) {
                $assets = include($asset_file);
            }
            $config = wp_parse_args($assets, $config);
        }

        // 1. Register / Enqueue
        if (!!$config['register']) {
            wp_register_script(
                $handle,
                (!!$config['external'] ? $config['url'] : $this->getUrl($config['url'])),
                $config['dependencies'],
                $config['version'],
                $config['in_footer']
            );
        } else {
            wp_enqueue_script(
                $handle,
                (!!$config['external'] ? $config['url'] : $this->getUrl($config['url'])),
                $config['dependencies'],
                $config['version'],
                $config['in_footer']
            );
        }

        $config['data'] = array_merge($config['data'], $this->getScript($handle)['data'] ?? []);
        // 2. Localize
        foreach ($config['data'] as $localize) {
            wp_localize_script($handle, $localize['name'], $localize['data']);
        };
    }

}
