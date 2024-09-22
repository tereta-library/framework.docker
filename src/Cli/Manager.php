<?php declare(strict_types=1);

namespace Framework\Docker\Cli;

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
     * @var string[]
     */
    private $configKeys = [
        'name',
        'httpPort'
    ];

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
        $this->dockerConfig->set('http.port', $input);

        echo "Local HTTPS port (443): ";
        $input = trim(fgets(STDIN));
        if (!$input) {
            $input = '443';
        }
        echo Symbol::UP_LINE . Symbol::CLEAR_LINE .
            "Local HTTPS port: " . Symbol::COLOR_GREEN . $input . Symbol::COLOR_RESET . "\n";
        $this->dockerConfig->set('https.port', $input);

        echo "Will you use local MySQL server (Yes): ";
        $input = trim(fgets(STDIN));
        if (!$input) {
            $input = 'Yes';
        }
        echo Symbol::UP_LINE . Symbol::CLEAR_LINE .
            "Will you use local MySQL server : " . Symbol::COLOR_GREEN . $input . Symbol::COLOR_RESET . "\n";

        $this->dockerConfig->set('mysql', null);

        if ($input == 'Yes') {
            $this->interactiveConfigurationMysql();
        }

        $this->dockerConfig->save();

        echo Symbol::COLOR_GREEN . "Successfully configured\n" . Symbol::COLOR_RESET . "\n";
        $this->help();
    }

    /**
     * @return void
     */
    private function interactiveConfigurationMysql(): void
    {
        echo "Local MySQL port (3306): ";
        $input = trim(fgets(STDIN));
        if (!$input) {
            $input = '3306';
        }
        echo Symbol::UP_LINE . Symbol::CLEAR_LINE .
            "Local MySQL port: " . Symbol::COLOR_GREEN . $input . Symbol::COLOR_RESET . "\n";
        $this->dockerConfig->set('mysql.port', $input);

        echo "Local MySQL user (developer): ";
        $input = trim(fgets(STDIN));
        if (!$input) {
            $input = 'developer';
        }
        echo Symbol::UP_LINE . Symbol::CLEAR_LINE .
            "Local MySQL user (developer): " . Symbol::COLOR_GREEN . $input . Symbol::COLOR_RESET . "\n";
        $this->dockerConfig->set('mysql.user', $input);

        echo "Local MySQL password (developer): ";
        $input = trim(fgets(STDIN));
        if (!$input) {
            $input = 'developer';
        }
        echo Symbol::UP_LINE . Symbol::CLEAR_LINE .
            "Local MySQL password: " . Symbol::COLOR_GREEN . $input . Symbol::COLOR_RESET . "\n";
        $this->dockerConfig->set('mysql.password', $input);

        echo "Local MySQL database (developer): ";
        $input = trim(fgets(STDIN));
        if (!$input) {
            $input = 'developer';
        }
        echo Symbol::UP_LINE . Symbol::CLEAR_LINE .
            "Local MySQL database: " . Symbol::COLOR_GREEN . $input . Symbol::COLOR_RESET . "\n";
        $this->dockerConfig->set('mysql.database', $input);
    }

    /**
     * @cli docker:help
     * @cliDescription Help instruction for docker
     * @return void
     */
    public function help(): void
    {
        $containerName = $this->dockerConfig->get('name') ?? 'framework';

        echo "To use interactive configure docker: " . Symbol::COLOR_GREEN . "php cli.php docker:configure\n" . Symbol::COLOR_RESET .
             "To configure docker options: " . Symbol::COLOR_GREEN . "php cli.php docker:configure [key] [value]\n" . Symbol::COLOR_RESET .
             "To build image: " . Symbol::COLOR_GREEN . "docker:build:image\n" . Symbol::COLOR_RESET .
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

        if ($noCache == 'update') {
            $noCache = '--no-cache';
        }

        $command = "docker build {$noCache} -t " . static::DOCKER_IMAGE_NAME . " -f {$this->dockerFile} {$this->dockerContext}";
        echo shell_exec($command);
    }

    /**
     * @cli docker:build
     * @cliDescription Build docker container
     * @return void
     * @throws Exception
     */
    public function build(): void
    {
        $name = $this->dockerConfig->get('name') ?? 'framework';
        $httpPort = $this->dockerConfig->get('http.port') ?? '80';
        $httpsPort = $this->dockerConfig->get('https.port') ?? '433';
        $mysqlPort = $this->dockerConfig->get('mysql.port') ?? '3306';

        echo "Building docker container [{$name}]...\n";

        $command = "docker run -d --name {$name} " .
            "-v " . ApplicationManager::getRootDirectory() . ":/var/www/html/ " .
            "-v {$this->dockerContext}/files/amp/default-host.conf:/etc/apache2/sites-available/000-default.conf " .
            "-v {$this->dockerContext}/files/amp/xdebug.ini:/etc/php/8.3/mods-available/xdebug.ini " .
            "-v {$this->dockerContext}/files/amp/php.ini:/etc/php/8.3/apache2/php.ini " .
            "--add-host host.docker.internal:host-gateway " .
            "-p {$httpPort}:80 " .
            "-p {$httpsPort}:443 " .
            "-p {$mysqlPort}:3306 " .
            static::DOCKER_IMAGE_NAME;

        echo shell_exec($command);

        $this->buildConfigureDatabase();
    }

    /**
     * @return void
     * @throws Exception
     */
    private function buildConfigureDatabase(): void
    {
        if ($this->dockerConfig->get('mysql') === null) {
            return;
        }

        echo "Configuring database...\n";
        $name = $this->dockerConfig->get('name') ?? 'framework';
        $mysqlUser = $this->dockerConfig->get('mysql.user') ?? 'developer';
        $mysqlPassword = $this->dockerConfig->get('mysql.password') ?? 'developer';
        $mysqlDatabase = $this->dockerConfig->get('mysql.database') ?? 'developer';

        echo "\n";
        $counter = 0;
        $symbols = ['|', '/', '-', '\\'];
        $symbols = array_merge($symbols, $symbols, $symbols);
        while (true) {
            echo Symbol::UP_LINE . Symbol::CLEAR_LINE .
                "Checking if MySQL started: " . Symbol::COLOR_GREEN . $symbols[$counter] . Symbol::COLOR_RESET . "\n";
            usleep(3000 * 30);
            if ($counter >= 9) throw new Exception("MySQL server is not started");
            $counter++;
            $command = "docker exec -it {$name} /bin/bash -c \"mysql -e 'SELECT NOW();'\"";
            $result = shell_exec($command);
            if (!str_starts_with((string) $result, 'ERROR 2002 (HY000): ')) {
                break;
            }
        }
        echo Symbol::UP_LINE . Symbol::CLEAR_LINE;

        echo "Creating database...\n";
        $command = "docker exec -it {$name} /bin/bash -c \"mysql -e 'CREATE DATABASE IF NOT EXISTS {$mysqlDatabase};'\"";
        echo shell_exec($command);

        echo "Creating database user...\n";
        $command = "docker exec -it {$name} /bin/bash -c \"mysql -e \\\"CREATE USER IF NOT EXISTS '{$mysqlUser}'@'%' IDENTIFIED BY '{$mysqlPassword}'\\\"\"";
        echo shell_exec($command);

        echo "Creating database privileges...\n";
        $command = "docker exec -it {$name} /bin/bash -c \"mysql -e \\\"GRANT ALL PRIVILEGES ON {$mysqlDatabase}.* TO '{$mysqlUser}'@'%' WITH GRANT OPTION\\\"\"";
        echo shell_exec($command);
    }

    /**
     * @cli docker
     * @cliDescription Start docker container
     * @return void
     * @throws Exception
     */
    public function docker(): void
    {
        $this->start();
    }

    /**
     * @cli docker:start
     * @cliDescription Start docker container
     * @return void
     * @throws Exception
     */
    public function start(): void
    {
        $name = $this->dockerConfig->get('name') ?? 'framework';

        echo "Starting docker container [{$name}]...\n";
        $result = shell_exec("docker start {$name} 2>&1");
        if (preg_match('/Cannot connect to the Docker daemon at .* Is the docker daemon running\?/Usi', $result)) {
            throw new Exception("Docker daemon is not running. Please start docker daemon first.");
        }

        echo Symbol::COLOR_GREEN . "Docker container {$name} started. To enter into the container: docker exec -it {$this->dockerConfig->get('name')} /bin/bash\n" . Symbol::COLOR_RESET;
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
        shell_exec("docker stop {$name}");
        echo Symbol::COLOR_GREEN . "Docker container {$name} stopped.\n" . Symbol::COLOR_RESET;
    }

    /**
     * @cli docker:stopAll
     * @cliDescription Stop all docker container
     * @return void
     */
    public function stopAll(): void
    {
        echo "Stopping all docker containers...\n";
        shell_exec("docker stop $(docker ps -a -q)");
        echo Symbol::COLOR_GREEN . "All docker containers has been stopped.\n" . Symbol::COLOR_RESET;
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