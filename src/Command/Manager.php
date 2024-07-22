<?php declare(strict_types=1);

namespace Framework\Docker\Command;

use Framework\Cli\Interface\Controller;
use Framework\Cli\Symbol;
use Framework\Helper\Config;
use InvalidArgumentException;
use Framework\Application\Manager as ApplicationManager;
use Exception;

class Manager implements Controller
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

    private $configKeys = [
        'name',
        'httpPort'
    ];

    /**
     * @cli docker:configure
     * @cliDescription Configure docker for the instance (app/etc/docker.php config will be created)
     * @param string|null $key Configuration key
     * @param string|null $value Configuration value
     * @return void
     * @throws Exception
     */
    public function configure(?string $key = null, ?string $value = null): void
    {
        if (!$key) {
            $this->interactiveConfiguration();
            return;
        }

        if (!in_array($key, $this->configKeys)) {
            throw new InvalidArgumentException("Invalid configuration key: {$key}; Possible configuration keys:" . implode(", ", $this->configKeys));
        }

        $initialValue = null;
        if (!$value && $initialValue = $this->dockerConfig->get($key)) {
            echo "Current value {$key}: {$initialValue}\n";
            return;
        }

        if (!$value) {
            $value = $initialValue;
        }

        $this->dockerConfig->set($key, $value);
        $this->dockerConfig->save();
    }

    /**
     * @return void
     * @throws Exception
     */
    private function interactiveConfiguration(): void
    {
        echo Symbol::COLOR_BRIGHT_BLUE . "Configuration...\n" . Symbol::COLOR_RESET;
        echo "Project name (framework): ";
        $input = trim(fgets(STDIN));
        if (!$input) {
            $input = 'framework';
        }
        echo Symbol::UP_LINE . Symbol::CLEAR_LINE .
            "Project name: " . Symbol::COLOR_GREEN . $input . Symbol::COLOR_RESET . "\n";
        $this->dockerConfig->set('name', $input);

        echo "Local HTTP port (80): ";
        $input = trim(fgets(STDIN));
        if (!$input) {
            $input = '80';
        }
        echo Symbol::UP_LINE . Symbol::CLEAR_LINE .
            "Local HTTP port: " . Symbol::COLOR_GREEN . $input . Symbol::COLOR_RESET . "\n";

        $this->dockerConfig->set('port', $input);

        $this->dockerConfig->save();

        echo Symbol::COLOR_GREEN . "Successfully configured\n" . Symbol::COLOR_RESET;
        $this->help();
    }

    /**
     * @cli docker:help
     * @cliDescription Help instruction for docker
     * @return void
     */
    public function help(): void
    {
        $containerName = $this->dockerConfig->get('name') ?? 'framework';

        echo "To build image: " . Symbol::COLOR_GREEN . "docker:build:image\n" . Symbol::COLOR_RESET .
             "To build container: " . Symbol::COLOR_GREEN . "docker:build\n" . Symbol::COLOR_RESET .
             "To run container: " . Symbol::COLOR_GREEN . "php cli.php docker:start\n" . Symbol::COLOR_RESET .
             "To stop the container: " . Symbol::COLOR_GREEN . "php cli.php docker:stop\n" . Symbol::COLOR_RESET .
             "To stop the container: " . Symbol::COLOR_GREEN . "php cli.php docker:setup\n" . Symbol::COLOR_RESET .
             "To enter the container: " . Symbol::COLOR_GREEN . "docker exec -it {$containerName} /bin/bash\n" . Symbol::COLOR_RESET;
    }

    /**
     * @cli docker:build:image
     * @cliDescription Build docker image
     * @param string $noCache
     * @return void
     */
    public function buildImage(string $noCache = ''): void
    {
        echo "Building docker image...\n";

        if ($noCache == 'yes') {
            $noCache = '--no-cache';
        }

        $command = "docker build {$noCache} -t " . static::DOCKER_IMAGE_NAME . " -f {$this->dockerFile} {$this->dockerContext}";
        echo shell_exec($command);
    }

    /**
     * @cli docker:build
     * @cliDescription Build docker container
     * @return void
     */
    public function build(): void
    {
        $name = $this->dockerConfig->get('name') ?? 'framework';
        $port = $this->dockerConfig->get('port') ?? '80';

        echo "Building docker container [{$name}]...\n";

        $command = "docker run -d --name {$name} " .
            "-v " . ApplicationManager::getRootDirectory() . ":/var/www/html/ " .
            "-v {$this->dockerContext}/files/amp/default-host.conf:/etc/apache2/sites-available/000-default.conf " .
            "-v {$this->dockerContext}/files/amp/xdebug.ini:/etc/php/8.3/mods-available/xdebug.ini " .
            "-v {$this->dockerContext}/files/amp/php.ini:/etc/php/8.3/apache2/php.ini " .
            "--add-host host.docker.internal:host-gateway " .
            "-p {$port}:80 " . static::DOCKER_IMAGE_NAME;

        echo shell_exec($command);
    }

    /**
     * @cli docker:start
     * @cliDescription Start docker container
     * @return void
     */
    public function start(): void
    {
        $name = $this->dockerConfig->get('name') ?? 'framework';

        echo "Starting docker container [{$name}]...\n";
        echo shell_exec("docker start {$name}");
        echo "To enter into container: docker exec -it {$this->dockerConfig->get('name')} /bin/bash\n";

    }

    /**
     * @cli docker:stop
     * @cliDescription Stop docker container
     * @return void
     */
    public function stop(): void
    {
        $name = $this->dockerConfig->get('name') ?? 'framework';

        echo "Stopping docker container [{$name}]...\n";
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

    /**
     * @cli docker:command
     * @cliDescription Run command in docker container
     * @return void
     */
    public function command(): void
    {
        $name = $this->dockerConfig->get('name') ?? 'framework';
        $args = func_get_args();
        if (!$args) {
            throw new InvalidArgumentException("Command is required");
        }

        $escapedArgs = array_map('escapeshellarg', $args);
        $command = "docker exec -it {$name}  /bin/bash -c \"" . implode(" ", $escapedArgs) . "\"";
        echo shell_exec($command);
    }

    /**
     * @cli docker:setup
     * @cliDescription Setup and upgrade the configuration structure and modules inside docker
     *
     * @return void
     * @throws Exception
     */
    public function setupDocker(): void
    {
        $name = $this->dockerConfig->get('name') ?? 'framework';

        $result = shell_exec("docker exec -it {$name} /bin/bash -c \"php /var/www/html/cli.php setup\"");
        echo $result;
    }
}