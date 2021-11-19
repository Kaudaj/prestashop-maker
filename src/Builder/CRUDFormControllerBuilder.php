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

namespace Kaudaj\PrestaShopMaker\Builder;

use Symfony\Bundle\MakerBundle\Str;
use Symfony\Bundle\MakerBundle\Util\ClassSourceManipulator;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;

/**
 * @internal
 */
final class CRUDFormControllerBuilder
{
    /**
     * @var string
     */
    private $entityClassName;

    public function __construct(string $entityClassName)
    {
        $this->entityClassName = $entityClassName;
    }

    public function addCreateAction(ClassSourceManipulator $manipulator): void
    {
        $createActionBuilder = $manipulator->createMethodBuilder('createAction', 'Response', false, [
            'Show '.Str::asHumanWords($this->entityClassName).' create form & handle processing of it',
            "@AdminSecurity(\"is_granted(['create'], request.get('_legacy_controller'))\")",
        ]);

        $manipulator->addUseStatementIfNecessary(Response::class);
        $manipulator->addUseStatementIfNecessary(Request::class);

        $createActionBuilder->addParam(
            (new \PhpParser\Builder\Param('request'))->setType('Request')
        );

        $entityVar = Str::asLowerCamelCase($this->entityClassName);
        $entitySnakeCase = Str::asSnakeCase($this->entityClassName);
        $servicesPrefix = 'kaudaj.prestashop_maker';

        $manipulator->addMethodBody($createActionBuilder, <<<CODE
<?php
\${$entityVar}FormBuilder = \$this->get('$servicesPrefix.form.$entitySnakeCase.{$entitySnakeCase}_form_builder');
\${$entityVar}Form = \${$entityVar}FormBuilder->getForm();
CODE
        );
        $createActionBuilder->addStmt($manipulator->createMethodLevelBlankLine());
        $manipulator->addMethodBody($createActionBuilder, <<<CODE
<?php
\${$entityVar}Form->handleRequest(\$request);
CODE
        );
        $createActionBuilder->addStmt($manipulator->createMethodLevelBlankLine());
        $manipulator->addMethodBody($createActionBuilder, <<<CODE
<?php
\${$entityVar}FormHandler = \$this->get('$servicesPrefix.form.$entitySnakeCase.{$entitySnakeCase}_form_handler');
\$result = \${$entityVar}FormHandler->handle(\${$entityVar}Form);
CODE
        );
        $createActionBuilder->addStmt($manipulator->createMethodLevelBlankLine());
        $manipulator->addMethodBody($createActionBuilder, <<<CODE
<?php
if (null !== \$result->getIdentifiableObjectId()) {
    \$this->addFlash('success', \$this->trans('Successful creation.', 'Admin.Notifications.Success'));
    
    return \$this->redirectToRoute('admin_{$entitySnakeCase}_index');
}
CODE
        );
        $createActionBuilder->addStmt($manipulator->createMethodLevelBlankLine());
        $manipulator->addMethodBody($createActionBuilder, <<<CODE
<?php
//TODO: Set template domain
return \$this->render('@TemplateDomain/{$this->entityClassName}/create.html.twig', [
    '{$entityVar}Form' => \${$entityVar}Form->createView(),
]);
CODE
        );

        $manipulator->addMethodBuilder($createActionBuilder);
    }

    public function addEditAction(ClassSourceManipulator $manipulator): void
    {
        $editActionBuilder = $manipulator->createMethodBuilder('editAction', 'Response', false, [
            'Show '.Str::asHumanWords($this->entityClassName).' edit form & handle processing of it',
            "@AdminSecurity(\"is_granted(['update'], request.get('_legacy_controller'))\")",
        ]);

        $manipulator->addUseStatementIfNecessary(Response::class);
        $manipulator->addUseStatementIfNecessary(Request::class);

        $entityVar = Str::asLowerCamelCase($this->entityClassName);
        $entitySnakeCase = Str::asSnakeCase($this->entityClassName);
        $servicesPrefix = 'kaudaj.prestashop_maker';

        $editActionBuilder->addParams([
            (new \PhpParser\Builder\Param($entityVar.'Id'))->setType('int'),
            (new \PhpParser\Builder\Param('request'))->setType('Request'),
        ]);

        $manipulator->addMethodBody($editActionBuilder, <<<CODE
<?php
\${$entityVar}FormBuilder = \$this->get('$servicesPrefix.form.$entitySnakeCase.{$entitySnakeCase}_form_builder');
\${$entityVar}Form = \${$entityVar}FormBuilder->getFormFor(\${$entityVar}Id);
CODE
        );
        $editActionBuilder->addStmt($manipulator->createMethodLevelBlankLine());
        $manipulator->addMethodBody($editActionBuilder, <<<CODE
<?php
\${$entityVar}Form->handleRequest(\$request);
CODE
        );
        $editActionBuilder->addStmt($manipulator->createMethodLevelBlankLine());
        $manipulator->addMethodBody($editActionBuilder, <<<CODE
<?php
\${$entityVar}FormHandler = \$this->get('$servicesPrefix.form.$entitySnakeCase.{$entitySnakeCase}_form_handler');
\$result = \${$entityVar}FormHandler->handleFor(\${$entityVar}Id, \${$entityVar}Form);
CODE
        );
        $editActionBuilder->addStmt($manipulator->createMethodLevelBlankLine());
        $manipulator->addMethodBody($editActionBuilder, <<<CODE
<?php
if (null !== \$result->getIdentifiableObjectId()) {
    \$this->addFlash('success', \$this->trans('Successful creation.', 'Admin.Notifications.Success'));
    
    return \$this->redirectToRoute('admin_{$entitySnakeCase}_index');
}
CODE
        );
        $editActionBuilder->addStmt($manipulator->createMethodLevelBlankLine());
        $manipulator->addMethodBody($editActionBuilder, <<<CODE
<?php
return \$this->render('@TemplateDomain/{$this->entityClassName}/edit.html.twig', [
    '{$entityVar}Form' => \${$entityVar}Form->createView(),
]);
CODE
        );

        $manipulator->addMethodBuilder($editActionBuilder);
    }
}
