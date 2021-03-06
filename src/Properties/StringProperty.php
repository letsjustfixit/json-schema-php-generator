<?php

namespace Elsevier\JSONSchemaPHPGenerator\Properties;

use Elsevier\JSONSchemaPHPGenerator\CodeCreator;
use Nette\PhpGenerator\ClassType;
use Nette\PhpGenerator\Method;

class StringProperty extends TypedProperty
{
    /**
     * @var integer|false
     */
    private $minLength;
    /**
     * @var integer|false
     */
    private $maxLength;
    /**
     * @var boolean
     */
    private $hasConstraint;

    /**
     * @param string $name
     * @param integer|false $minLength
     * @param integer|false $maxLength
     */
    public function __construct($name, $minLength = false, $maxLength = false)
    {
        parent::__construct($name, 'string');
        $this->minLength = $minLength;
        $this->maxLength = $maxLength;
        $this->hasConstraint = ($this->minLength !== false || $this->maxLength !== false);
    }

    /**
     * @inheritdoc
     */
    public function addConstructorBody(Method $constructor)
    {
        $constructor->addBody($this->getCodeToAssignValue());
        return $constructor;
    }

    /**
     * @inheritdoc
     */
    public function addExtraMethodsTo(ClassType $class)
    {
        if ($this->minLength !== false) {
            $body = <<<BODY
if (mb_strlen(\$value) < {$this->minLength}) {
    throw new InvalidValueException(\$value . ' is less than the minimum specified length of {$this->minLength}');
}
return \$value;
BODY;
            $class->addMethod('ensureMinimumLength')
                ->setVisibility('private')
                ->addBody($body)
                ->addParameter('value');
        }
        if ($this->maxLength !== false) {
            $body = <<<BODY
if (mb_strlen(\$value) > {$this->maxLength}) {
    throw new InvalidValueException(\$value . ' is more than the maximum specified length of {$this->maxLength}');
}
return \$value;
BODY;
            $class->addMethod('ensureMaximumLength')
                ->setVisibility('private')
                ->addBody($body)
                ->addParameter('value');
        }
        return $class;
    }

    /**
     * @inheritdoc
     */
    public function extraClasses(CodeCreator $code)
    {
        $classes = [];
        if ($this->hasConstraint) {
            $classes['InvalidValueException'] = $code->createException('InvalidValueException');
        }
        return $classes;
    }

    /**
     * @inheritdoc
     */
    public function addSetterTo(ClassType $class)
    {
        if ($this->hasConstraint) {
            $class->addMethod('set' . ucfirst($this->name))
                ->addComment('@param ' . $this->type . ' $' . $this->name)
                ->addComment('@throws InvalidValueException')
                ->addBody($this->getCodeToAssignValue())
                ->addParameter($this->name);
        } else {
            $class->addMethod('set' . ucfirst($this->name))
                ->addComment('@param ' . $this->type . ' $' . $this->name)
                ->addBody($this->getCodeToAssignValue())
                ->addParameter($this->name);
        }
        return $class;
    }

    /**
     * @inheritdoc
     */
    protected function getCodeToAssignValue()
    {
        $value = "(string)\${$this->name}";
        if ($this->hasConstraint) {
            if ($this->minLength !== false) {
                $value = "\$this->ensureMinimumLength($value)";
            }
            if ($this->maxLength !== false) {
                $value = "\$this->ensureMaximumLength($value)";
            }
        }
        return "\$this->$this->name = $value;";
    }

    public function getConstructorException(array $constructorExceptions)
    {
        if ($this->hasConstraint) {
            $constructorExceptions[] = 'InvalidValueException';
        }
        return $constructorExceptions;
    }
}
