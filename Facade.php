<?php declare(strict_types=1);

namespace Framework\Debug;

use ReflectionClass;
use ReflectionException;

/**
 * Class Framework\Debug\Facade
 * @package Framework\Debug
 */
class Facade
{
    /**
     * @param mixed $var
     * @param bool $serialize
     * @return string
     */
    public static function dump(mixed $var, bool $serialize = false): string
    {
        if ($serialize) {
            return serialize($var);
        }

        return var_export($var, true);
    }

    /**
     * @return string
     */
    private static function backTrace(): string
    {
        ob_start();
        debug_print_backtrace();
        return ob_get_clean();
    }

    /**
     * @param string $classMethod
     * @param array $args
     * @return string
     * @throws ReflectionException
     */
    public static function runClassMethod(string $classMethod, array $args = []): string
    {
        $classMethod = explode('::', $classMethod);
        $class = array_shift($classMethod);
        $method = array_shift($classMethod);

        $reflectionClass = new ReflectionClass($class);
        $reflectionMethod = $reflectionClass->getMethod($method);

        foreach ($args as $key => $item) {
            switch(true) {
                case (str_starts_with($item, 'json:')):
                    $args[$key] = json_decode(substr($item, 5), true);
                    break;
                case (str_starts_with($item, 'serialized:')):
                    $args[$key] = unserialize(substr($item, 11), true);
                    break;
                case (str_starts_with($item, 'base64:')):
                    $args[$key] = base64_decode(substr($item, 7), true);
                    break;
            }
        }

        ob_start();
        $return = $reflectionMethod->invokeArgs(new $class, $args);
        $output = ob_get_clean();

        $result = '';
        if ($output) {
            $result .= 'Output:' . $output . PHP_EOL;
        }

        if ($return !== null) {
            $result .= 'Return: ' . $return . PHP_EOL;
        }

        return  $result;
    }
}

// Example:
//     php framework/Debug.php Framework\\Debug::dump "json:[123]"
if (php_sapi_name() === 'cli' && ($_SERVER['PWD'] . '/' . $_SERVER['SCRIPT_FILENAME']) === __FILE__) {
    $arguments = $_SERVER['argv'];
    array_shift($arguments);
    echo Facade::runClassMethod(array_shift($arguments), $arguments);
}