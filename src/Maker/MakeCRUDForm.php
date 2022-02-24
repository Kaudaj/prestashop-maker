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

use Kaudaj\PrestaShopMaker\Builder\CRUDForm\ControllerBuilder;
use Kaudaj\PrestaShopMaker\Builder\CRUDForm\QueryResultBuilder;
use Symfony\Bundle\MakerBundle\ConsoleStyle;
use Symfony\Bundle\MakerBundle\Doctrine\DoctrineHelper;
use Symfony\Bundle\MakerBundle\FileManager;
use Symfony\Bundle\MakerBundle\Generator;
use Symfony\Bundle\MakerBundle\InputConfiguration;
use Symfony\Bundle\MakerBundle\Renderer\FormTypeRenderer;
use Symfony\Bundle\MakerBundle\Str;
use Symfony\Bundle\MakerBundle\Util\ClassDetails;
use Symfony\Bundle\MakerBundle\Util\ClassNameDetails;
use Symfony\Bundle\MakerBundle\Util\ClassSourceManipulator;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputInterface;

final class MakeCRUDForm extends EntityBasedMaker
{
    public function __construct(FileManager $fileManager, ?string $destinationModule, DoctrineHelper $entityHelper)
    {
        parent::__construct($fileManager, $destinationModule, $entityHelper);

        $this->templatesPath .= 'crud-form/';
    }

    public static function getCommandName(): string
    {
        return 'make:prestashop:crud-form';
    }

    /**
     * @return string[]
     */
    public static function getCommandAliases(): array
    {
        return ['make:ps:crud-form'];
    }

    public static function getCommandDescription(): string
    {
        return 'Creates a CRUD Form';
    }

    public function configureCommand(Command $command, InputConfiguration $inputConf): void
    {
        parent::configureCommand($command, $inputConf);

        $helpFileContents = file_get_contents($this->rootPath.'src/Resources/help/MakeCRUDForm.txt');
        if ($helpFileContents) {
            $command->setHelp($helpFileContents);
        }
    }

    public function generate(InputInterface $input, ConsoleStyle $io, Generator $generator): void
    {
        parent::generate($input, $io, $generator);

        //CQRS
        $this->generateQuery();
        $this->generateQueryResult();
        $this->generateQueryHandler();

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
            "Domain\\{$this->entityClassName}\\QueryResult\\"
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
            "Get{$this->entityClassName}ForEditing",
            "Domain\\{$this->entityClassName}\\QueryHandler\\",
            'Handler'
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
            .'.query_handler.'.str_replace('_handler', '', Str::asSnakeCase($classNameDetails->getShortName()));
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

    private function generateFormType(): void
    {
        $formClassNameDetails = $this->generator->createClassNameDetails(
            $this->entityClassName,
            "Form\\{$this->entityClassName}\\",
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
                "@$dataProviderServiceName",
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
            'arguments' => [
                "@$dataHandlerServiceName",
            ],
        ]);
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
                $this->templatesPath.'controller/Controller.tpl.php'
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
        $this->generateFile(
            "views/templates/Admin/{$this->entityClassName}/Blocks/form.html.twig",
            'templates/form.tpl.php'
        );

        $this->generateFile(
            "views/templates/Admin/{$this->entityClassName}/create.html.twig",
            'templates/create.tpl.php'
        );

        $this->generateFile(
            "views/templates/Admin/{$this->entityClassName}/edit.html.twig",
            'templates/edit.tpl.php'
        );
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
}
