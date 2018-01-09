<?php

namespace ShuntingYard\OperatorRegistry;

require_once('Interfaces/HasGroup.php');

use ShuntingYard\OperatorRegistry\Interfaces\HasGroup;

class Group implements HasGroup
{
    protected $contents = [];

    public function __construct($contents)
    {
        if (!is_array($contents)) {
            throw new \InvalidArgumentException("Contents must be an array: " . var_export($contents, true) . "<br/>");
        }
        $this->contents = $contents;
    }

    public function getContents(): array
    {
        return $this->contents;
    }

    public function addContents($contents)
    {
        if (!is_array($contents)) {
            throw new \InvalidArgumentException("Contents to add must be an array: " . var_export($contents, true) . "<br/>");
        }
        if (empty($this->contents)) {
            $this->contents = $contents;
        } else {
            $this->contents = array_merge($this->contents, $contents);
        }
    }
}