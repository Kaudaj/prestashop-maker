<?php
/**
 * Copyright since 2019 Kaudaj.
 *
 * NOTICE OF LICENSE
 *
 * This source file is subject to the Academic Free License 3.0 (AFL-3.0)
 * that is bundled with this package in the file LICENSE.md.
 * It is also available through the world-wide-web at this URL:
 * https://opensource.org/licenses/AFL-3.0
 * If you did not receive a copy of the license and are unable to
 * obtain it through the world-wide-web, please send an email
 * to info@kaudaj.com so we can send you a copy immediately.
 *
 * @author    Kaudaj <info@kaudaj.com>
 * @copyright Since 2019 Kaudaj
 * @license   https://opensource.org/licenses/AFL-3.0 Academic Free License 3.0 (AFL-3.0)
 */

declare(strict_types=1);

namespace Kaudaj\PrestaShopMaker\Command;

use Exception;
use Kaudaj\PrestaShopMaker\Exception\WindowsProcessInterruptedException;
use RuntimeException;
use Symfony\Bundle\MakerBundle\Exception\RuntimeCommandException;
use Symfony\Bundle\MakerBundle\Str;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Command\SignalableCommandInterface;
use Symfony\Component\Console\Input\InputArgument;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\Console\Style\SymfonyStyle;
use Symfony\Component\Finder\Finder;
use Symfony\Component\Finder\SplFileInfo;
use Symfony\Component\Process\Exception\ProcessFailedException;
use Symfony\Component\Process\Exception\ProcessSignaledException;
use Symfony\Component\Process\Process;

class MakeToCommand extends Command implements SignalableCommandInterface
{
    protected static $defaultName = 'make-to';
    protected static $defaultDescription = 'Execute make command and move the modified files in a different project.';

    /**
     * @var SymfonyStyle Input/Output operations handler
     */
    private $io;

    /**
     * @var string Paths to store source files backup
     */
    private const BACKUP_PATH = 'backup';

    /**
     * @var string[] Paths to not check for modified files
     */
    private const NO_SOURCE_PATHS = ['backup', 'var', 'vendor'];

    /**
     * @var string PrestaShop Maker project root path
     */
    private $rootPath;

    /**
     * @var string|null Destination module class name if set
     */
    protected $destinationModule;

    /**
     * @var int[] Robocopy success codes
     */
    private const ROBOCOPY_SUCCESS_CODES = [0, 1, 2, 3, 4, 5, 6, 7];

    public function __construct(string $rootPath, ?string $destinationModule)
    {
        $this->rootPath = $rootPath.DIRECTORY_SEPARATOR;
        $this->destinationModule = $destinationModule;

        parent::__construct();
    }

    protected function configure(): void
    {
        $this
            ->addArgument('destination-path', InputArgument::REQUIRED, 'Path of the destination project')
            ->addArgument('make-commands', InputArgument::REQUIRED | InputArgument::IS_ARRAY, 'Make commands to execute')
        ;
    }

    /**
     * {@inheritdoc}
     *
     * @return int[] Subscribed signals
     */
    public function getSubscribedSignals(): array
    {
        if ($this->isWindows()) {
            return [];
        }

        return [\SIGINT];
    }

    public function handleSignal(int $signal): void
    {
    }

    protected function execute(InputInterface $input, OutputInterface $output): int
    {
        $this->io = new SymfonyStyle($input, $output);
        $destinationPath = $input->getArgument('destination-path');
        $makeCommands = $input->getArgument('make-commands');

        if (!is_dir($destinationPath)) {
            $this->io->error("$destinationPath directory doesn't exist");

            return Command::FAILURE;
        }

        $this->backupSourceFiles();

        if ($this->isWindows()) {
            sapi_windows_set_ctrl_handler(function () {
                throw new WindowsProcessInterruptedException();
            });
        }

        try {
            $beforeMakeTime = time();
            sleep(1);

            $this->executeMakeCommands($makeCommands);

            $this->io->newLine();
            $this->io->section("Moving files to $destinationPath");

            $modifiedFiles = $this->getModifiedFiles($beforeMakeTime);
            if (!$modifiedFiles) {
                $this->io->success('No new/modified files to send after make command execution.');

                return Command::SUCCESS;
            }

            $this->replaceNamespaceInFiles($modifiedFiles, $destinationPath);
            $this->sendFiles($modifiedFiles, $destinationPath);

            $fileList = '';
            foreach ($modifiedFiles as $file) {
                $fileList .= '- '.$file->getRelativePathname().PHP_EOL;
            }

            $this->io->success(
                'Generated files have successfully been moved to your project!'.PHP_EOL
                .'List of generated files:'.PHP_EOL
                .$fileList
            );

            return Command::SUCCESS;
        } catch (Exception $exception) {
            $this->io->error(
                "Error processing {$this->getName()}:\u{a0}
                {$exception->getMessage()}
                ({$exception->getFile()}, {$exception->getLine()})"
            );

            return Command::FAILURE;
        } finally {
            $this->recoverSourceFiles();
        }
    }

    private function backupSourceFiles(): void
    {
        if (file_exists($this->rootPath.self::BACKUP_PATH)) {
            $removeBackupCommand = (!$this->isWindows() ? 'rm -rf' : 'rmdir /s /q')
                .' '.self::BACKUP_PATH;
            $this->runProcess($removeBackupCommand, $this->rootPath);
        }

        $this->runProcess('mkdir '.self::BACKUP_PATH, $this->rootPath);

        $isWindows = $this->isWindows();

        foreach ($this->getSourceFiles()->depth('== 0') as $file) {
            $sourcePath = $file->getRelativePathname();

            if ($sourcePath) {
                if ($isWindows) {
                    if (is_dir($sourcePath)) {
                        $backupCommand = "robocopy /E $sourcePath ".self::BACKUP_PATH.DIRECTORY_SEPARATOR.$sourcePath;
                    } else {
                        $backupCommand = 'robocopy . '.self::BACKUP_PATH." $sourcePath";
                    }

                    $this->runProcess($backupCommand, $this->rootPath, self::ROBOCOPY_SUCCESS_CODES);
                } else {
                    $backupCommand = 'cp -R '.$sourcePath.' '.self::BACKUP_PATH;

                    $this->runProcess($backupCommand, $this->rootPath);
                }
            }
        }
    }

    /**
     * @param string[] $makeCommands Make command to execute
     */
    private function executeMakeCommands(array $makeCommands): void
    {
        foreach ($makeCommands as $makeCommand) {
            $this->io->newLine();
            $this->io->section("Execution of $makeCommand");

            $command = "php bin/console $makeCommand";

            try {
                if (!$this->isWindows()) {
                    $this->runProcess($command, $this->rootPath, [], true, null);

                    continue;
                }

                $process = proc_open($command, [], $pipes, $this->rootPath);
                if (!is_resource($process)) {
                    continue;
                }

                $returnCode = proc_close($process);
                if ($returnCode) {
                    // TODO: Find a way to display error for Windows users
                    throw new RuntimeException('An error has occured.');
                }
            } catch (Exception $exception) {
                if (($exception instanceof ProcessSignaledException && SIGINT === $exception->getSignal())
                    || $exception instanceof WindowsProcessInterruptedException
                ) {
                    throw new RuntimeException('Execution aborted by user.');
                }

                $this->io->error("$makeCommand failed: {$exception->getMessage()}");
                continue;
            }
        }
    }

    /**
     * @return SplFileInfo[]
     */
    private function getModifiedFiles(int $beforeMakeTime): array
    {
        $modifiedFiles = [];

        foreach ($this->getSourceFiles()->files() as $sourceFile) {
            $backupFilePath = $this->rootPath.self::BACKUP_PATH.DIRECTORY_SEPARATOR
                .str_replace($this->rootPath, '', $sourceFile->getPathname());

            if (!file_exists($backupFilePath) || $sourceFile->getMTime() > $beforeMakeTime) {
                $modifiedFiles[] = $sourceFile;
            }
        }

        return $modifiedFiles;
    }

    /**
     * @param SplFileInfo[] $files Files to replace the content
     */
    private function replaceNamespaceInFiles(array $files, string $destinationPath): void
    {
        $destinationJsonPath = getcwd().DIRECTORY_SEPARATOR.$destinationPath.DIRECTORY_SEPARATOR.'composer.json';
        if (!file_exists($destinationJsonPath)) {
            return;
        }

        $sourceNS = $this->getNamespaces($this->rootPath.'composer.json');
        $destNS = $this->getNamespaces($destinationJsonPath);

        $replacePairs = [];

        if ($sourceNS['autoload'] && $destNS['autoload']) {
            $replacePairs[$sourceNS['autoload']] = $destNS['autoload'];

            $sourceServicePrefix = $this->formatToServicePrefix($sourceNS['autoload']);
            $destServicePrefix = $this->formatToServicePrefix($destNS['autoload']);
            $replacePairs[$sourceServicePrefix] = $destServicePrefix;
        }

        if ($sourceNS['autoload-dev'] && $destNS['autoload-dev']) {
            $replacePairs[$sourceNS['autoload-dev']] = $destNS['autoload-dev'];

            $sourceServicePrefix = $this->formatToServicePrefix($sourceNS['autoload-dev']);
            $destServicePrefix = $this->formatToServicePrefix($destNS['autoload-dev']);
            $replacePairs[$sourceServicePrefix] = $destServicePrefix;
        }

        foreach ($files as $file) {
            $currentContent = file_get_contents($file->getPathname());

            if ($currentContent) {
                $newContent = strtr($currentContent, $replacePairs);
                file_put_contents($file->getPathname(), $newContent);
            }
        }
    }

    /**
     * Get namespace and dev namespace from composer json project file.
     *
     * @return array{autoload: string|null, autoload-dev: string|null}
     */
    private function getNamespaces(string $composerJsonPathname): array
    {
        $namespaces = [
            'autoload' => null,
            'autoload-dev' => null,
        ];

        $fileContent = file_get_contents($composerJsonPathname);
        if ($fileContent) {
            $composerJson = json_decode($fileContent, true);
        }

        if (isset($composerJson['autoload']['psr-4'])) {
            $namespaces['autoload'] = key($composerJson['autoload']['psr-4']);
        }

        if (isset($composerJson['autoload-dev']['psr-4'])) {
            $namespaces['autoload-dev'] = key($composerJson['autoload-dev']['psr-4']);
        }

        return $namespaces;
    }

    private function formatToServicePrefix(string $namespace): string
    {
        $parts = explode('\\', $namespace);
        $parts = str_replace('PrestaShop', 'Prestashop', $parts);
        $servicePrefix = implode('.', array_map([Str::class, 'asSnakeCase'], $parts));

        return $servicePrefix;
    }

    /**
     * @param SplFileInfo[] $files Files to send
     */
    private function sendFiles(array $files, string $destinationPath): void
    {
        $this->io->progressStart(count($files));

        if ($this->destinationModule) {
            $destinationPath .= '/modules/'.strtolower($this->destinationModule);

            if (!file_exists($destinationPath)) {
                throw new RuntimeCommandException("Destination module doesn't exist.");
            }
        }

        foreach ($files as $file) {
            $destFilePath = $destinationPath.DIRECTORY_SEPARATOR.str_replace($this->rootPath, '', $file->getPath());
            $destFilename = $file->getFilename();

            if (file_exists($destFilePath.DIRECTORY_SEPARATOR.$destFilename)) {
                $fileBasename = $file->getBasename('.'.$file->getExtension());
                $destFilename = str_replace($fileBasename, $fileBasename.'-from-ps-maker', $destFilename);
            }

            if (!is_dir($destFilePath)) {
                if (!$this->isWindows()) {
                    $createParentDirsCommand = "mkdir --parents -m=770 $destFilePath";
                    $this->runProcess($createParentDirsCommand);
                } else {
                    $createParentDirsCommand = 'mkdir '.str_replace('/', '\\', $destFilePath);
                    $this->runProcess($createParentDirsCommand);
                }
            }

            if (!$this->isWindows()) {
                $copyFileCommand = "cp {$file->getPathname()} $destFilePath".DIRECTORY_SEPARATOR.$destFilename;
                $this->runProcess($copyFileCommand);
            } else {
                $copyFileCommand = "copy /y {$file->getPathname()} ".str_replace('/', '\\', $destFilePath.DIRECTORY_SEPARATOR.$destFilename);
                $this->runProcess($copyFileCommand);
            }

            $this->io->progressAdvance();
        }

        $this->io->progressFinish();
    }

    private function recoverSourceFiles(): void
    {
        foreach ($this->getSourceFiles()->depth('== 0') as $sourceFile) {
            $sourcePath = $sourceFile->getRelativePathname();

            if (!$this->isWindows()) {
                $removeSourceFilesCommand = "rm -rf $sourcePath";
            } else {
                if (is_dir($this->rootPath.$sourcePath)) {
                    $removeSourceFilesCommand = "rmdir /s /q $sourcePath";
                } else {
                    $removeSourceFilesCommand = "del /q $sourcePath";
                }
            }

            $this->runProcess($removeSourceFilesCommand, $this->rootPath);
        }

        if (!$this->isWindows()) {
            $recoverCommand = 'cp -R '.self::BACKUP_PATH.DIRECTORY_SEPARATOR.'* .';
            $this->runProcess($recoverCommand, $this->rootPath);
        } else {
            $recoverCommand = 'robocopy /E '.self::BACKUP_PATH.' .';
            $this->runProcess($recoverCommand, $this->rootPath, self::ROBOCOPY_SUCCESS_CODES);
        }

        $removeBackupCommand = (!$this->isWindows() ? 'rm -rf' : 'rmdir /s /q')
            .' '.self::BACKUP_PATH;
        $this->runProcess($removeBackupCommand, $this->rootPath);
    }

    /**
     * @param string         $command      Command to execute
     * @param string|null    $workingDir   Directory where command will be executed
     * @param int[]          $successCodes Codes for which the process is successful
     * @param bool           $tty          Interactive mode for Unix
     * @param int|float|null $timeout      Timeout
     *
     * @throws ProcessFailedException
     */
    private function runProcess($command, $workingDir = null, $successCodes = [], $tty = false, $timeout = 60): void
    {
        $process = Process::fromShellCommandline($command, $workingDir, null, null, $timeout);
        $process->setTty($tty);

        $process->run();

        if (in_array($process->getExitCode(), $successCodes)) {
            return;
        }

        if (!$process->isSuccessful()) {
            throw new ProcessFailedException($process);
        }
    }

    /**
     * Check if current operating system is Windows.
     */
    private function isWindows(): bool
    {
        return 'WIN' === strtoupper(substr(PHP_OS, 0, 3));
    }

    private function getSourceFiles(): Finder
    {
        return (new Finder())
            ->in($this->rootPath)
            ->exclude(self::NO_SOURCE_PATHS)
        ;
    }
}
