<?php declare(strict_types=1);

namespace Framework\Docker\Command;

use Framework\Cli\Abstract\Command;
use Framework\Helper\Config;
use InvalidArgumentException;
use Framework\Application\Manager as ApplicationManager;
use Exception;

class Run extends Command
{
    const DOCKER_IMAGE_NAME = 'framework.docker:latest';

    /**
     * @var string $dockerLibraryDirectory
     */
    private string $dockerLibraryDirectory;

    /**
     * @var string $dockerFile
     */
    private string $dockerFile;

    /**
     * @var string $dockerContext
     */
    private string $dockerContext;

    /**
     * @var Config|null $dockerConfig
     */
    private ?Config $dockerConfig;

    /**
     * Run constructor.
     */
    public function __construct()
    {
        $this->dockerLibraryDirectory = realpath(__DIR__ . '/..');
        $this->dockerFile = $this->dockerLibraryDirectory . '/config/amp.Dockerfile';
        $this->dockerContext = $this->dockerLibraryDirectory . '/config';

        $this->dockerConfig = (new Config('php'))->load(ApplicationManager::getRootDirectory() . '/app/etc/docker.php');
    }

    /**
     * @cli docker:configure
     * @cliDescription Configure docker for the instance (app/etc/docker.php config will be created)
     * @param string $name Name it is name of docker container
     * @return void
     * @throws Exception
     */
    public function configure(string $name): void
    {
        if (!$name) {
            throw new InvalidArgumentException('Missing required arguments: projectName should be provided.');
        }

        $this->dockerConfig->set('name', $name);
        $this->dockerConfig->save();
    }

    /**
     * @cli docker:build:image
     * @cliDescription Build docker image
     * @return void
     */
    public function buildImage(): void
    {
        echo "Building docker image...\n";

        $command = "docker build -t " . static::DOCKER_IMAGE_NAME . " -f {$this->dockerFile} {$this->dockerContext}";
        //echo $command . "\n";
        echo shell_exec($command);
    }

    /**
     * @cli docker:build
     * @cliDescription Build docker container
     * @return void
     */
    public function build(): void
    {
        echo "Building docker container...\n";

        $name = $this->dockerConfig->get('name');
        if (!$name) {
            throw new InvalidArgumentException('Missing required arguments: projectName should be provided. Run php cli.php docker:configure');
        }

        $command = "docker run -d --name {$name} " .
            "-v " . ApplicationManager::getRootDirectory() . ":/var/www/html/ " .
            "-v {$this->dockerContext}/files/amp/default-host.conf:/etc/apache2/sites-available/000-default.conf " .
            "-v {$this->dockerContext}/files/amp/xdebug.ini:/etc/php/8.3/mods-available/xdebug.ini " .
            "-v {$this->dockerContext}/files/amp/php.ini:/etc/php/8.3/apache2/php.ini " .
            "--add-host host.docker.internal:host-gateway " .
            "-p 80:80 " . static::DOCKER_IMAGE_NAME;

        //echo $command . "\n";
        echo shell_exec($command);
    }

    /**
     * @cli docker:start
     * @cliDescription Start docker container
     * @return void
     */
    public function start(): void
    {
        echo "Starting docker container...\n";

        $name = $this->dockerConfig->get('name');
        if (!$name) {
            throw new InvalidArgumentException('Missing required arguments: projectName should be provided. Run php cli.php docker:configure');
        }

        echo shell_exec("docker start {$name}");
    }

    /**
     * @cli docker:stop
     * @cliDescription Stop docker container
     * @return void
     */
    public function stop(): void
    {
        echo "Stopping docker container...\n";

        $name = $this->dockerConfig->get('name');
        if (!$name) {
            throw new InvalidArgumentException('Missing required arguments: projectName should be provided. Run php cli.php docker:configure');
        }

        echo shell_exec("docker stop {$name}");
    }

    /**
     * @cli docker:stopAll
     * @cliDescription Stop all docker container
     * @return void
     */
    public function stopAll(): void
    {
        echo "Stopping all docker containers...\n";

        echo shell_exec("docker stop $(docker ps -a -q)");
    }
}