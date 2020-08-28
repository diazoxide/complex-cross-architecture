<?php


namespace NovemBit\CCA\common;


abstract class ComponentOwner
{

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
                $this->{$name} = $component::init($this, $config);
            }
        }
    }


    public function getComponents(): ?array
    {
        return $this->components;
    }
}