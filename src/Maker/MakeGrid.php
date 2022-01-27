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

use Kaudaj\PrestaShopMaker\Builder\Grid\ControllerBuilder;
use Symfony\Bundle\MakerBundle\ConsoleStyle;
use Symfony\Bundle\MakerBundle\DependencyBuilder;
use Symfony\Bundle\MakerBundle\Doctrine\DoctrineHelper;
use Symfony\Bundle\MakerBundle\FileManager;
use Symfony\Bundle\MakerBundle\Generator;
use Symfony\Bundle\MakerBundle\InputConfiguration;
use Symfony\Bundle\MakerBundle\Maker\AbstractMaker;
use Symfony\Bundle\MakerBundle\Str;
use Symfony\Bundle\MakerBundle\Util\ClassSourceManipulator;
use Symfony\Bundle\MakerBundle\Util\YamlManipulationFailedException;
use Symfony\Bundle\MakerBundle\Util\YamlSourceManipulator;
use Symfony\Bundle\MakerBundle\Validator;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputArgument;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Question\Question;

final class MakeGrid extends AbstractMaker
{
    public const SERVICES_PREFIX = 'kaudaj.prestashop_maker';
    public const TEMPLATES_PATH = 'src/Resources/skeleton/grid/';
    public const HELP_FILE = 'src/Resources/help/MakeGrid.txt';

    /** @var FileManager */
    private $fileManager;
    /** @var Generator */
    private $generator;
    /** @var DoctrineHelper */
    private $entityHelper;

    /** @var string */
    private $entityClassName;

    /** @var string */
    private $rootPath;
    /** @var string */
    private $psr4;
    /** @var YamlSourceManipulator */
    private $servicesManipulator;

    public function __construct(
        FileManager $fileManager,
        Generator $generator,
        DoctrineHelper $entityHelper
    ) {
        $this->fileManager = $fileManager;
        $this->generator = $generator;
        $this->entityHelper = $entityHelper;

        $this->rootPath = $this->fileManager->getRootDirectory().'/';
        $this->psr4 = '';
        if (strpos(__NAMESPACE__, '\\Maker')) {
            $this->psr4 = substr(__NAMESPACE__, 0, strpos(__NAMESPACE__, '\\Maker')).'\\';
        }
        $servicesYaml = $this->fileManager->getFileContents('config/services.yml');
        $this->servicesManipulator = new YamlSourceManipulator($servicesYaml);
    }

    public static function getCommandName(): string
    {
        return 'make:prestashop:grid';
    }

    public static function getCommandDescription(): string
    {
        return 'Creates a Grid';
    }

    public function configureCommand(Command $command, InputConfiguration $inputConf): void
    {
        $command
            ->addArgument('entity-class', InputArgument::OPTIONAL, 'Class name of related entity')
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

        // Grid
        $this->generateGridDefinitionFactory();
        $this->generateFilters();
        $this->generateQueryBuilder();
        $this->generateGridFactory();

        // Controller
        $this->generateController();

        // Templates
        $this->generateTemplates();

        $generator->writeChanges();

        $this->writeSuccessMessage($io);
    }

    private function generateGridDefinitionFactory(): void
    {
        $classNameDetails = $this->generator->createClassNameDetails(
            "{$this->entityClassName}",
            'Grid\\Definition\\Factory\\',
            'GridDefinitionFactory'
        );

        $this->generateClass(
            $classNameDetails->getFullName(),
            'DefinitionFactory.tpl.php',
            [
                'entity_properties' => $this->getEntityProperties(),
            ]
        );

        $entitySnakeCase = Str::asSnakeCase($this->entityClassName);
        $serviceName = self::SERVICES_PREFIX.".grid.definition.factory.{$entitySnakeCase}_grid_definition_factory";

        $this->addService(
            $serviceName,
            [
                'class' => $classNameDetails->getFullName(),
                'parent' => 'prestashop.core.grid.definition.factory.abstract_grid_definition',
                'arguments' => [
                    "@prestashop.core.grid.query.{$entitySnakeCase}_query_builder",
                    '@prestashop.core.hook.dispatcher',
                    '@prestashop.core.grid.query.doctrine_query_parser',
                    $entitySnakeCase,
                ],
            ]
        );
    }

    private function generateFilters(): void
    {
        $classNameDetails = $this->generator->createClassNameDetails(
            "{$this->entityClassName}",
            'Search\\Filters\\',
            'Filters'
        );

        $this->generateClass(
            $classNameDetails->getFullName(),
            'Filters.tpl.php'
        );
    }

    private function generateQueryBuilder(): void
    {
        $classNameDetails = $this->generator->createClassNameDetails(
            "{$this->entityClassName}",
            'Grid\\Query\\',
            'QueryBuilder'
        );

        $this->generateClass(
            $classNameDetails->getFullName(),
            'QueryBuilder.tpl.php',
            [
                'entity_properties' => $this->getEntityProperties(),
            ]
        );

        $entitySnakeCase = Str::asSnakeCase($this->entityClassName);
        $serviceName = self::SERVICES_PREFIX.".grid.query.{$entitySnakeCase}_query_builder";

        $this->addService(
            $serviceName,
            [
                'class' => $classNameDetails->getFullName(),
                'parent' => 'prestashop.core.grid.abstract_query_builder',
                'arguments' => [
                    "@=service('prestashop.adapter.legacy.context').getContext().language.id",
                    "@=service('prestashop.adapter.legacy.context').getContext().shop.id",
                ],
            ]
        );
    }

    private function generateGridFactory(): void
    {
        $entitySnakeCase = Str::asSnakeCase($this->entityClassName);
        $serviceName = self::SERVICES_PREFIX.".grid.{$entitySnakeCase}_grid_factory";

        $this->addService(
            $serviceName,
            [
                'class' => 'PrestaShop\PrestaShop\Core\Grid\GridFactory',
                'arguments' => [
                    "@prestashop.core.grid.definition.factory.{$entitySnakeCase}_grid_definition_factory",
                    "@prestashop.core.grid.data.factory.{$entitySnakeCase}_data_factory",
                    '@prestashop.core.grid.filter.form_factory',
                    '@prestashop.core.hook.dispatcher',
                ],
            ]
        );
    }

    private function generateController(): void
    {
        $controllerClassNameDetails = $this->generator->createClassNameDetails(
            $this->entityClassName,
            'Admin\\Controller\\',
            'Controller'
        );

        $controllerSourceCode = '';
        $controllerPath = '';

        if (!class_exists($controllerClassNameDetails->getFullName())) {
            $controllerPath = $this->generator->generateController(
                $controllerClassNameDetails->getFullName(),
                $this->rootPath.self::TEMPLATES_PATH.'Controller.tpl.php'
            );

            $controllerSourceCode = $this->generator->getFileContentsForPendingOperation($controllerPath);
        } else {
            $controllerPath = $this->fileManager->getRelativePathForFutureClass($controllerClassNameDetails->getFullName());
            if ($controllerPath) {
                $controllerSourceCode = $this->fileManager->getFileContents($controllerPath);
            }
        }

        if (!$controllerPath || !$controllerSourceCode) {
            return;
        }

        $manipulator = new ClassSourceManipulator($controllerSourceCode, true);

        $controllerBuilder = new ControllerBuilder($this->entityClassName);

        if (!method_exists($controllerClassNameDetails->getFullName(), 'indexAction')) {
            $controllerBuilder->addIndexAction($manipulator);
        }

        $this->generator->dumpFile($controllerPath, $manipulator->getSourceCode());
    }

    private function generateTemplates(): void
    {
        $this->generateFile(
            "views/templates/Admin/{$this->entityClassName}/index.html.twig",
            'index.tpl.php'
        );
    }

    /**
     * @param array<string, mixed> $extraVariables
     */
    private function generateClass(string $classFullName, string $skeletonName, array $extraVariables = []): string
    {
        return $this->generator->generateClass(
            $classFullName,
            $this->rootPath.self::TEMPLATES_PATH.$skeletonName,
            $this->getDefaultVariablesForGeneration() + $extraVariables
        );
    }

    /**
     * @param array<string, mixed> $variables
     */
    private function generateFile(string $filePath, string $skeletonName, array $variables = []): void
    {
        $this->generator->generateFile(
            $filePath,
            $this->rootPath.self::TEMPLATES_PATH.$skeletonName,
            $this->getDefaultVariablesForGeneration() + $variables
        );
    }

    /**
     * @return array<string, mixed> $variables
     */
    private function getDefaultVariablesForGeneration(): array
    {
        return [
            'psr_4' => $this->psr4,
            'entity_class_name' => $this->entityClassName,
            'entity_var' => Str::asLowerCamelCase($this->entityClassName),
            'entity_snake' => Str::asSnakeCase($this->entityClassName),
            'entity_human_words' => Str::asHumanWords($this->entityClassName),
            'entity_lower_words' => strtolower(Str::asHumanWords($this->entityClassName)),
        ];
    }

    /**
     * @param array<string, mixed> $params
     */
    private function addService(string $serviceName, array $params): void
    {
        try {
            $newData = $this->servicesManipulator->getData();

            if (isset($newData['services'][$serviceName])) {
                return;
            }

            $newData['services']['_'.$serviceName] = $this->servicesManipulator->createEmptyLine();
            $newData['services'][$serviceName] = $params;

            $this->servicesManipulator->setData($newData);

            $this->generator->dumpFile('config/services.yml', $this->servicesManipulator->getContents());
        } catch (YamlManipulationFailedException $e) {
        }
    }

    /**
     * @return \ReflectionProperty[]
     */
    private function getEntityProperties(): array
    {
        $entityClassDetails = $this->generator->createClassNameDetails(
            $this->entityClassName,
            'Entity\\'
        );

        if (!class_exists($entityClassDetails->getFullName())) {
            return [];
        }

        $entityReflect = new \ReflectionClass($entityClassDetails->getFullName());
        $entityProperties = $entityReflect->getProperties();

        $entityProperties = array_filter($entityProperties, function ($property) {
            return 'id' !== $property->getName();
        });

        return $entityProperties;
    }

    public function configureDependencies(DependencyBuilder $dependencies): void
    {
    }
}