<?php


namespace NovemBit\CCA\wp;


abstract class Container extends \NovemBit\CCA\common\Container
{

    protected $styles = [];
    protected $scripts = [];

    protected $version = null;

    protected $assets_root_uri = null;

    protected function __construct(?\NovemBit\CCA\common\Container $parent = null, $params = [])
    {
        parent::__construct($parent, $params);


        if ((!empty($this->scripts) || !empty($this->styles)) && !$this->getAssetsRootURI()) {
            trigger_error('Component $assets_relative_uri property not defined', E_USER_ERROR);
        }

        foreach ($this->styles as $key => &$config) {
            add_action(
                $config['action'] ?? 'init',
                function () use ($key, &$config) {
                    unset($config['action'], $config['priority']);
                    $this->enqueueStyle($key, $config);
                },
                $config['priority'] ?? 10
            );
        }

        foreach ($this->scripts as $key => &$config) {
            add_action(
                $config['action'] ?? 'init',
                function () use ($key, &$config) {
                    unset($config['action'], $config['priority']);
                    $this->enqueueScript($key, $config);
                },
                $config['priority'] ?? 10
            );
        }
    }

    private function enqueueStyle($handle, $config)
    {
        if (isset($config['callback']) && is_callable($config['callback'])) {
            $config = call_user_func($config['callback']);
        }
        $config   = wp_parse_args($config, [
            'url'     => '',
            'deps'    => [],
            'version' => $this->getVersion(),
            'media'   => 'all',
            'external' => false,
        ]);

        if (!$config['url']) {
            return;
        }

        wp_enqueue_style(
            $handle,
            (!!$config['external'] ? '' : trailingslashit($this->getAssetsRootURI())) . $config['url'],
            $config['deps'],
            $config['version'],
            $config['media']
        );
    }

    public function getAssetsRootURI()
    {
        return $this->assets_root_uri ?? ($this->getParent() ? $this->getParent()->getAssetsRootURI() : null) ?? false;
    }

    public function getVersion()
    {
        return $this->version ?? ($this->getParent() ? $this->getParent()->getVersion() : null) ?? 'N/A';
    }

    private function enqueueScript($handle, $config)
    {
        if (isset($config['callback']) && is_callable($config['callback'])) {
            $config = call_user_func($config['callback']);
        }

        $config   = wp_parse_args($config, [
            'url'       => '',
            'deps'      => [],
            'version'   => $this->getVersion(),
            'in_footer' => false,
            'external'  => false,
            'data'      => [],
        ]);

        if (!$config['url']) {
            return;
        }

        // 1. Enqueue
        wp_enqueue_script(
            $handle,
            (!!$config['external'] ? '' : trailingslashit($this->getAssetsRootURI())) . $config['url'],
            $config['deps'],
            $config['version'],
            $config['in_footer']
        );

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