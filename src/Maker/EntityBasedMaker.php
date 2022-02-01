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

use Symfony\Bundle\MakerBundle\ConsoleStyle;
use Symfony\Bundle\MakerBundle\Doctrine\DoctrineHelper;
use Symfony\Bundle\MakerBundle\FileManager;
use Symfony\Bundle\MakerBundle\Generator;
use Symfony\Bundle\MakerBundle\InputConfiguration;
use Symfony\Bundle\MakerBundle\Str;
use Symfony\Bundle\MakerBundle\Validator;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputArgument;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Question\Question;

abstract class EntityBasedMaker extends Maker
{
    /**
     * @var string
     */
    protected $entityClassName;

    /**
     * @var DoctrineHelper
     */
    protected $entityHelper;

    public function __construct(FileManager $fileManager, DoctrineHelper $entityHelper)
    {
        parent::__construct($fileManager);

        $this->entityHelper = $entityHelper;
    }

    public function configureCommand(Command $command, InputConfiguration $inputConf): void
    {
        $command
            ->addArgument('entity-class', InputArgument::OPTIONAL, 'Please enter the class name of the related entity')
        ;

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
        parent::generate($input, $io, $generator);

        $this->entityClassName = $input->getArgument('entity-class');
    }

    /**
     * @return \ReflectionProperty[]
     */
    protected function getEntityProperties(): array
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

    /**
     * @return array<string, mixed> $variables
     */
    protected function getDefaultVariablesForGeneration(): array
    {
        $entityPlural = Str::singularCamelCaseToPluralCamelCase(Str::asCamelCase($this->entityClassName));

        $entityProperties = array_map(function (\ReflectionProperty $property) {
            return $property->getName();
        }, $this->getEntityProperties());

        return parent::getDefaultVariablesForGeneration() + [
            'entity_class_name' => $this->entityClassName,
            'entity_var' => Str::asLowerCamelCase($this->entityClassName),
            'entity_snake' => Str::asSnakeCase($this->entityClassName),
            'entity_human_words' => Str::asHumanWords($this->entityClassName),
            'entity_lower_words' => strtolower(Str::asHumanWords($this->entityClassName)),
            'entity_plural_var' => Str::asLowerCamelCase($entityPlural),
            'entity_plural_human_words' => Str::asHumanWords($entityPlural),
            'entity_plural_lower_words' => strtolower(Str::asHumanWords($entityPlural)),
            'entity_properties' => $entityProperties,
        ];
    }
}
