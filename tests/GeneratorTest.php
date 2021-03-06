<?php

namespace Elsevier\JSONSchemaPHPGenerator\Tests;

use Elsevier\JSONSchemaPHPGenerator\CodeCreator;
use Elsevier\JSONSchemaPHPGenerator\Generator;
use Elsevier\JSONSchemaPHPGenerator\InvalidJsonException;
use Elsevier\JSONSchemaPHPGenerator\InvalidSchemaException;
use League\Flysystem\Adapter\Local;
use League\Flysystem\Filesystem;
use Monolog\Handler\NullHandler;
use Monolog\Logger;
use Psr\Log\LoggerInterface;

class GeneratorTest extends \PHPUnit\Framework\TestCase
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

    public function testEmptySchemaCreatesNoFiles()
    {
        $fileSystem = $this->createFilesystem();
        $generator = $this->buildGenerator($fileSystem);

        $generator->generate('{}');

        assertThat($fileSystem->listContents(), is([]));
    }

    public function testBasicSchemaCreatesOneClassFile()
    {
        $schema = '{
            "properties": {
                "foo": {"type": "number"},
                "bar": {"type": "string"}
            }
        }';
        $fileSystem = $this->createFilesystem();
        $generator = $this->buildGenerator($fileSystem);

        $generator->generate($schema);

        assertThat($fileSystem->has('FooBar.php'), is(true));
    }

    public function testInvalidJsonThrowsException()
    {
        $schema = '{';
        $generator = $this->buildGenerator();

        $this->setExpectedException(InvalidJsonException::class);
        $generator->generate($schema);
    }

    public function testInvalidJsonSchemaThrowsException()
    {
        $schema = '{
            "properties": {
                "Baz": "invalid"
            }
        }';
        $generator = $this->buildGenerator();

        $this->setExpectedException(InvalidSchemaException::class);
        $generator->generate($schema);
    }

    /**
     * @param string $rootDir
     * @return Filesystem
     */
    public function createFilesystem($rootDir = '/tmp/outputDir/')
    {
        $localFiles = new Local($rootDir);
        return new Filesystem($localFiles);
    }

    /**
     * @after
     */
    public function cleanOutOutputDir()
    {
        $fileSystem = $this->createFilesystem();
        $files = $fileSystem->listContents();
        foreach ($files as $file) {
            $fileSystem->delete($file['path']);
        }
    }

    private function buildGenerator($fileSystem = null)
    {
        $codeCreator = new CodeCreator('FooBar', 'Elsevier\JSONSchemaPHPGenerator\Examples', $this->log);
        $fileSystem = isset($fileSystem) ? $fileSystem : $this->createFilesystem();
        $generator = new Generator($fileSystem, $codeCreator, __DIR__ . '/../vendor/justinrainbow/json-schema/dist/schema/json-schema-draft-04.json');
        return $generator;
    }
}
