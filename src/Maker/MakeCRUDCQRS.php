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

use Kaudaj\PrestaShopMaker\Builder\CRUDForm\CommandBuilder;
use Symfony\Bundle\MakerBundle\ConsoleStyle;
use Symfony\Bundle\MakerBundle\Doctrine\DoctrineHelper;
use Symfony\Bundle\MakerBundle\FileManager;
use Symfony\Bundle\MakerBundle\Generator;
use Symfony\Bundle\MakerBundle\InputConfiguration;
use Symfony\Bundle\MakerBundle\Str;
use Symfony\Bundle\MakerBundle\Util\ClassSourceManipulator;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputInterface;

final class MakeCRUDCQRS extends EntityBasedMaker
{
    /**
     * @var string
     */
    private $namespacePrefix;

    public function __construct(FileManager $fileManager, ?string $destinationModule, DoctrineHelper $entityHelper)
    {
        parent::__construct($fileManager, $destinationModule, $entityHelper);

        $this->templatesPath .= 'crud-cqrs/';
    }

    public static function getCommandName(): string
    {
        return 'make:prestashop:crud-cqrs';
    }

    /**
     * @return string[]
     */
    public static function getCommandAliases(): array
    {
        return ['make:ps:crud-cqrs'];
    }

    public static function getCommandDescription(): string
    {
        return 'Creates a CRUD CQRS set';
    }

    public function configureCommand(Command $command, InputConfiguration $inputConf): void
    {
        parent::configureCommand($command, $inputConf);

        $helpFileContents = file_get_contents($this->rootPath.'src/Resources/help/MakeCRUDCQRS.txt');
        if ($helpFileContents) {
            $command->setHelp($helpFileContents);
        }
    }

    public function generate(InputInterface $input, ConsoleStyle $io, Generator $generator): void
    {
        parent::generate($input, $io, $generator);

        $this->namespacePrefix = (!$this->destinationModule ? 'Core\\' : '')."Domain\\{$this->entityClassName}\\";

        $commandsNames = ['Add', 'Edit', 'Delete'];

        $this->generateExceptions();
        $this->generateValueObject();

        //TODO: If destination is PS core, add interfaces for handlers in Adapter namespace

        $this->generateQuery();
        $this->generateAbstractQueryHandler();
        $this->generateQueryHandler();

        $this->generateAbstractCommandHandler();
        foreach ($commandsNames as $commandName) {
            $this->generateCommand($commandName);
            $this->generateCommandHandler($commandName);
        }

        $generator->writeChanges();

        $this->writeSuccessMessage($io);
    }

    private function generateExceptions(): void
    {
        $classNameDetails = $this->generator->createClassNameDetails(
            "{$this->entityClassName}",
            "{$this->namespacePrefix}Exception\\",
            'Exception'
        );

        $this->generateClass(
            $classNameDetails->getFullName(),
            'Exception.tpl.php'
        );

        $entityLowerWords = strtolower(Str::asHumanWords($this->entityClassName));

        $this->generateSubException(
            "CannotAdd{$this->entityClassName}",
            "Raised when failed to add $entityLowerWords entity."
        );
        $this->generateSubException(
            "{$this->entityClassName}NotFound",
            "Raised when $entityLowerWords was not found."
        );
        $this->generateSubException(
            "CannotUpdate{$this->entityClassName}",
            "Raised when failed to update $entityLowerWords entity."
        );
        $this->generateSubException(
            "CannotDelete{$this->entityClassName}",
            "Raised when failed to delete $entityLowerWords entity."
        );
    }

    private function generateSubException(string $exceptionName, string $annotation): void
    {
        $classNameDetails = $this->generator->createClassNameDetails(
            $exceptionName,
            "{$this->namespacePrefix}Exception\\",
            'Exception'
        );

        $this->generateClass(
            $classNameDetails->getFullName(),
            'SubException.tpl.php',
            [
                'annotation' => $annotation,
            ]
        );
    }

    private function generateValueObject(): void
    {
        $classNameDetails = $this->generator->createClassNameDetails(
            "{$this->entityClassName}Id",
            "{$this->namespacePrefix}ValueObject\\"
        );

        $this->generateClass(
            $classNameDetails->getFullName(),
            'ValueObject.tpl.php'
        );
    }

    private function generateQuery(): void
    {
        $classNameDetails = $this->generator->createClassNameDetails(
            "Get{$this->entityClassName}",
            "{$this->namespacePrefix}Query\\"
        );

        $this->generateClass(
            $classNameDetails->getFullName(),
            'Query.tpl.php'
        );
    }

    private function generateAbstractQueryHandler(): void
    {
        $classNameDetails = $this->generator->createClassNameDetails(
            "Abstract{$this->entityClassName}",
            "{$this->namespacePrefix}QueryHandler\\",
            'QueryHandler'
        );

        $this->generateClass(
            $classNameDetails->getFullName(),
            'AbstractQueryHandler.tpl.php'
        );

        $handlerServiceName = self::SERVICES_PREFIX.'.'.Str::asSnakeCase($this->entityClassName)
            .'.query_handler.abstract';
        $this->addService(
            $handlerServiceName,
            [
                'abstract' => true,
                'class' => $classNameDetails->getFullName(),
                'arguments' => [
                    '@doctrine.orm.entity_manager',
                ],
            ]
        );
    }

    private function generateQueryHandler(): void
    {
        $classNameDetails = $this->generator->createClassNameDetails(
            "Get{$this->entityClassName}",
            "{$this->namespacePrefix}QueryHandler\\",
            'Handler'
        );

        $this->generateClass(
            $classNameDetails->getFullName(),
            'QueryHandler.tpl.php'
        );

        $handlerServiceName = self::SERVICES_PREFIX.'.'.Str::asSnakeCase($this->entityClassName)
            .'.query_handler.get';
        $this->addService(
            $handlerServiceName,
            [
                'public' => true,
                'parent' => self::SERVICES_PREFIX.'.'.Str::asSnakeCase($this->entityClassName).'.query_handler.abstract',
                'class' => $classNameDetails->getFullName(),
                'tags' => [
                    'name' => 'tactician.handler',
                    'command' => "{$this->psr4}Domain\\{$this->entityClassName}\\Query\\Get{$this->entityClassName}",
                ],
            ]
        );
    }

    private function generateCommand(string $name): void
    {
        $classNameDetails = $this->generator->createClassNameDetails(
            "{$name}{$this->entityClassName}",
            "{$this->namespacePrefix}Command\\",
            'Command'
        );

        $path = $this->generateClass(
            $classNameDetails->getFullName(),
            "{$name}Command.tpl.php"
        );

        if ('Delete' == $name) {
            return;
        }

        $sourceCode = $this->generator->getFileContentsForPendingOperation($path);

        if (!$path || !$sourceCode) {
            return;
        }

        $manipulator = new ClassSourceManipulator($sourceCode, true);

        $commandBuilder = new CommandBuilder($this->getEntityProperties());

        $commandBuilder->addProperties($manipulator);
        $commandBuilder->addGettersAndSetters($manipulator);

        $this->generator->dumpFile($path, $manipulator->getSourceCode());
    }

    private function generateAbstractCommandHandler(): void
    {
        $classNameDetails = $this->generator->createClassNameDetails(
            "Abstract{$this->entityClassName}",
            "{$this->namespacePrefix}CommandHandler\\",
            'CommandHandler'
        );

        $this->generateClass(
            $classNameDetails->getFullName(),
            'AbstractCommandHandler.tpl.php'
        );

        $handlerServiceName = self::SERVICES_PREFIX.'.'.Str::asSnakeCase($this->entityClassName)
            .'.command_handler.abstract';
        $this->addService(
            $handlerServiceName,
            [
                'abstract' => true,
                'class' => $classNameDetails->getFullName(),
                'arguments' => [
                    '@doctrine.orm.entity_manager',
                ],
            ]
        );
    }

    private function generateCommandHandler(string $name): void
    {
        $classNameDetails = $this->generator->createClassNameDetails(
            "{$name}{$this->entityClassName}",
            "{$this->namespacePrefix}CommandHandler\\",
            'Handler'
        );

        $this->generateClass(
            $classNameDetails->getFullName(),
            "{$name}CommandHandler.tpl.php"
        );

        $handlerServiceName = self::SERVICES_PREFIX.'.'.Str::asSnakeCase($this->entityClassName)
            .".command_handler.$name";
        $this->addService(
            $handlerServiceName,
            [
                'public' => true,
                'parent' => self::SERVICES_PREFIX.'.'.Str::asSnakeCase($this->entityClassName).'.query_handler.abstract',
                'class' => $classNameDetails->getFullName(),
                'tags' => [
                    'name' => 'tactician.handler',
                    'command' => "{$this->psr4}Domain\\{$this->entityClassName}\\Command\\{$name}{$this->entityClassName}Command",
                ],
            ]
        );
    }
}
