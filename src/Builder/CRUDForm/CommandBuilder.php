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

namespace Kaudaj\PrestaShopMaker\Builder\CRUDForm;

use ReflectionProperty;
use Symfony\Bundle\MakerBundle\Util\ClassSourceManipulator;

final class CommandBuilder
{
    /**
     * @var ReflectionProperty[]
     */
    private $entityProperties;

    /**
     * @param ReflectionProperty[] $entityProperties
     */
    public function __construct(array $entityProperties)
    {
        $this->entityProperties = $entityProperties;
    }

    public function addProperties(ClassSourceManipulator $manipulator): void
    {
        foreach ($this->entityProperties as $property) {
            $manipulator->addProperty(
                $property->getName(),
                ["@var {$property->getType()}"],
                $property->getDeclaringClass()->getDefaultProperties()[$property->getName()] ?? null
            );
        }
    }

    public function addSetterMethods(ClassSourceManipulator $manipulator): void
    {
        foreach ($this->entityProperties as $property) {
            $hasDefaultValue = null === $property->getDeclaringClass()->getDefaultProperties()[$property->getName()];
            $manipulator->addSetter($property->getName(), $property->getType(), $hasDefaultValue);
        }
    }
}
