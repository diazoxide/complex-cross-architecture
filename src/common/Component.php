<?php


namespace NovemBit\CCA\common;


abstract class Component extends ComponentOwner
{

    /**
     * @var Component|null
     */
    private $parent;

    /**
     * Component constructor.
     * @param ComponentOwner|null $parent
     * @param array $params
     */
    private function __construct(?ComponentOwner $parent = null, $params = [])
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
     * @param ComponentOwner|null $parent
     * @param array $params
     * @return static
     */
    public static function init(?ComponentOwner $parent = null, $params = []): self
    {
        return new static($parent, $params);
    }

    /**
     * @return $this|null
     */
    public function getParent(): ?ComponentOwner
    {
        return $this->parent;
    }

}