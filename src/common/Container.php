<?php


namespace NovemBit\CCA\common;


use Psr\Container\ContainerInterface;

abstract class Container implements ContainerInterface
{

    private $instances;

    /**
     * @var null|array
     * */
    public $components;

    /**
     * Init sub components
     */
    public function initComponents(): void
    {
        $components = $this->getComponents() ?? [];

        foreach ($components as $name => $component) {
            if (is_numeric($name)) {
                throw new \RuntimeException('SubComponent name must be declared in components array as key.');
            }

            $config = [];
            if (is_array($component)) {
                $config = $component[1];
                $component = [0];
            }

            if (class_exists($component) && is_subclass_of($component, Component::class)) {
                $this->instances[$name] = $component::init($this, $config);
                $this->{$name} = &$this->instances[$name];
            }
        }
    }

    public function getComponents(): ?array
    {
        return $this->components;
    }

    /**
     * @param mixed $name
     * @return bool
     */
    public function has($name): bool
    {
        return isset($this->instances[$name]);
    }

    /**
     * @param mixed $name
     * @return mixed|null
     */
    public function get($name): ?Component
    {
        return $this->instances[$name] ?? null;
    }

    protected $values = array();

    public function __get($key)
    {
        return $this->instances[self::toSnakeCase($key)];
    }

    private static function toSnakeCase(string $input):string {
        preg_match_all('!([A-Z][A-Z0-9]*(?=$|[A-Z][a-z0-9])|[A-Za-z][a-z0-9]+)!', $input, $matches);
        $ret = $matches[0];
        foreach ($ret as &$match) {
            $match = $match === strtoupper($match) ? strtolower($match) : lcfirst($match);
        }
        return implode('_', $ret);
    }

    public function __set($key, $value)
    {
        if ($value instanceof Component) {
            $this->instances[$key] = $value;
        }
    }
}