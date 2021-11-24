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

use Kaudaj\PrestaShopMaker\Builder\CRUDForm\CommandBuilder;
use Kaudaj\PrestaShopMaker\Builder\CRUDForm\ControllerBuilder;
use Kaudaj\PrestaShopMaker\Builder\CRUDForm\QueryResultBuilder;
use Symfony\Bundle\MakerBundle\ConsoleStyle;
use Symfony\Bundle\MakerBundle\DependencyBuilder;
use Symfony\Bundle\MakerBundle\Doctrine\DoctrineHelper;
use Symfony\Bundle\MakerBundle\FileManager;
use Symfony\Bundle\MakerBundle\Generator;
use Symfony\Bundle\MakerBundle\InputConfiguration;
use Symfony\Bundle\MakerBundle\Maker\AbstractMaker;
use Symfony\Bundle\MakerBundle\Renderer\FormTypeRenderer;
use Symfony\Bundle\MakerBundle\Str;
use Symfony\Bundle\MakerBundle\Util\ClassDetails;
use Symfony\Bundle\MakerBundle\Util\ClassNameDetails;
use Symfony\Bundle\MakerBundle\Util\ClassSourceManipulator;
use Symfony\Bundle\MakerBundle\Util\YamlManipulationFailedException;
use Symfony\Bundle\MakerBundle\Util\YamlSourceManipulator;
use Symfony\Bundle\MakerBundle\Validator;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputArgument;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Question\Question;

final class MakeCRUDForm extends AbstractMaker
{
    public const SERVICES_PREFIX = 'kaudaj.prestashop_maker';
    public const TEMPLATES_PATH = 'src/Resources/skeleton/crud-form/';
    public const HELP_FILE = 'src/Resources/help/MakeCRUDForm.txt';

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
        return 'make:crud-form';
    }

    public static function getCommandDescription(): string
    {
        return 'Creates a CRUD Form';
    }

    public function configureCommand(Command $command, InputConfiguration $inputConf): void
    {
        $command
            ->addArgument('entity-class', InputArgument::OPTIONAL, 'The class name of the entity to create CRUD Form.')
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

        //CQRS
        $this->generateExceptions();
        $this->generateValueObject();
        $this->generateQuery();
        $this->generateQueryResult();
        $this->generateQueryHandler();
        $this->generateAddCommand();
        $this->generateAddCommandHandler();
        $this->generateEditCommand();
        $this->generateEditCommandHandler();

        //Form
        $this->generateFormType();
        $this->generateFormDataProvider();
        $this->generateFormBuilder();
        $this->generateFormDataHandler();
        $this->generateFormHandler();

        //Controller
        $this->generateController();

        //Templates
        $this->generateTemplates();

        $generator->writeChanges();

        $this->writeSuccessMessage($io);

        $io->text([
            'Next: Add fields to your form and start using it.',
            'Find the PrestaShop documentation at <fg=yellow>https://devdocs.prestashop.com/1.7/development/architecture/migration-guide/forms/</>',
            'and the Symfony documentation at <fg=yellow>https://symfony.com/doc/current/forms.html</>',
        ]);
    }

    private function generateExceptions(): void
    {
        $classNameDetails = $this->generator->createClassNameDetails(
            "{$this->entityClassName}",
            "Domain\\{$this->entityClassName}\\Exception\\",
            'Exception'
        );

        $this->generateClass(
            $classNameDetails->getFullName(),
            'cqrs/Exception.tpl.php'
        );

        $entityLowerWords = strtolower(Str::asHumanWords($this->entityClassName));

        $this->generateSubException(
            'NotFound',
            "Raised when $entityLowerWords was not found."
        );
        $this->generateSubException(
            'CannotAdd',
            "Raised when failed to add $entityLowerWords entity."
        );
        $this->generateSubException(
            'CannotUpdate',
            "Raised when failed to update $entityLowerWords entity."
        );
    }

    private function generateSubException(string $exceptionName, string $annotation): void
    {
        $classNameDetails = $this->generator->createClassNameDetails(
            "$exceptionName{$this->entityClassName}",
            "Domain\\{$this->entityClassName}\\Exception\\",
            'Exception'
        );

        $this->generateClass(
            $classNameDetails->getFullName(),
            'cqrs/SubException.tpl.php',
            [
                'annotation' => $annotation,
            ]
        );
    }

    private function generateValueObject(): void
    {
        $classNameDetails = $this->generator->createClassNameDetails(
            "{$this->entityClassName}Id",
            "Domain\\{$this->entityClassName}\\ValueObject\\"
        );

        $this->generateClass(
            $classNameDetails->getFullName(),
            'cqrs/ValueObject.tpl.php'
        );
    }

    private function generateQuery(): void
    {
        $classNameDetails = $this->generator->createClassNameDetails(
            "Get{$this->entityClassName}ForEditing",
            "Domain\\{$this->entityClassName}\\Query\\"
        );

        $this->generateClass(
            $classNameDetails->getFullName(),
            'cqrs/Query.tpl.php'
        );
    }

    private function generateQueryResult(): void
    {
        $classNameDetails = $this->generator->createClassNameDetails(
            "Editable{$this->entityClassName}",
            'QueryResult\\'
        );

        $sourceCode = '';
        $path = '';

        $path = $this->generateClass(
            $classNameDetails->getFullName(),
            'cqrs/QueryResult.tpl.php'
        );

        $sourceCode = $this->generator->getFileContentsForPendingOperation($path);

        if (!$path || !$sourceCode) {
            return;
        }

        $manipulator = new ClassSourceManipulator($sourceCode, true);

        $resultBuilder = new QueryResultBuilder($this->entityClassName, $this->getEntityProperties());
        $resultBuilder->addProperties($manipulator);
        $resultBuilder->addConstructor($manipulator);

        $this->generator->dumpFile($path, $manipulator->getSourceCode());
    }

    private function generateQueryHandler(): void
    {
        $classNameDetails = $this->generator->createClassNameDetails(
            "Get{$this->entityClassName}ForEditingHandler",
            "Domain\\{$this->entityClassName}\\QueryHandler\\"
        );

        $entityGetMethods = [];
        foreach ($this->getEntityProperties() as $property) {
            $entityGetMethods[] = 'get'.Str::asCamelCase($property->getName());
        }

        $this->generateClass(
            $classNameDetails->getFullName(),
            'cqrs/QueryHandler.tpl.php',
            [
                'entity_get_methods' => $entityGetMethods,
            ]
        );

        $handlerServiceName = self::SERVICES_PREFIX.'.'.Str::asSnakeCase($this->entityClassName)
            .'.query_handler.'.Str::asSnakeCase($classNameDetails->getShortName());
        $this->addService(
            $handlerServiceName,
            [
                'class' => $classNameDetails->getFullName(),
                'tags' => [
                    'name' => 'tactician.handler',
                    'command' => "{$this->psr4}Domain\\{$this->entityClassName}\\Query\\Get{$this->entityClassName}ForEditing",
                ],
            ]
        );
    }

    private function generateAddCommand(): void
    {
        $classNameDetails = $this->generator->createClassNameDetails(
            "Add{$this->entityClassName}",
            "Domain\\{$this->entityClassName}\\Command\\",
            'Command'
        );

        $path = $this->generateClass(
            $classNameDetails->getFullName(),
            'cqrs/AddCommand.tpl.php'
        );

        $sourceCode = $this->generator->getFileContentsForPendingOperation($path);

        if (!$path || !$sourceCode) {
            return;
        }

        $manipulator = new ClassSourceManipulator($sourceCode, true);

        $commandBuilder = new CommandBuilder($this->getEntityProperties());
        $commandBuilder->addProperties($manipulator);
        $commandBuilder->addSetterMethods($manipulator);

        $this->generator->dumpFile($path, $manipulator->getSourceCode());
    }

    private function generateAddCommandHandler(): void
    {
        $classNameDetails = $this->generator->createClassNameDetails(
            "Add{$this->entityClassName}",
            "Domain\\{$this->entityClassName}\\CommandHandler\\",
            'CommandHandler'
        );

        $entityPropertiesNames = [];
        foreach ($this->getEntityProperties() as $property) {
            $entityPropertiesNames[] = $property->getName();
        }

        $this->generateClass(
            $classNameDetails->getFullName(),
            'cqrs/AddCommandHandler.tpl.php',
            [
                'entity_properties' => $entityPropertiesNames,
            ]
        );

        $handlerServiceName = self::SERVICES_PREFIX.'.'.Str::asSnakeCase($this->entityClassName)
            .'.command_handler.add_'.Str::asSnakeCase($classNameDetails->getShortName());
        $this->addService(
            $handlerServiceName,
            [
                'class' => $classNameDetails->getFullName(),
                'tags' => [
                    'name' => 'tactician.handler',
                    'command' => "{$this->psr4}Domain\\{$this->entityClassName}\\Command\\Add{$this->entityClassName}Command",
                ],
            ]
        );
    }

    private function generateEditCommand(): void
    {
        $classNameDetails = $this->generator->createClassNameDetails(
            "Edit{$this->entityClassName}",
            "Domain\\{$this->entityClassName}\\Command\\",
            'Command'
        );

        $path = $this->generateClass(
            $classNameDetails->getFullName(),
            'cqrs/EditCommand.tpl.php'
        );

        $sourceCode = $this->generator->getFileContentsForPendingOperation($path);

        if (!$path || !$sourceCode) {
            return;
        }

        $manipulator = new ClassSourceManipulator($sourceCode, true);

        $commandBuilder = new CommandBuilder($this->getEntityProperties());
        $commandBuilder->addProperties($manipulator);
        $commandBuilder->addSetterMethods($manipulator);

        $this->generator->dumpFile($path, $manipulator->getSourceCode());
    }

    private function generateEditCommandHandler(): void
    {
        $classNameDetails = $this->generator->createClassNameDetails(
            "Edit{$this->entityClassName}",
            "Domain\\{$this->entityClassName}\\CommandHandler\\",
            'CommandHandler'
        );

        $entityPropertiesNames = [];
        foreach ($this->getEntityProperties() as $property) {
            $entityPropertiesNames[] = $property->getName();
        }

        $this->generateClass(
            $classNameDetails->getFullName(),
            'cqrs/EditCommandHandler.tpl.php',
            [
                'entity_properties' => $entityPropertiesNames,
            ]
        );

        $handlerServiceName = self::SERVICES_PREFIX.'.'.Str::asSnakeCase($this->entityClassName)
            .'.command_handler.edit_'.Str::asSnakeCase($classNameDetails->getShortName());
        $this->addService(
            $handlerServiceName,
            [
                'class' => $classNameDetails->getFullName(),
                'tags' => [
                    'name' => 'tactician.handler',
                    'command' => "{$this->psr4}Domain\\{$this->entityClassName}\\Command\\Edit{$this->entityClassName}Command",
                ],
            ]
        );
    }

    private function generateFormType(): void
    {
        $formClassNameDetails = $this->generator->createClassNameDetails(
            $this->entityClassName,
            'Form\\',
            'Type'
        );

        $boundClassDetails = $this->generator->createClassNameDetails(
            $this->entityClassName,
            'Entity\\'
        );

        $formFields = $this->getFormFields($boundClassDetails);

        $formTypeRenderer = new FormTypeRenderer($this->generator);

        $formTypeRenderer->render(
            $formClassNameDetails,
            $formFields,
            $boundClassDetails
        );
    }

    private function generateFormDataProvider(): void
    {
        $classNameDetails = $this->generator->createClassNameDetails(
            $this->entityClassName,
            "Form\\{$this->entityClassName}\\",
            'FormDataProvider'
        );

        $boundClassDetails = $this->generator->createClassNameDetails(
            $this->entityClassName,
            'Entity\\'
        );

        $formFields = $this->getFormFields($boundClassDetails);

        foreach (array_keys($formFields) as $field) {
            $getMethod = 'get'.Str::asCamelCase($field);
            $formFields[$field] = "\$editable{$this->entityClassName}->$getMethod()";
        }

        $this->generateClass(
            $classNameDetails->getFullName(),
            'form/DataProvider.tpl.php',
            [
                'form_fields' => $formFields,
            ]
        );

        $serviceName = self::SERVICES_PREFIX.'.form.'
            .Str::asSnakeCase($this->entityClassName).'.'
            .Str::asSnakeCase($this->entityClassName).'_form_data_provider';

        $this->addService($serviceName, [
            'class' => $classNameDetails->getFullName(),
            'arguments' => ['@prestashop.core.command_bus'],
        ]);
    }

    private function generateFormBuilder(): void
    {
        $entitySnakeName = Str::asSnakeCase($this->entityClassName);
        $formServicesPrefix = self::SERVICES_PREFIX.'.form.'.$entitySnakeName.'.';

        $serviceName = $formServicesPrefix.$entitySnakeName.'_form_builder';
        $dataProviderServiceName = $formServicesPrefix.$entitySnakeName.'_form_data_provider';

        $this->addService($serviceName, [
            'class' => 'PrestaShop\PrestaShop\Core\Form\IdentifiableObject\Builder\FormBuilder',
            'factory' => 'prestashop.core.form.builder.form_builder_factory:create',
            'arguments' => [
                "Kaudaj\PrestaShopMaker\Form\\{$this->entityClassName}\\{$this->entityClassName}Type",
                $dataProviderServiceName,
            ],
        ]);
    }

    private function generateFormDataHandler(): void
    {
        $classNameDetails = $this->generator->createClassNameDetails(
            $this->entityClassName,
            "Form\\$this->entityClassName\\",
            'FormDataHandler'
        );

        $this->generateClass(
            $classNameDetails->getFullName(),
            'form/DataHandler.tpl.php'
        );

        $serviceName = self::SERVICES_PREFIX.'.form.'
            .Str::asSnakeCase($this->entityClassName).'.'
            .Str::asSnakeCase($this->entityClassName).'_form_data_handler';

        $this->addService($serviceName, [
            'class' => $classNameDetails->getFullName(),
            'arguments' => ['@prestashop.core.command_bus'],
        ]);
    }

    private function generateFormHandler(): void
    {
        $entitySnakeName = Str::asSnakeCase($this->entityClassName);
        $formServicesPrefix = self::SERVICES_PREFIX.'.form.'.$entitySnakeName.'.';

        $serviceName = $formServicesPrefix.$entitySnakeName.'_form_handler';
        $dataHandlerServiceName = $formServicesPrefix.$entitySnakeName.'_form_data_handler';

        $this->addService($serviceName, [
            'class' => 'PrestaShop\PrestaShop\Core\Form\IdentifiableObject\Handler\FormHandler',
            'factory' => 'prestashop.core.form.identifiable_object.handler.form_handler_factory:create',
            'arguments' => [$dataHandlerServiceName],
        ]);
    }

    private function generateController(): void
    {
        $controllerClassNameDetails = $this->generator->createClassNameDetails(
            $this->entityClassName,
            'Controller\\',
            'Controller'
        );

        $controllerSourceCode = '';
        $controllerPath = '';

        if (!class_exists($controllerClassNameDetails->getFullName())) {
            $controllerPath = $this->generator->generateController(
                $controllerClassNameDetails->getFullName(),
                $this->rootPath.self::TEMPLATES_PATH.'controller/Controller.tpl.php'
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

        if (!method_exists($controllerClassNameDetails->getFullName(), 'createAction')) {
            $controllerBuilder->addCreateAction($manipulator);
        }

        if (!method_exists($controllerClassNameDetails->getFullName(), 'editAction')) {
            $controllerBuilder->addEditAction($manipulator);
        }

        $this->generator->dumpFile($controllerPath, $manipulator->getSourceCode());
    }

    private function generateTemplates(): void
    {
        $this->generateTemplate(
            "Admin/{$this->entityClassName}/Blocks/form.html.twig",
            'templates/form.tpl.php'
        );

        $this->generateTemplate(
            "Admin/{$this->entityClassName}/create.html.twig",
            'templates/create.tpl.php'
        );

        $this->generateTemplate(
            "Admin/{$this->entityClassName}/edit.html.twig",
            'templates/edit.tpl.php'
        );
    }

    /**
     * @param array<string, mixed> $variables
     */
    private function generateClass(string $classFullName, string $skeletonName, array $variables = []): string
    {
        return $this->generator->generateClass(
            $classFullName,
            $this->rootPath.self::TEMPLATES_PATH.$skeletonName,
            $this->getDefaultVariablesForGeneration() + $variables
        );
    }

    /**
     * @param array<string, mixed> $variables
     */
    private function generateTemplate(string $templatePath, string $skeletonName, array $variables = []): void
    {
        $this->generator->generateTemplate(
            $templatePath,
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
     * @return array<string, mixed>
     */
    private function getFormFields(ClassNameDetails $entityClassDetails): array
    {
        $formFields = ['field_name' => null];

        $doctrineEntityDetails = $this->entityHelper->createDoctrineDetails($entityClassDetails->getFullName());

        if (null !== $doctrineEntityDetails) {
            $formFields = $doctrineEntityDetails->getFormFields();
        } else {
            $classDetails = new ClassDetails($entityClassDetails->getFullName());
            $formFields = $classDetails->getFormFields();
        }

        return $formFields;
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

        return $entityReflect->getProperties();
    }

    public function configureDependencies(DependencyBuilder $dependencies): void
    {
    }
}
