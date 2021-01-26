<?php


namespace NovemBit\CCA\common;


abstract class Container
{

    /**
     * @var array
     * */
    public $instances = [];

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

            $name = self::toSnakeCase($name);

            $config = [];
            if (is_array($component)) {
                $config = $component[1];
                $component = [0];
            }

            if (class_exists($component) && is_subclass_of($component, Component::class)) {
                $this->instances[$name] = $component::init($this, $config);
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
    public function hasComponent($name): bool
    {
        return isset($this->instances[$name]);
    }

    /**
     * @param mixed $name
     * @return mixed|null
     */
    public function getComponent($name): ?Component
    {
        return $this->instances[$name] ?? null;
    }

    /**
     * @param $name
     * @param $arguments
     * @return mixed
     */
    public function __call($name, $arguments)
    {
        $prefix = substr($name, 0, 3);

        $key = ltrim(substr($name, 3), '_');

        if ($prefix === 'get' && ($method_name = self::toSnakeCase($key)) && isset($this->instances[$method_name])) {
            return $this->instances[$method_name];
        } elseif (!isset ($this->{$name}) || !is_callable([$this, $name])) {
            trigger_error('Call to undefined method ' . static::class . '::' . $name . '()', E_USER_ERROR);
        }

        return $this->{$name};
    }

    public function __get($key)
    {
        return $this->getComponent(self::toSnakeCase($key)) ?? $this->{$key};
    }

    private static function toSnakeCase(string $input): string
    {
        preg_match_all('!([A-Z][A-Z0-9]*(?=$|[A-Z][a-z0-9])|[A-Za-z][a-z0-9]+)!', $input, $matches);
        $ret = $matches[0];
        foreach ($ret as &$match) {
            $match = $match === strtoupper($match) ? strtolower($match) : lcfirst($match);
        }
        return implode('_', $ret);
    }

}