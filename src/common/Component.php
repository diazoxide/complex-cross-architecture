<?php


namespace NovemBit\CCA\common;


abstract class Component extends Container
{

    /**
     * @var Component|null
     */
    private $parent;

    /**
     * Component constructor.
     * @param Container|null $parent
     * @param array $params
     */
    private function __construct(?Container $parent = null, $params = [])
    {
        $this->parent = $parent;

        $this->initComponents();

        $this->main($params);
    }

    /**
     * @param array|null $params
     */
    abstract public function main(?array $params = []): void;

    /**
     * @param Container|null $parent
     * @param array $params
     * @return static
     */
    public static function init(?Container $parent = null, $params = []): self
    {
        return new static($parent, $params);
    }

    /**
     * @return $this|null
     */
    public function getParent(): ?Container
    {
        return $this->parent;
    }

}