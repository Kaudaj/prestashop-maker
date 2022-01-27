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

namespace Kaudaj\PrestaShopMaker\Maker;

use RuntimeException;
use Symfony\Bundle\MakerBundle\ConsoleStyle;
use Symfony\Bundle\MakerBundle\DependencyBuilder;
use Symfony\Bundle\MakerBundle\Doctrine\DoctrineHelper;
use Symfony\Bundle\MakerBundle\Doctrine\EntityRelation;
use Symfony\Bundle\MakerBundle\FileManager;
use Symfony\Bundle\MakerBundle\Generator;
use Symfony\Bundle\MakerBundle\InputConfiguration;
use Symfony\Bundle\MakerBundle\Maker\AbstractMaker;
use Symfony\Bundle\MakerBundle\Str;
use Symfony\Bundle\MakerBundle\Util\ClassSourceManipulator;
use Symfony\Bundle\MakerBundle\Validator;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputArgument;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Question\Question;

final class MakeMultiLangEntity extends AbstractMaker
{
    public const TEMPLATES_PATH = 'src/Resources/skeleton/multi-lang-entity/';
    public const HELP_FILE = 'src/Resources/help/MakeMultiLangEntity.txt';

    /** @var FileManager */
    private $fileManager;
    /** @var DoctrineHelper */
    private $entityHelper;

    /** @var string */
    private $entityClassName;

    /** @var string */
    private $rootPath;

    public function __construct(
        FileManager $fileManager,
        DoctrineHelper $entityHelper
    ) {
        $this->fileManager = $fileManager;
        $this->entityHelper = $entityHelper;

        $this->rootPath = $this->fileManager->getRootDirectory().'/';
    }

    public static function getCommandName(): string
    {
        return 'make:prestashop:multi-lang-entity';
    }

    public static function getCommandDescription(): string
    {
        return 'Make a multi lang entity';
    }

    public function configureCommand(Command $command, InputConfiguration $inputConf): void
    {
        $command
            ->addArgument('entity-class', InputArgument::OPTIONAL, 'Class name of the related entity')
        ;

        $helpFileContents = file_get_contents($this->rootPath.self::HELP_FILE);
        if ($helpFileContents) {
            $command->setHelp($helpFileContents);
        }

        $inputConf->setArgumentAsNonInteractive('entity-class');
    }

    public function interact(InputInterface $input, ConsoleStyle $io, Command $command): void
    {
        if (null === $input->getArgument('entity-class')) {
            $argument = $command->getDefinition()->getArgument('entity-class');

            $entities = $this->entityHelper->getEntitiesForAutocomplete();

            $question = new Question($argument->getDescription());
            $question->setValidator(function ($answer) use ($entities) {return Validator::existsOrNull($answer, $entities); });
            $question->setAutocompleterValues($entities);
            $question->setMaxAttempts(3);

            $input->setArgument('entity-class', $io->askQuestion($question));
        }
    }

    public function generate(InputInterface $input, ConsoleStyle $io, Generator $generator): void
    {
        $this->entityClassName = $input->getArgument('entity-class');

        $this->runLangEntityMaker();
        $this->addEntityRelation();
        $this->addLangRelation();

        $this->writeSuccessMessage($io);
    }

    private function runLangEntityMaker(): void
    {
        $process = proc_open("bin/console make:entity {$this->entityClassName}Lang", [], $pipes, $this->rootPath);
        if (is_resource($process)) {
            $returnCode = proc_close($process);

            if ($returnCode) {
                throw new RuntimeException('Make command failed.');
            }
        }
    }

    private function addEntityRelation(): void
    {
        $relation = new EntityRelation(
            EntityRelation::MANY_TO_ONE,
            "{$this->entityClassName}Lang",
            "{$this->entityClassName}"
        );

        $relation->setIsNullable(false);
        $relation->setOrphanRemoval(true);

        $relation->setOwningProperty(Str::asLowerCamelCase($this->entityClassName));
        $relation->setInverseProperty(Str::asLowerCamelCase($this->entityClassName).'Langs');

        $langEntityPathname = "{$this->rootPath}src/Entity/{$this->entityClassName}Lang.php";
        $langEntityContent = file_get_contents($langEntityPathname);

        if ($langEntityContent) {
            $langEntityManipulator = new ClassSourceManipulator($langEntityContent, true);
            $langEntityManipulator->addManyToOneRelation($relation->getOwningRelation());

            file_put_contents($langEntityPathname, $langEntityManipulator->getSourceCode());
        }

        $entityPathname = "{$this->rootPath}src/Entity/{$this->entityClassName}.php";
        $entityContent = file_get_contents($entityPathname);

        if ($entityContent) {
            $entityManipulator = new ClassSourceManipulator($entityContent, true);
            $entityManipulator->addOneToManyRelation($relation->getInverseRelation());

            file_put_contents($entityPathname, $entityManipulator->getSourceCode());
        }
    }

    private function addLangRelation(): void
    {
        $relation = new EntityRelation(
            EntityRelation::MANY_TO_ONE,
            "{$this->entityClassName}Lang",
            "PrestaShopBundle\Entity\Lang"
        );

        $relation->setIsNullable(false);
        $relation->setOrphanRemoval(true);

        $relation->setOwningProperty(Str::asLowerCamelCase('Lang'));
        $relation->setMapInverseRelation(false);

        $langEntityPathname = "{$this->rootPath}src/Entity/{$this->entityClassName}Lang.php";
        $langEntityContent = file_get_contents($langEntityPathname);

        if ($langEntityContent) {
            $langEntityManipulator = new ClassSourceManipulator($langEntityContent, true);
            $langEntityManipulator->addManyToOneRelation($relation->getOwningRelation());

            file_put_contents($langEntityPathname, $langEntityManipulator->getSourceCode());
        }
    }

    public function configureDependencies(DependencyBuilder $dependencies): void
    {
    }
}
