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

namespace Kaudaj\PrestaShopMaker\Maker;

use Kaudaj\PrestaShopMaker\Builder\MultiLangEntity\LangEntityBuilder;
use RuntimeException;
use Symfony\Bundle\MakerBundle\ConsoleStyle;
use Symfony\Bundle\MakerBundle\Doctrine\DoctrineHelper;
use Symfony\Bundle\MakerBundle\Doctrine\EntityClassGenerator;
use Symfony\Bundle\MakerBundle\FileManager;
use Symfony\Bundle\MakerBundle\Generator;
use Symfony\Bundle\MakerBundle\InputConfiguration;
use Symfony\Bundle\MakerBundle\Util\ClassSourceManipulator;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Process\Exception\ProcessFailedException;
use Symfony\Component\Process\Process;

final class MakeMultiLangEntity extends EntityBasedMaker
{
    /**
     * @var EntityClassGenerator
     */
    private $entityClassGenerator;

    public function __construct(FileManager $fileManager, DoctrineHelper $entityHelper, EntityClassGenerator $entityClassGenerator)
    {
        parent::__construct($fileManager, $entityHelper);

        $this->entityClassGenerator = $entityClassGenerator;
    }

    public static function getCommandName(): string
    {
        return 'make:prestashop:multi-lang-entity';
    }

    /**
     * @return string[]
     */
    public static function getCommandAliases(): array
    {
        return [
            'make:ps:multi-lang-entity',
            'make:prestashop:lang-entity',
            'make:ps:lang-entity',
        ];
    }

    public static function getCommandDescription(): string
    {
        return 'Creates a multi lang entity';
    }

    public function configureCommand(Command $command, InputConfiguration $inputConf): void
    {
        parent::configureCommand($command, $inputConf);

        $helpFileContents = file_get_contents($this->rootPath.'src/Resources/help/MakeMultiLangEntity.txt');
        if ($helpFileContents) {
            $command->setHelp($helpFileContents);
        }
    }

    public function generate(InputInterface $input, ConsoleStyle $io, Generator $generator): void
    {
        parent::generate($input, $io, $generator);

        $this->buildLangEntity();
        $this->askForNewFields();
    }

    private function buildLangEntity(): void
    {
        $entityClassNameDetails = $this->generator->createClassNameDetails(
            "{$this->entityClassName}",
            'Entity\\'
        );
        $langEntityClassNameDetails = $this->generator->createClassNameDetails(
            "{$this->entityClassName}",
            'Entity\\',
            'Lang'
        );

        $langEntityBuilder = new LangEntityBuilder($entityClassNameDetails);

        $langEntityPathname = $this->entityClassGenerator->generateEntityClass($langEntityClassNameDetails, false);
        $langEntitySourceCode = $this->generator->getFileContentsForPendingOperation($langEntityPathname);
        $langEntitySourceCode = $langEntityBuilder->removeIdProperty($langEntitySourceCode);

        $this->generator->dumpFile($langEntityPathname, $langEntitySourceCode);

        $entityPathname = "{$this->rootPath}src/Entity/{$entityClassNameDetails->getRelativeName()}.php";
        $entitySourceCode = file_get_contents($entityPathname);

        if (!$entitySourceCode) {
            return;
        }

        $entityManipulator = new ClassSourceManipulator($entitySourceCode, true);
        $langEntityManipulator = new ClassSourceManipulator($langEntitySourceCode, true);

        $langEntityBuilder->addEntityRelation($entityManipulator, $langEntityManipulator);
        $langEntityBuilder->addLangRelation($langEntityManipulator);

        $this->generator->dumpFile($entityPathname, $entityManipulator->getSourceCode());
        $this->generator->dumpFile($langEntityPathname, $langEntityManipulator->getSourceCode());

        $this->generator->writeChanges();
    }

    private function askForNewFields(): void
    {
        $command = "php bin/console make:entity {$this->entityClassName}Lang";
        $isWindows = 'WIN' !== strtoupper(substr(PHP_OS, 0, 3));

        if (!$isWindows) {
            $process = Process::fromShellCommandline($command, $this->rootPath, null, null, null);
            $process->setTty(true);

            $process->run();

            if (!$process->isSuccessful()) {
                throw new ProcessFailedException($process);
            }
        } else {
            $process = proc_open($command, [], $pipes, $this->rootPath);
            if (!is_resource($process)) {
                throw new RuntimeException('Failed to call make:entity.');
            }

            $returnCode = proc_close($process);
            if ($returnCode) {
                throw new RuntimeException('Failed to call make:entity.');
            }
        }
    }
}
