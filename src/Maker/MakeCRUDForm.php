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

use Kaudaj\PrestaShopMaker\Builder\CRUDFormControllerBuilder;
use Symfony\Bundle\MakerBundle\ConsoleStyle;
use Symfony\Bundle\MakerBundle\DependencyBuilder;
use Symfony\Bundle\MakerBundle\Doctrine\DoctrineHelper;
use Symfony\Bundle\MakerBundle\Exception\RuntimeCommandException;
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
    private $rootPath;
    /** @var YamlSourceManipulator */
    private $servicesManipulator;

    /** @var string */
    private $entityClassName;

    public function __construct(
        FileManager $fileManager,
        Generator $generator,
        DoctrineHelper $entityHelper
    ) {
        $this->fileManager = $fileManager;
        $this->generator = $generator;
        $this->entityHelper = $entityHelper;

        $this->rootPath = $this->fileManager->getRootDirectory().'/';
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

        $this->generateFormDataProvider();

        $this->generateFormBuilder();

        $this->generateFormDataHandler();

        $this->generateFormHandler();

        $this->generateController();

        $this->generateTemplates();

        $generator->writeChanges();

        $this->writeSuccessMessage($io);

        $io->text([
            'Next: Add fields to your form and start using it.',
            'Find the PrestaShop documentation at <fg=yellow>https://devdocs.prestashop.com/1.7/development/architecture/migration-guide/forms/</>',
            'and the Symfony documentation at <fg=yellow>https://symfony.com/doc/current/forms.html</>',
        ]);
    }

    private function generateFormDataProvider(): void
    {
        $classNameDetails = $this->generator->createClassNameDetails(
            $this->entityClassName,
            "Form\\$this->entityClassName\\",
            'FormDataProvider'
        );

        $this->generator->generateClass(
            $classNameDetails->getFullName(),
            $this->rootPath.self::TEMPLATES_PATH.'DataProvider.tpl.php',
            [
                'entity_class_name' => $this->entityClassName,
                'entity_var' => Str::asLowerCamelCase($this->entityClassName),
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
                "Kaudaj\PrestaShopMaker\Form\\$this->entityClassName\\{$this->entityClassName}Type",
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

        $this->generator->generateClass(
            $classNameDetails->getFullName(),
            $this->rootPath.self::TEMPLATES_PATH.'DataHandler.tpl.php',
            [
                'entity_class_name' => $this->entityClassName,
                'entity_var' => Str::asLowerCamelCase($this->entityClassName),
            ]
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

        if (method_exists($controllerClassNameDetails->getFullName(), 'createAction')) {
            throw new RuntimeCommandException(sprintf('Method "createAction" already exists on class %s', $controllerClassNameDetails->getFullName()));
        }

        if (method_exists($controllerClassNameDetails->getFullName(), 'editAction')) {
            throw new RuntimeCommandException(sprintf('Method "editAction" already exists on class %s', $controllerClassNameDetails->getFullName()));
        }

        $manipulator = new ClassSourceManipulator($controllerSourceCode, true);

        $controllerBuilder = new CRUDFormControllerBuilder($this->entityClassName);
        $controllerBuilder->addCreateAction($manipulator);
        $controllerBuilder->addEditAction($manipulator);

        $this->generator->dumpFile($controllerPath, $manipulator->getSourceCode());
    }

    private function generateTemplates(): void
    {
        $this->generator->generateTemplate(
            "{$this->entityClassName}/Blocks/form.html.twig",
            $this->rootPath.self::TEMPLATES_PATH.'form.tpl.php',
            [
                'entityVar' => Str::asLowerCamelCase($this->entityClassName),
                'entitySnake' => Str::asSnakeCase($this->entityClassName),
                'entityHumanWords' => Str::asHumanWords($this->entityClassName),
            ]
        );

        $this->generator->generateTemplate(
            "{$this->entityClassName}/create.html.twig",
            $this->rootPath.self::TEMPLATES_PATH.'create.tpl.php',
            [
                'entityClassName' => $this->entityClassName,
            ]
        );

        $this->generator->generateTemplate(
            "{$this->entityClassName}/edit.html.twig",
            $this->rootPath.self::TEMPLATES_PATH.'edit.tpl.php',
            [
                'entityClassName' => $this->entityClassName,
            ]
        );
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

    public function configureDependencies(DependencyBuilder $dependencies): void
    {
    }
}
