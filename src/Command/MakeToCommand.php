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
    private const BACKUP_PATH = 'backup/';

    /**
     * @var string[] Paths to check for modified files
     */
    private const SOURCE_PATHS = ['config', 'src', 'templates', 'tests'];

    /**
     * @var string PrestaShop Maker project root path
     */
    private $rootPath;

    /**
     * @param string $rootPath
     */
    public function __construct($rootPath)
    {
        $this->rootPath = $rootPath.'/';

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

        $this->backupSourceFiles();

        try {
            $beforeMakeTime = time();

            $this->executeMakeCommand($makeCommand);

            $modifiedFiles = $this->getModifiedFiles($beforeMakeTime);

            if (!$modifiedFiles) {
                $this->io->success('No new/modified files to send after make command execution.');

                return Command::SUCCESS;
            }

            $this->replaceNamespaceInFiles($modifiedFiles, $destinationPath);

            $this->sendFiles($modifiedFiles, $destinationPath);

            $fileList = '';
            foreach ($modifiedFiles as $file) {
                $fileList .= '- '.$file->getFilename().PHP_EOL;
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
            $removeBackupCommand = (!$this->isWindows() ? 'rm -rf' : 'rmdir /s')
                .' '.self::BACKUP_PATH;
            $this->runProcess($removeBackupCommand);
        }

        $this->runProcess('mkdir '.self::BACKUP_PATH);

        $backupCommand = (!$this->isWindows() ? 'cp -R ' : 'robocopy /E ')
            .implode(' ', self::SOURCE_PATHS).' '.self::BACKUP_PATH;
        $this->runProcess($backupCommand);
    }

    /**
     * @param string $makeCommand Make command to execute
     *
     * @return void
     */
    private function executeMakeCommand($makeCommand)
    {
        $this->runProcess('bin/console '.$makeCommand);
    }

    /**
     * @param int $beforeMakeTime Timestamp before make command execution
     *
     * @return SplFileInfo[]
     */
    private function getModifiedFiles($beforeMakeTime)
    {
        $sourcePaths = array_map(
            function ($path) { return $this->rootPath.$path; },
            self::SOURCE_PATHS
        );

        $sourceFiles = (new Finder())
            ->in($sourcePaths)
            ->files()
        ;

        $modifiedFiles = [];

        foreach ($sourceFiles as $sourceFile) {
            $backupFilePath = $this->rootPath.self::BACKUP_PATH
                .str_replace($this->rootPath, '', $sourceFile->getPathname());

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
        foreach ($files as $file) {
            $destDirectoryPath = $destinationPath.str_replace($this->rootPath, '', $file->getPath());
            $destFilePathname = $destDirectoryPath.'/'.$file->getFilename();

            if (file_exists($destFilePathname)) {
                $destFilePathname = $destDirectoryPath.'/ps-maker-'.$file->getFilename();
            }

            $createParentDirsCommand =
                (!$this->isWindows() ? 'mkdir --parents -m=770' : 'mkdir')
                    .' '.$destDirectoryPath
            ;
            $this->runProcess($createParentDirsCommand);

            $copyFileCommand = (!$this->isWindows() ? 'cp' : 'robocopy')
                .' '.$file->getPathname().' '.$destFilePathname;
            $this->runProcess($copyFileCommand);
        }
    }

    /**
     * @return void
     */
    private function recoverSourceFiles()
    {
        foreach (self::SOURCE_PATHS as $path) {
            $removeSourceFilesCommand = (!$this->isWindows() ? 'rm -rf' : 'rmdir /s')
                .' '.$path;
            $this->runProcess($removeSourceFilesCommand);
        }

        $recoverCommand = (!$this->isWindows() ? 'cp -R ' : 'robocopy /E ')
            .self::BACKUP_PATH.'/* .';
        $this->runProcess($recoverCommand);

        $removeBackupCommand = (!$this->isWindows() ? 'rm -rf' : 'rmdir /s')
            .' '.self::BACKUP_PATH;
        $this->runProcess($removeBackupCommand);
    }

    /**
     * @param string $command
     *
     * @return void
     *
     * @throws ProcessFailedException
     */
    private function runProcess($command)
    {
        $process = Process::fromShellCommandline($command);
        $process->setWorkingDirectory($this->rootPath);
        $process->setTty(true);
        $process->run();

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
