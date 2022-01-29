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

namespace Kaudaj\PrestaShopMaker\Builder\Grid;

use Symfony\Bundle\MakerBundle\Str;
use Symfony\Bundle\MakerBundle\Util\ClassSourceManipulator;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;

final class ControllerBuilder
{
    /**
     * @var string
     */
    private $entityClassName;

    public function __construct(string $entityClassName)
    {
        $this->entityClassName = $entityClassName;
    }

    public function addIndexAction(ClassSourceManipulator $manipulator): void
    {
        $indexActionBuilder = $manipulator->createMethodBuilder('indexAction', 'Response', false, [
            'Display '.strtolower(Str::asHumanWords($this->entityClassName)).' grid',
            "@AdminSecurity(\"is_granted(['read'], request.get('_legacy_controller'))\")",
        ]);

        $manipulator->addUseStatementIfNecessary(Response::class);
        $manipulator->addUseStatementIfNecessary(Request::class);

        $indexActionBuilder->addParam(
            (new \PhpParser\Builder\Param('request'))->setType('Request')
        );
        $indexActionBuilder->addParam(
            (new \PhpParser\Builder\Param('filters'))->setType("{$this->entityClassName}Filters")
        );

        $servicesPrefix = 'kaudaj.prestashop_maker';
        $entitySnakeCase = Str::asSnakeCase($this->entityClassName);
        $entityPluralVar = Str::asLowerCamelCase(
            Str::singularCamelCaseToPluralCamelCase(Str::asCamelCase($this->entityClassName))
        );

        $manipulator->addMethodBody($indexActionBuilder, <<<CODE
<?php
\${$entityPluralVar}GridFactory = \$this->get('$servicesPrefix.grid.{$entitySnakeCase}_grid_factory');
\${$entityPluralVar}Grid = \${$entityPluralVar}GridFactory->getGrid(\$filters);
CODE
        );
        $indexActionBuilder->addStmt($manipulator->createMethodLevelBlankLine());
        $manipulator->addMethodBody($indexActionBuilder, <<<CODE
<?php
//TODO: Set template domain
return \$this->render('@TemplateDomain/{$this->entityClassName}/index.html.twig', [
    '{$entityPluralVar}Grid' => \$this->presentGrid(\${$entityPluralVar}Grid),
]);
CODE
        );

        $manipulator->addMethodBuilder($indexActionBuilder);
    }
}
