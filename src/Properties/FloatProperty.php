<?php

namespace Elsevier\JSONSchemaPHPGenerator\Properties;

class FloatProperty extends TypedProperty
{
    /**
     * @param string $name
     */
    public function __construct($name)
    {
        parent::__construct($name, 'float');
    }
}
