<?php


namespace NovemBit\CCA\common;


abstract class Component
{

    /**
     * @var Component|null
     */
    private $parent;

    private function __construct(?self $parent = null, $params = [])
    {
        $this->parent = $parent;
    }

    public static function init(?self $parent = null, $params = []){
        return new static($parent,$params);
    }

    public function getParent(): ?self
    {
        return $this->parent;
    }
}