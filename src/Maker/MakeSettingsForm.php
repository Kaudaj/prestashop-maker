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

use Kaudaj\PrestaShopMaker\Util\FormTypesMapper;
use Symfony\Bundle\MakerBundle\ConsoleStyle;
use Symfony\Bundle\MakerBundle\FileManager;
use Symfony\Bundle\MakerBundle\Generator;
use Symfony\Bundle\MakerBundle\InputConfiguration;
use Symfony\Bundle\MakerBundle\Str;
use Symfony\Bundle\MakerBundle\Validator;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputArgument;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Question\Question;
use Symfony\Component\Form\Extension\Core\Type\TextType;
use Symfony\Component\Validator\Constraints\Regex;
use Symfony\Component\Validator\Validation;

final class MakeSettingsForm extends Maker
{
    /**
     * @var string
     */
    private $formName;

    /**
     * @var string
     */
    private $formNameInService;

    /**
     * @var array<array<string, array<string, mixed>>>
     */
    private $formFields;

    /**
     * @var string
     */
    private $formNamespace;

    public function __construct(FileManager $fileManager, ?string $destinationModule)
    {
        parent::__construct($fileManager, $destinationModule);

        $this->templatesPath .= 'settings-form/';
    }

    public static function getCommandName(): string
    {
        return 'make:prestashop:settings-form';
    }

    /**
     * @return string[]
     */
    public static function getCommandAliases(): array
    {
        return ['make:ps:settings-form'];
    }

    public static function getCommandDescription(): string
    {
        return 'Creates a Settings Form';
    }

    public function configureCommand(Command $command, InputConfiguration $inputConf): void
    {
        parent::configureCommand($command, $inputConf);

        $helpFileContents = file_get_contents($this->rootPath.'src/Resources/help/MakeSettingsForm.txt');
        if ($helpFileContents) {
            $command->setHelp($helpFileContents);
        }

        $command
            ->addArgument('form-name', InputArgument::OPTIONAL, sprintf(
                'The name of the form to create (e.g. <fg=yellow>%s</>)',
                Str::asClassName($this->getRandomFormNames())
            ))
        ;

        $inputConf->setArgumentAsNonInteractive('form-name');
    }

    public function interact(InputInterface $input, ConsoleStyle $io, Command $command): void
    {
        if ($input->getArgument('form-name')) {
            return;
        }

        $argument = $command->getDefinition()->getArgument('form-name');

        $question = new Question('Please enter '.lcfirst($argument->getDescription()));
        $question->setValidator(function ($answer) {
            Validator::notBlank($answer);

            return Validator::validateClassName($answer);
        });
        $question->setMaxAttempts(3);

        $input->setArgument('form-name', $io->askQuestion($question));
    }

    private function getRandomFormNames(): string
    {
        $names = [
            'Notifications',
            'General',
            'UploadQuota',
            'SmtpConfiguration',
            'Contact',
            'OrderState',
            'Configure\\AdvancedParameters\\Administration\\General',
        ];

        return $names[array_rand($names)];
    }

    public function generate(InputInterface $input, ConsoleStyle $io, Generator $generator): void
    {
        parent::generate($input, $io, $generator);

        $this->formName = $input->getArgument('form-name');
        $this->formNameInService = $this->formatNamespaceForService($this->formName);
        $this->formFields = $this->askForFields($io);
        $this->formNamespace = (!$this->destinationModule ? 'PrestaShopBundle\\Form\\Admin\\' : 'Form\\')."{$this->formName}\\";

        if (!$this->destinationModule) {
            $question = new Question('Translation domain for the form type', 'Admin.Translation.Domain');
            $translationDomain = $io->askQuestion($question);
        } else {
            $translationDomain = 'Modules.'.ucfirst(strtolower($this->destinationModule)).'.Admin';
        }

        $this->generateFormType($translationDomain);
        $this->generateDataConfiguration();
        $this->generateFormDataProvider();
        $this->generateFormDataHandler();

        $this->generateJavascript();
        $this->generateTemplate($translationDomain);

        $generator->writeChanges();

        $this->writeSuccessMessage($io);
    }

    /**
     * @return array<string, array<string, mixed>>
     */
    private function askForFields(ConsoleStyle $io): array
    {
        $fields = [];
        $currentFields = [];
        $isFirstField = true;

        $newField = $this->askForNextField($io, $isFirstField, $currentFields);

        while (null !== $newField) {
            $isFirstField = false;

            $name = $newField['name'];

            $fields[$name] = $newField;
            $currentFields[] = $name;

            $newField = $this->askForNextField($io, $isFirstField, $currentFields);
        }

        return $fields;
    }

    /**
     * @param string[] $fields
     *
     * @return array{
     *  name: string,
     *  constant: string,
     *  type: string,
     *  short_type: string,
     *  php_type: string|string[]|null,
     *  options: array<string, mixed>
     * }|null Field informations
     */
    private function askForNextField(ConsoleStyle $io, bool $isFirstField, array $fields): ?array
    {
        $io->writeln('');

        if ($isFirstField) {
            $questionText = 'New field name (press <return> to stop adding fields)';
        } else {
            $questionText = 'Add another field? Enter the field name (or press <return> to stop adding fields)';
        }

        $fieldName = $io->ask($questionText, null, function ($name) use ($fields) {
            if (\in_array($name, $fields)) {
                throw new \InvalidArgumentException(sprintf('The "%s" property already exists.', $name));
            }

            $validator = Validation::createValidator();

            $violations = $validator->validate($name, new Regex(
                '/[a-z0-9_]/',
                'The field name must only contain lowercase letters, numbers and underscores.'
            ));

            if (0 !== count($violations)) {
                foreach ($violations as $violation) {
                    throw new \InvalidArgumentException((string) $violation->getMessage());
                }
            }

            return $name;
        });

        if (!$fieldName || !is_string($fieldName)) {
            return null;
        }

        $defaultType = TextType::class;

        $type = null;
        $formTypesMapper = new FormTypesMapper();

        $typesMap = $formTypesMapper->getMap();
        $formattedTypesMap = [];
        foreach ($typesMap as $formType => $phpType) {
            $formattedTypesMap[$formType] = $formTypesMapper->getFormattedFormType($formType);
        }

        while (null === $type) {
            $question = new Question('Field type (enter <comment>?</comment> to see known types)', $formattedTypesMap[$defaultType]);
            $question->setAutocompleterValues(array_values($formattedTypesMap));

            $typeInput = $io->askQuestion($question);

            if ('?' === $typeInput) {
                $formTypesMapper->printMap($io);
                $io->writeln('');
            } elseif (in_array($typeInput, $formattedTypesMap)) {
                $type = array_search($typeInput, $formattedTypesMap);

                if (false === $type || !is_string($type)) {
                    $type = null;
                }
            } elseif ($typeInput && is_string($typeInput)) {
                $type = $typeInput;
            }
        }

        $configurationKey = 'PS_'.strtoupper($fieldName); //TODO: Use env variables (cf README) for prefix

        //TODO: Write a FormField class to avoid big key-array
        return [
            'name' => $fieldName,
            'constant' => 'FIELD_'.strtoupper($fieldName),
            'type' => $type,
            'short_type' => Str::getShortClassName($type),
            'php_type' => $typesMap[$type],
            'options' => [
                'multistore_configuration_key' => "'$configurationKey'",
            ],
        ];
    }

    private function generateFormType(string $translationDomain): void
    {
        $classNameDetails = $this->generator->createClassNameDetails(
            Str::getShortClassName($this->formName),
            $this->formNamespace,
            'Type'
        );

        $this->generateClass(
            $classNameDetails->getFullName(),
            'Type.tpl.php',
            [
                'block_prefix' => Str::asSnakeCase($this->formName).'_block',
                'translation_domain' => $translationDomain,
            ]
        );

        $serviceName = !$this->destinationModule
            ? "form.type.{$this->formNameInService}"
            : $serviceName = "{$this->servicesPrefix}.form.{$this->formNameInService}.type"
        ;

        $this->addService($serviceName, [
            'class' => $classNameDetails->getFullName(),
            'parent' => 'form.type.translatable.aware',
            'public' => true,
            'tags' => [
              'name' => 'form.type',
            ],
        ]);
    }

    private function generateDataConfiguration(): void
    {
        $classNameDetails = $this->generator->createClassNameDetails(
            Str::getShortClassName($this->formName),
            (!$this->destinationModule ? 'Adapter\\' : 'Form\\')."{$this->formName}\\",
            'Configuration'
        );

        $this->generateClass(
            $classNameDetails->getFullName(),
            'DataConfiguration.tpl.php'
        );

        $serviceName = $this->servicesPrefix
            .(!$this->destinationModule ? '.adapter' : '.form')
            .".{$this->formNameInService}.configuration"
        ;

        $this->addService($serviceName, [
            'class' => $classNameDetails->getFullName(),
            'arguments' => [
                '@prestashop.adapter.legacy.configuration',
                '@prestashop.adapter.shop.context',
                '@prestashop.adapter.multistore_feature',
                '@=service("prestashop.adapter.legacy.context").getContext().cookie',
                '@=service("prestashop.adapter.environment").isDebug()',
            ],
        ]);
    }

    private function generateFormDataProvider(): void
    {
        $classNameDetails = $this->generator->createClassNameDetails(
            Str::getShortClassName($this->formName),
            $this->formNamespace,
            'DataProvider'
        );

        $this->generateClass(
            $classNameDetails->getFullName(),
            'DataProvider.tpl.php'
        );

        $serviceName = $this->servicesPrefix
            .(!$this->destinationModule ? '.adapter' : '.form')
            .".{$this->formNameInService}.form_provider"
        ;

        $this->addService($serviceName, [
            'class' => $classNameDetails->getFullName(),
            'arguments' => [
                "@{$this->servicesPrefix}"
                    .(!$this->destinationModule ? '.adapter' : '.form')
                    .".{$this->formNameInService}.configuration",
            ],
        ]);
    }

    private function generateFormDataHandler(): void
    {
        $serviceName = $this->servicesPrefix.".form.{{$this->formNameInService}}.form_data_handler";

        $typeService = !$this->destinationModule
            ? "form.type.{$this->formNameInService}"
            : $serviceName = "{$this->servicesPrefix}.form.{$this->formNameInService}.type"
        ;

        $dataProviderService = $this->servicesPrefix
            .(!$this->destinationModule ? '.adapter' : '.form')
            .".{$this->formNameInService}.form_provider"
        ;

        $this->addService($serviceName, [
            'class' => 'PrestaShop\PrestaShop\Core\Form\Handler',
            'arguments' => [
                '@form.factory',
                '@prestashop.core.hook.dispatcher',
                "@$dataProviderService",
                "@$typeService",
                Str::getShortClassName($this->formName),
            ],
        ]);
    }

    private function generateTemplate(string $translationDomain): void
    {
        $filename = str_replace('_', '-', Str::asSnakeCase(Str::getShortClassName($this->formName))).'-form';

        $templatesPath = (!$this->destinationModule ? 'src/PrestaShopBundle/Resources/' : '').'views/Admin/';

        $routesPrefix = (!$this->destinationModule ? 'admin_' : strtolower($this->destinationModule))
            .Str::asSnakeCase($this->formName)
        ;

        $this->generateFile(
            $templatesPath
                .str_replace(Str::getShortClassName($this->formName), '', str_replace('\\', '/', $this->formName))
                ."/Blocks/$filename.html.twig",
            'form.tpl.php',
            [
                'form_action' => "path('{$routesPrefix}_save')",
                'translation_domain' => $translationDomain,
            ]
        );
    }

    private function generateJavascript(): void
    {
        $page = str_replace('_', '-', Str::asSnakeCase(explode('\\', $this->formName)[0]));

        $this->generateFile(
            (!$this->destinationModule ? "admin-dev/themes/new-theme/js/pages/$page/index.js" : "_dev/js/back/$page.js"),
            'javascript.tpl.php'
        );
    }

    /**
     * {@inheritdoc}
     */
    protected function getDefaultVariablesForGeneration(): array
    {
        $formSnakeCase = implode('_', array_map(function ($classPart) {
            return Str::asSnakeCase($classPart);
        }, explode('\\', $this->formName)));

        $formShortName = Str::getShortClassName($this->formName);

        return array_merge(
            parent::getDefaultVariablesForGeneration(),
            [
                'form_fields' => $this->formFields,
                'form_name' => $this->formName,
                'form_snake_case' => $formSnakeCase,
                'form_short_name' => $formShortName,
                'form_human_words' => Str::asHumanWords($formShortName),
                'form_var' => Str::asLowerCamelCase($formShortName),
            ]
        );
    }
}
