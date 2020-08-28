<?php


namespace NovemBit\CCA\demo;


use NovemBit\CCA\wp\Plugin;

class MyPlugin extends Plugin
{

    /**
     * @var MyComponent1
     * */
    public $my_component;

    /**
     * @return void
     */
    protected function main(): void
    {
        $this->my_component = new MyComponent1();
    }

    /**
     * @return string
     */
    public function getName(): string
    {
        return 'MyPlugin';
    }
}