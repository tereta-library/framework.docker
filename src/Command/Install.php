<?php declare(strict_types=1);

namespace Tereta\Docker\Command;

use Framework\Cli\Abstract\Command;

/**
 * @class Tereta\Docker\Command\Install
 */
class Install extends Command
{
    /**
     * @var array|string[]
     */
    protected array $arguments = ['name'];

    /**
     * @return void
     */
    public function execute(): void
    {
        $name = $this->getArgument('name');

        if (!$name) {
            throw new \InvalidArgumentException('Missing required arguments');
        }

        $this->buildImage();

        $this->install($name);
    }

    private function buildImage(): void
    {
        echo "Building docker image...\n";
        echo shell_exec('docker build -t framework_amp:latest -f config/amp.Dockerfile .');
    }

    private function runImage(): void
    {
        echo "Running docker image...\n";
        echo shell_exec(
            'docker run -d ' .
                    '--name framework_amp ' .
                    '--env-file ../../../../docker.env ' .
                    '-v $(pwd)/../../../../:/var/www/html/ ' .
                    '-v $(pwd)/config/files/amp/default-host.conf:/etc/apache2/sites-available/000-default.conf ' .
                    '-v $(pwd)/config/files/amp/xdebug.ini:/etc/php/8.3/mods-available/xdebug.ini ' .
            '-v $(pwd)/config/files/amp/php.ini:/etc/php/8.3/apache2/php.ini ' .
            '-p 80:80 ' .
            '--add-host host.docker.internal:host-gateway ' .
            '-p 80:80 framework_amp:latest'
        );
    }

    private function installDEPRECATED(string $path, string $name, string $type): void
    {
        echo "Creating docker.php file in the \"$rootDir\" directory...\n";
        echo "\033[32m" . "Project name: " . "\033[0m";
        $projectName = trim(fgets(STDIN));

        $content = "COMPOSE_PROJECT_NAME=${projectName}" . PHP_EOL;

        $dockerConfiguration = $rootDir . '/docker.env';
        echo "Set the path to the project root directory ({$dockerConfiguration}): ";
        file_put_contents($dockerConfiguration, $content . PHP_EOL);
    }
}