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

use PhpParser\Builder\Param;
use ReflectionProperty;
use Symfony\Bundle\MakerBundle\Str;
use Symfony\Bundle\MakerBundle\Util\ClassSourceManipulator;

final class QueryResultBuilder
{
    /**
     * @var string
     */
    private $entityClassName;

    /**
     * @var ReflectionProperty[]
     */
    private $entityProperties;

    /**
     * @param ReflectionProperty[] $entityProperties
     */
    public function __construct(string $entityClassName, array $entityProperties)
    {
        $this->entityClassName = $entityClassName;
        $this->entityProperties = $entityProperties;
    }

    public function addProperties(ClassSourceManipulator $manipulator): void
    {
        foreach ($this->entityProperties as $property) {
            $manipulator->addProperty(
                $property->getName(),
                $property->getType() ? ["@var {$property->getType()->getName()}"] : [''],
                $property->getDeclaringClass()->getDefaultProperties()[$property->getName()] ?? null
            );
        }
    }

    public function addConstructor(ClassSourceManipulator $manipulator): void
    {
        $entityVar = Str::asLowerCamelCase($this->entityClassName);

        $params = [(new Param("{$entityVar}Id"))->setType('integer')->getNode()];

        foreach ($this->entityProperties as $property) {
            $param = new Param($property->getName());
            if ($property->getType()) {
                $param->setType($property->getType()->getName());
            }
            $params[] = $param->getNode();
        }

        $paramsAssignements = '';
        foreach ($this->entityProperties as $property) {
            $name = $property->getName();
            $paramsAssignements .= "\$this->$name = \$$name;\n";
        }

        $body = <<<CODE
<?php
//TODO: Add throws annotation since it is not automatic at the moment
\$this->{$entityVar}Id = new {$this->entityClassName}Id(\${$entityVar}Id);
$paramsAssignements
CODE
        ;

        $manipulator->addConstructor($params, $body);
    }
}
