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
use Symfony\Bundle\MakerBundle\Maker\AbstractMaker as SymfonyMaker;
use Symfony\Bundle\MakerBundle\Util\YamlManipulationFailedException;
use Symfony\Bundle\MakerBundle\Util\YamlSourceManipulator;
use Symfony\Component\Console\Input\InputInterface;

abstract class Maker extends SymfonyMaker
{
    public const SERVICES_PREFIX = 'kaudaj.prestashop_maker';

    /** @var FileManager */
    protected $fileManager;
    /** @var Generator */
    protected $generator;

    /** @var string */
    protected $rootPath;
    /** @var string */
    protected $templatesPath;
    /** @var string */
    protected $psr4;
    /** @var YamlSourceManipulator */
    protected $servicesManipulator;

    public function __construct(FileManager $fileManager)
    {
        $this->fileManager = $fileManager;

        $this->rootPath = $this->fileManager->getRootDirectory().'/';
        $this->templatesPath = $this->rootPath.'src/Resources/skeleton/';
        $this->psr4 = '';
        if (strpos(__NAMESPACE__, '\\Maker')) {
            $this->psr4 = substr(__NAMESPACE__, 0, strpos(__NAMESPACE__, '\\Maker')).'\\';
        }

        $servicesYaml = $this->fileManager->getFileContents('config/services.yml');
        $this->servicesManipulator = new YamlSourceManipulator($servicesYaml);
    }

    public function generate(InputInterface $input, ConsoleStyle $io, Generator $generator): void
    {
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
            'php_common' => file_get_contents($this->templatesPath.'php-common.tpl.php'),
        ];
    }

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
