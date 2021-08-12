<?php


namespace NovemBit\CCA\wp;


abstract class Container extends \NovemBit\CCA\common\Container
{

    protected $styles = [];
    protected $scripts = [];

    protected $version = null;

    protected $assets_root_uri = null;
    protected $assets_root_path = null;

    private function processStyles()
    {
        foreach ($this->styles as $key => &$config) {
            add_action(
                $config['action'] ?? 'init',
                function () use ($key, &$config) {
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
                    $this->enqueueStyle($key, $config);
                },
                $config['priority'] ?? 10
            );
        }
    }

    private function processScripts()
    {
        foreach ($this->scripts as $key => &$config) {
            add_action(
                $config['action'] ?? 'init',
                function () use ($key, &$config) {
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
                    $this->enqueueScript($key, $config);
                },
                $config['priority'] ?? 10
            );
        }
    }

    protected function __construct(?\NovemBit\CCA\common\Container $parent = null, $params = [])
    {
        parent::__construct($parent, $params);

        if ((!empty($this->scripts) || !empty($this->styles)) && !$this->getAssetsRootURI()) {
            trigger_error('Component $assets_relative_uri property not defined', E_USER_ERROR);
        }

        $this->processStyles();
        $this->processScripts();
    }

    public function getAssetsRootURI( string $relative = '' )
    {
        $uri = null;
        if (isset($this->assets_root_uri)) {
            $uri = trailingslashit($this->assets_root_uri) . ltrim($relative, '/');
        } elseif($this->getParent()) {
            $uri = $this->getParent()->getAssetsRootURI($relative);
        }
        return $uri;
    }

    public function getAssetsRootPath( string $relative = '' )
    {
        $path = null;
        if (isset($this->assets_root_path)) {
            $path = wp_normalize_path($this->assets_root_path . '/' . $relative);
        } elseif ($this->getParent()) {
            $path = $this->getParent()->getAssetsRootPath($relative);
        }
        return $path;
    }

    public function getVersion()
    {
        return $this->version ?? ($this->getParent() ? $this->getParent()->getVersion() : null) ?? 'N/A';
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
        $styles = array_filter($this->styles, function($style) {
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
            $this->styles[$handle . '-preload'] = $preload_config;
            $this->enqueueStyle($handle . '-preload', $preload_config);
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

        // 2. Localize
        foreach ($config['data'] as $localize) {
            wp_localize_script($handle, $localize['name'], $localize['data']);
        };
    }

    /**
     * @param  string  $handle_name
     * @param  string  $name
     * @param $data
     */
    public function localizeScript(string $handle_name, string $name, $data)
    {
        $this->scripts[$handle_name]['data'][] = [
            'name' => $name,
            'data' => $data
        ];
    }

}