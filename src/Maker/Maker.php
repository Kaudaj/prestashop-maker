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

use Symfony\Bundle\MakerBundle\ConsoleStyle;
use Symfony\Bundle\MakerBundle\DependencyBuilder;
use Symfony\Bundle\MakerBundle\FileManager;
use Symfony\Bundle\MakerBundle\Generator;
use Symfony\Bundle\MakerBundle\InputConfiguration;
use Symfony\Bundle\MakerBundle\Maker\AbstractMaker as SymfonyMaker;
use Symfony\Bundle\MakerBundle\Str;
use Symfony\Bundle\MakerBundle\Util\YamlManipulationFailedException;
use Symfony\Bundle\MakerBundle\Util\YamlSourceManipulator;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Input\InputOption;

abstract class Maker extends SymfonyMaker
{
    /** @var FileManager */
    protected $fileManager;
    /** @var string|null */
    protected $destinationModule;

    /** @var string */
    protected $rootPath;
    /** @var string */
    protected $templatesPath;
    /** @var string */
    protected $psr4;
    /** @var string */
    protected $servicesPrefix;
    /** @var YamlSourceManipulator */
    protected $servicesManipulator;

    /** @var Generator */
    protected $generator;

    public function __construct(FileManager $fileManager, ?string $destinationModule)
    {
        $this->fileManager = $fileManager;
        $this->destinationModule = $destinationModule;

        $this->rootPath = $this->fileManager->getRootDirectory().'/';
        $this->templatesPath = $this->rootPath.'src/Resources/skeleton/';
        $this->psr4 = '';
        if (strpos(__NAMESPACE__, '\\Maker')) {
            $this->psr4 = substr(__NAMESPACE__, 0, strpos(__NAMESPACE__, '\\Maker')).'\\';
        }

        $servicesYaml = $this->fileManager->getFileContents('config/services.yml');
        $this->servicesManipulator = new YamlSourceManipulator($servicesYaml);
    }

    public function configureCommand(Command $command, InputConfiguration $inputConf): void
    {
        $command
            ->addOption('destination-module', 'm', InputOption::VALUE_REQUIRED, 'If the destination is a module, the module class name.')
        ;
    }

    public function generate(InputInterface $input, ConsoleStyle $io, Generator $generator): void
    {
        $destinationModuleInput = $input->getOption('destination-module');
        if ($destinationModuleInput) {
            $this->destinationModule = $destinationModuleInput;
        }

        if (!$this->destinationModule) {
            $this->servicesPrefix = 'prestashop';
        } else {
            $this->servicesPrefix = 'kaudaj.prestashop_maker';
        }

        $this->generator = $generator;
    }

    /**
     * @param array<string, mixed> $extraVariables
     */
    protected function generateClass(string $classFullName, string $skeletonName, array $extraVariables = []): string
    {
        return $this->generator->generateClass(
            $classFullName,
            $this->templatesPath.$skeletonName,
            $this->getDefaultVariablesForGeneration() + $extraVariables
        );
    }

    /**
     * @param array<string, mixed> $extraVariables
     */
    protected function generateFile(string $filePath, string $skeletonName, array $extraVariables = []): void
    {
        $this->generator->generateFile(
            $filePath,
            $this->templatesPath.$skeletonName,
            $this->getDefaultVariablesForGeneration() + $extraVariables
        );
    }

    /**
     * @return array<string, mixed> $variables
     */
    protected function getDefaultVariablesForGeneration(): array
    {
        return [
            'psr_4' => $this->psr4,
            'php_common_path' => "{$this->rootPath}src/Resources/skeleton/common/php-common.tpl.php",
            'destination_is_module' => null !== $this->destinationModule,
            'templates_namespace' => (!$this->destinationModule ? 'PrestaShop/' : 'Modules/'.strtolower($this->destinationModule).'/views/'),
        ];
    }

    // TODO: Add yml path argument when prestashop-maker is a composer package
    /**
     * @param array<string, mixed> $params
     */
    protected function addService(string $serviceName, array $params): void
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

    protected function formatNamespaceForService(string $namespace): string
    {
        return implode('.', array_map(function ($namespacePart) {
            return Str::asSnakeCase($namespacePart);
        }, explode('\\', $namespace)));
    }

    /**
     * @param array<string, mixed> $params
     */
    protected function addRoute(string $routeName, array $params): void
    {
        //TODO: Implement it.
    }

    public function configureDependencies(DependencyBuilder $dependencies): void
    {
    }
}
