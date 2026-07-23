<?php

declare(strict_types=1);

namespace Dotdigitalgroup\Email\Test\Unit\Traits;

trait MockBuilderCompatibilityTrait
{
    private const GENERATED_CLASS_DIR = 'dotdigital-phpunit12-compat';

    /**
     * Create a generated abstract type that declares missing methods so PHPUnit 12 can mock them.
     */
    protected function classWithAddedMethods(string $className, array $methods): string
    {
        $className = ltrim($className, '\\');
        $missingMethods = array_values(array_filter($methods, static function (string $method) use ($className): bool {
            return !method_exists($className, $method);
        }));

        if (!$missingMethods) {
            return '\\' . $className;
        }

        sort($missingMethods);
        $cacheKey = sha1($className . '|' . implode('|', $missingMethods));
        $namespace = 'Dotdigitalgroup\\Email\\Test\\Unit\\Traits\\Generated';
        $shortName = 'Compat_' . $cacheKey;
        $fqcn = $namespace . '\\' . $shortName;

        if (!class_exists($fqcn, false)) {
            $methodDefinitions = implode("\n", array_map(static function (string $method): string {
                if (!preg_match('/^[A-Za-z_][A-Za-z0-9_]*$/', $method)) {
                    throw new \InvalidArgumentException(sprintf('Invalid method name "%s"', $method));
                }

                return sprintf('public function %s(...$args) { return null; }', $method);
            }, $missingMethods));

            $typeKeyword = interface_exists($className) ? 'implements' : 'extends';
            $this->loadGeneratedCompatibilityClass(
                $namespace,
                $shortName,
                $typeKeyword,
                $className,
                $methodDefinitions
            );
        }

        return '\\' . $fqcn;
    }

    /**
     * Writes the generated compatibility type to a temporary file and loads it.
     */
    private function loadGeneratedCompatibilityClass(
        string $namespace,
        string $shortName,
        string $typeKeyword,
        string $className,
        string $methodDefinitions
    ): void {
        $dir = rtrim(sys_get_temp_dir(), DIRECTORY_SEPARATOR) . DIRECTORY_SEPARATOR . self::GENERATED_CLASS_DIR;
        if (!is_dir($dir) && !mkdir($dir, 0700, true) && !is_dir($dir)) {
            throw new \RuntimeException(sprintf('Unable to create compatibility class directory "%s"', $dir));
        }

        $file = $dir . DIRECTORY_SEPARATOR . $shortName . '.php';
        if (!file_exists($file)) {
            $classCode = sprintf(
                "<?php\nnamespace %s;\nabstract class %s %s \\%s\n{\n%s\n}\n",
                $namespace,
                $shortName,
                $typeKeyword,
                $className,
                $this->indentMethodDefinitions($methodDefinitions)
            );

            if (file_put_contents($file, $classCode, LOCK_EX) === false) {
                throw new \RuntimeException(sprintf('Unable to write compatibility class file "%s"', $file));
            }
        }

        require_once $file;
    }

    private function indentMethodDefinitions(string $methodDefinitions): string
    {
        return preg_replace('/^/m', '    ', trim($methodDefinitions)) ?: '';
    }
}
