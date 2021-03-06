<?php

namespace Elsevier\JSONSchemaPHPGenerator\Tests\Properties;

use Elsevier\JSONSchemaPHPGenerator\Properties\Factory;
use Elsevier\JSONSchemaPHPGenerator\Properties\ArrayProperty;
use Elsevier\JSONSchemaPHPGenerator\Properties\BooleanProperty;
use Elsevier\JSONSchemaPHPGenerator\Properties\ConstantProperty;
use Elsevier\JSONSchemaPHPGenerator\Properties\EnumProperty;
use Elsevier\JSONSchemaPHPGenerator\Properties\FloatProperty;
use Elsevier\JSONSchemaPHPGenerator\Properties\InterfaceProperty;
use Elsevier\JSONSchemaPHPGenerator\Properties\ObjectProperty;
use Elsevier\JSONSchemaPHPGenerator\Properties\StringProperty;
use Elsevier\JSONSchemaPHPGenerator\Properties\UntypedProperty;
use Monolog\Handler\NullHandler;
use Monolog\Logger;
use Psr\Log\LoggerInterface;

class FactoryTest extends \PHPUnit\Framework\TestCase
{
    /**
     * @var LoggerInterface
     */
    private $log;

    public function setUp()
    {
        $this->log = new Logger('UnitTestLogger');
        $this->log->pushHandler(new NullHandler());
    }

    public function testWithNoTypeReturnsUntypedProperty()
    {
        $attributes = json_decode('{}');

        $factory = $this->buildFactory();
        $property = $factory->create('FooBar', $attributes, 'Class', 'Example\\Namespace');

        assertThat($property, is(anInstanceOf(UntypedProperty::class)));
    }

    public function testWithInvalidTypeReturnsUntypedProperty()
    {
        $attributes = json_decode('{ "type": "foobar" }');

        $factory = $this->buildFactory();
        $property = $factory->create('FooBar', $attributes, 'Class', 'Example\\Namespace');

        assertThat($property, is(anInstanceOf(UntypedProperty::class)));
    }

    public function testNumberReturnsFloatProperty()
    {
        $attributes = json_decode('{ "type": "number"}');

        $factory = $this->buildFactory();
        $property = $factory->create('FooBar', $attributes, 'Class', 'Example\\Namespace');

        assertThat($property, is(anInstanceOf(FloatProperty::class)));
    }

    public function testStringReturnsStringProperty()
    {
        $attributes = json_decode('{ "type": "string"}');

        $factory = $this->buildFactory();
        $property = $factory->create('FooBar', $attributes, 'Class', 'Example\\Namespace');

        assertThat($property, is(anInstanceOf(StringProperty::class)));
    }

    public function testBooleanReturnsBooleanProperty()
    {
        $attributes = json_decode('{ "type": "boolean"}');

        $factory = $this->buildFactory();
        $property = $factory->create('FooBar', $attributes, 'Class', 'Example\\Namespace');

        assertThat($property, is(anInstanceOf(BooleanProperty::class)));
    }

    public function testSingleValueEnumReturnsConstantProperty()
    {
        $attributes = json_decode(
            '{
                "enum": [
                    "Bar"
                ],
                "type": "string"
            }'
        );

        $factory = $this->buildFactory();
        $property = $factory->create('FooBar', $attributes, 'Class', 'Example\\Namespace');

        assertThat($property, is(anInstanceOf(ConstantProperty::class)));
    }

    public function testMultiValueEnumReturnsConstantProperty()
    {
        $attributes = json_decode(
            '{
                "enum": [
                    "Foo",
                    "Bar"
                ],
                "type": "string"
            }'
        );

        $factory = $this->buildFactory();
        $property = $factory->create('FooBar', $attributes, 'Class', 'Example\\Namespace');

        assertThat($property, is(anInstanceOf(EnumProperty::class)));
    }

    public function testObjectPropertyWithReferenceReturnsObjectProperty()
    {
        $attributes = json_decode('{"$ref": "#/definitions/SubReference"}');

        $factory = $this->buildFactory();
        $property = $factory->create('FooBar', $attributes, 'Class', 'Example\\Namespace');

        assertThat($property, is(anInstanceOf(ObjectProperty::class)));
    }

    public function testArrayProperty()
    {
        $attributes = json_decode('
            {
                "items": {
                    "$ref": "#/definitions/SubReference"
                },
                "type": "array"
            }
        ');

        $factory = $this->buildFactory();
        $property = $factory->create('FooBar', $attributes, 'Class', 'Example\\Namespace');

        assertThat($property, is(anInstanceOf(ArrayProperty::class)));
    }

    public function testInterfaceProperty()
    {
        $attributes = json_decode('
            {
                "anyOf": [
                    {
                        "$ref": "#/definitions/SubReference"
                    },
                    {
                        "$ref": "#/definitions/OtherSubReference"
                    }
                ]
            }
        ');

        $factory = $this->buildFactory();
        $property = $factory->create('FooBar', $attributes, 'Class', 'Example\\Namespace');

        assertThat($property, is(anInstanceOf(InterfaceProperty::class)));
    }

    private function buildFactory()
    {
        return new Factory($this->log);
    }
}
