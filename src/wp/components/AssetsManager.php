<?php
namespace NovemBit\CCA\wp\components;

use NovemBit\CCA\wp\Container;

/**
 * Class AssetsManager
 * @package NovemBit\CCA\wp\components
 */
class AssetsManager extends Container {

    /**
     * Entry point
     * @param  array|null  $params
     */
    protected function main(?array $params = []): void
    {
    }

    /**
     * Run component
     */
    public function run()
    {
        if ((!empty($this->getParent()->getStyles()) || !empty($this->getParent()->getScripts())) && !$this->getAssetsRootURI()) {
            trigger_error('Component $assets_relative_uri property not defined', E_USER_ERROR);
        }

        $this->executeStyles();
        $this->executeScripts();
    }

    /**
     * Execute styles
     */
    private function executeStyles()
    {
        foreach ($this->getParent()->getStyles() as $handle => $config) {
            add_action(
                $config['action'] ?? 'init',
                function () use ($handle) {
                    $config = $this->getParent()->getStyle($handle);
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
                    $this->getParent()->addStyle($handle, $config);
                    $this->enqueueStyle($handle, $config);
                },
                $config['priority'] ?? 10
            );
        }
    }

    /**
     * Execute scripts
     */
    private function executeScripts()
    {
        foreach ($this->getParent()->getScripts() as $handle => $config) {
            add_action(
                $config['action'] ?? 'init',
                function () use ($handle) {
                    $config = $this->getParent()->getScript($handle);
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
                    $this->getParent()->addScript($handle, $config);
                    $this->enqueueScript($handle, $config);
                },
                $config['priority'] ?? 10
            );
        }
    }

    /**
     * Callback method to edit style tag and modify its attributes
     * @hooked in "style_loader_tag" filter
     * @param string  $tag  Current tag
     * @param string  $handle  Style handle name
     * @return string
     */
    public function editStyleLoaderTag($tag, $handle)
    {
        $styles = array_filter($this->getParent()->getStyles(), function($style) {
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
                $tag = str_replace('/>', join(' ', $attrs), $tag);
            }
        }

        return $tag;
    }

    /**
     * Enqueue single style
     * @param string  $handle  Handle name
     * @param array  $config  Handle configuration
     */
    private function enqueueStyle($handle, array $config)
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
                'preload' => [],
                'attributes' => array_merge($config['preload'], [
                    'rel' => 'preload'
                ]),
                'register' => false,
                'withPath' => false,
            ]);
            $this->getParent()->addStyle("{$handle}-preload", $preload_config);
            $this->enqueueStyle("{$handle}-preload", $preload_config);
        }

        if (is_array($config['attributes']) && !empty($config['attributes'])) {
            add_filter('style_loader_tag', [$this, 'editStyleLoaderTag'], 10, 2);
        }

        $config['dependencies'] = $config['deps'];
        unset($config['deps']);
        if ($config['asset']) {
            $assets = [];
            $asset_file = $this->getAssetsRootPath($config['asset']);
            if ( file_exists( $asset_file ) ) {
                $assets = include( $asset_file );
            }
            $config = wp_parse_args($assets, $config);
        }

        if (!!$config['register']) {
            wp_register_style(
                $handle,
                (!!$config['external'] ? $config['url'] : $this->getAssetsRootURI($config['url'])),
                $config['dependencies'],
                $config['version'],
                $config['media']
            );
        } else {
            wp_enqueue_style(
                $handle,
                (!!$config['external'] ? $config['url'] : $this->getAssetsRootURI($config['url'])),
                $config['dependencies'],
                $config['version'],
                $config['media']
            );
        }

        if (!!$config['withPath'] && !$config['external']) {
            wp_style_add_data($handle, 'path', $this->getAssetsRootPath($config['url']));
        }
    }

    /**
     * Enqueue single script
     * @param string  $handle  Handle name
     * @param array  $config  Handle configuration
     */
    private function enqueueScript($handle, array $config)
    {
        $config = wp_parse_args(
            $config,
            [
                'url'       => '',
                'deps'      => [],
                'asset'     => '',
                'version'   => $this->getVersion(),
                'in_footer' => false,
                'external'  => false,
                'data'      => [],
                'register'  => false,
            ]
        );

        if (!$config['url']) {
            return;
        }

        $config['dependencies'] = $config['deps'];
        unset($config['deps']);
        if ($config['asset']) {
            $assets = [];
            $asset_file = $this->getAssetsRootPath($config['asset']);
            if ( file_exists( $asset_file ) ) {
                $assets = include( $asset_file );
            }
            $config = wp_parse_args($assets, $config);
        }

        // 1. Register / Enqueue
        if (!!$config['register']) {
            wp_register_script(
                $handle,
                (!!$config['external'] ? $config['url'] : $this->getAssetsRootURI($config['url'])),
                $config['dependencies'],
                $config['version'],
                $config['in_footer']
            );
        } else {
            wp_enqueue_script(
                $handle,
                (!!$config['external'] ? $config['url'] : $this->getAssetsRootURI($config['url'])),
                $config['dependencies'],
                $config['version'],
                $config['in_footer']
            );
        }

        $config['data'] = array_merge($config['data'], $this->getParent()->getScript($handle)['data'] ?? []);
        // 2. Localize
        foreach ($config['data'] as $localize) {
            wp_localize_script($handle, $localize['name'], $localize['data']);
        };
    }
}
