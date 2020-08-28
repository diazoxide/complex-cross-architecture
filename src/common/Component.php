<?php


namespace NovemBit\CCA\common;


abstract class Component
{

    /**
     * @var Component|null
     */
    private $parent;

    public function __construct(?self $parent = null, $params = [])
    {
        $this->parent = $parent;
    }

    public function getParent(): ?self
    {
        return $this->parent;
    }
}