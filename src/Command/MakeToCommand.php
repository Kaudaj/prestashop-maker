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

namespace Kaudaj\PrestaShopMaker\Command;

use Exception;
use RuntimeException;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputArgument;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\Console\Style\SymfonyStyle;
use Symfony\Component\Finder\Finder;
use Symfony\Component\Finder\SplFileInfo;
use Symfony\Component\Process\Exception\ProcessFailedException;
use Symfony\Component\Process\Process;

class MakeToCommand extends Command
{
    protected static $defaultName = 'make-to';
    protected static $defaultDescription = 'Execute make command and move the modified files in a different project.';

    /**
     * @var SymfonyStyle Input/Output operations handler
     */
    private $io;

    /**
     * @var string Paths to check for modified files
     */
    private const BACKUP_PATH = 'backup';

    /**
     * @var string[] Paths to check for modified files
     */
    private const SOURCE_PATHS = ['config', 'src', 'templates', 'tests', 'docker-compose.yml'];

    /**
     * @var string PrestaShop Maker project root path
     */
    private $rootPath;

    /**
     * @var int[] Robocopy success codes
     */
    private const ROBOCOPY_SUCCESS_CODES = [0, 1, 2, 3, 4, 5, 6, 7];

    /**
     * @param string $rootPath
     */
    public function __construct($rootPath)
    {
        $this->rootPath = $rootPath.DIRECTORY_SEPARATOR;

        parent::__construct();
    }

    protected function configure(): void
    {
        $this
            ->addArgument('destination-path', InputArgument::REQUIRED, 'Path of the destination project')
            ->addArgument('make-command', InputArgument::REQUIRED, 'Make command to execute')
        ;
    }

    protected function execute(InputInterface $input, OutputInterface $output): int
    {
        $this->io = new SymfonyStyle($input, $output);
        $destinationPath = $input->getArgument('destination-path');
        $makeCommand = $input->getArgument('make-command');

        if (!is_dir($destinationPath)) {
            $this->io->error("$destinationPath directory doesn't exist");

            return Command::FAILURE;
        }

        $this->backupSourceFiles();

        try {
            $this->io->section("Execution of $makeCommand");

            $beforeMakeTime = time();
            $this->executeMakeCommand($makeCommand);

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

    /**
     * @return void
     */
    private function backupSourceFiles()
    {
        if (file_exists($this->rootPath.self::BACKUP_PATH)) {
            $removeBackupCommand = (!$this->isWindows() ? 'rm -rf' : 'rmdir /s /q')
                .' '.self::BACKUP_PATH;
            $this->runProcess($removeBackupCommand, $this->rootPath);
        }

        $this->runProcess('mkdir '.self::BACKUP_PATH, $this->rootPath);

        if (!$this->isWindows()) {
            $backupCommand = 'cp -R '.implode(' ', self::SOURCE_PATHS).' '.self::BACKUP_PATH;
            $this->runProcess($backupCommand, $this->rootPath);
        } else {
            foreach (self::SOURCE_PATHS as $sourcePath) {
                if (is_dir($this->rootPath.$sourcePath)) {
                    $backupCommand = "robocopy /E $sourcePath ".self::BACKUP_PATH.DIRECTORY_SEPARATOR.$sourcePath;
                } else {
                    $backupCommand = 'robocopy . '.self::BACKUP_PATH." $sourcePath";
                }

                $this->runProcess($backupCommand, $this->rootPath, self::ROBOCOPY_SUCCESS_CODES);
            }
        }
    }

    /**
     * @param string $makeCommand Make command to execute
     *
     * @return void
     */
    private function executeMakeCommand($makeCommand)
    {
        /*$makeCommand = preg_split('')

        $command = $this->getApplication()->find('demo:greet');

        $arguments = [
            'name'    => 'Fabien',
            '--yell'  => true,
        ];

        $greetInput = new ArrayInput($arguments);
        $returnCode = $command->run($greetInput, $output);*/

        $process = proc_open("php {$this->rootPath}bin/console $makeCommand", [STDIN, STDOUT, STDERR], $pipes);
        if (is_resource($process)) {
            $returnCode = proc_close($process);

            if ($returnCode) {
                throw new RuntimeException('Make command failed.');
            }
        }
    }

    /**
     * @param int $beforeMakeTime Timestamp before make command execution
     *
     * @return SplFileInfo[]
     */
    private function getModifiedFiles($beforeMakeTime)
    {
        $sourcePaths = array_map(
            function ($path) {
                return '/^'.preg_quote($path).'/';
            },
            self::SOURCE_PATHS
        );

        $sourceFiles = (new Finder())
            ->in($this->rootPath)
            ->path($sourcePaths)
            ->files()
        ;

        $modifiedFiles = [];

        foreach ($sourceFiles as $sourceFile) {
            $backupFilePath = $this->rootPath.self::BACKUP_PATH
                .DIRECTORY_SEPARATOR.str_replace($this->rootPath, '', $sourceFile->getPathname());

            if (!file_exists($backupFilePath)
                || $sourceFile->getMTime() > $beforeMakeTime
            ) {
                $modifiedFiles[] = $sourceFile;
            }
        }

        return $modifiedFiles;
    }

    /**
     * @param SplFileInfo[] $files           Files to replace the content
     * @param string        $destinationPath Destination project path
     *
     * @return void
     */
    private function replaceNamespaceInFiles($files, $destinationPath)
    {
        if (!file_exists($destinationPath.'composer.json')) {
            return;
        }

        $sourceNS = $this->getNamespaces($this->rootPath.'composer.json');
        $destNS = $this->getNamespaces($destinationPath.'composer.json');

        $replacePairs = [];

        if ($sourceNS['autoload'] && $destNS['autoload']) {
            $replacePairs[$sourceNS['autoload']] = $destNS['autoload'];
        }

        if ($sourceNS['autoload-dev'] && $destNS['autoload-dev']) {
            $replacePairs[$sourceNS['autoload-dev']] = $destNS['autoload-dev'];
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
     * @param string $composerJsonFile Composer json file path
     *
     * @return array{autoload: string|null, autoload-dev: string|null}
     */
    private function getNamespaces($composerJsonFile)
    {
        $namespaces = [
            'autoload' => null,
            'autoload-dev' => null,
        ];

        $fileContent = file_get_contents($composerJsonFile);
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

    /**
     * @param SplFileInfo[] $files           Files to send
     * @param string        $destinationPath Destination project path
     *
     * @return void
     */
    private function sendFiles($files, $destinationPath)
    {
        $this->io->progressStart(count($files));

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
                $copyFileCommand = "copy /y {$file->getPathname()} $destFilePath".DIRECTORY_SEPARATOR.$destFilename;
                $this->runProcess($copyFileCommand);
            }

            $this->io->progressAdvance();
        }

        $this->io->progressFinish();
    }

    /**
     * @return void
     */
    private function recoverSourceFiles()
    {
        foreach (self::SOURCE_PATHS as $sourcePath) {
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
     * @param string $command      Command to execute
     * @param string $workingDir   Directory where command will be executed
     * @param int[]  $successCodes Codes for which the process is successful
     *
     * @return void
     *
     * @throws ProcessFailedException
     */
    private function runProcess($command, $workingDir = null, $successCodes = [])
    {
        $process = Process::fromShellCommandline($command);

        if ($workingDir) {
            $process->setWorkingDirectory($workingDir);
        }

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
}
