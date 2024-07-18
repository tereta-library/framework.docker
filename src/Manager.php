<?php declare(strict_types=1);

namespace Framework\Docker;

use Framework\Process\System;
use Framework\Pattern\Factory as PatternFactory;
use Framework\Application\Interface\Installer as InstallerInterface;
use Exception;
use ReflectionException;

/**
 * Class Framework\Docker\Manager
 */
class Manager
{
    private function __construct()
    {
    }

    /**
     * @param string $command
     * @param array $arguments
     * @return void
     * @throws ReflectionException
     */
    public static function command(string $command, array $arguments): void
    {
        switch($command) {
            case('install'):
                (new static)->commandInstall($arguments);
                break;
            default:
                throw new Exception('The command is not found');
        }
    }

    /**
     * @param array $arguments
     * @return void
     */
    private function commandInstall(array $arguments): void
    {
        $rootDir = realpath(dirname(__FILE__) . '/../../../..');

        echo "Creating docker.env file in the \"$rootDir\" directory...\n";
        echo "\033[32m" . "Project name: " . "\033[0m";
        $projectName = trim(fgets(STDIN));

        $content = "COMPOSE_PROJECT_NAME=${projectName}" . PHP_EOL;

        $dockerConfiguration = $rootDir . '/docker.env';
        echo "Set the path to the project root directory ({$dockerConfiguration}): ";
        file_put_contents($dockerConfiguration, $content . PHP_EOL);
    }
}

if (PHP_SAPI === 'cli') {
    $arguments = $_SERVER['argv'];
    $initialFile = array_shift($arguments);
    if (count($arguments) == 0) {
        throw new Exception('The command is not set');
    }

    if (__FILE__ === getcwd() . '/' . $initialFile) {
        require_once __DIR__ . '/../../../autoload.php';

        $command = array_shift($arguments);
        Installer::command($command, $arguments);
    }
}