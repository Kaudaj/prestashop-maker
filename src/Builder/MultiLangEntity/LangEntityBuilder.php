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

namespace Kaudaj\PrestaShopMaker\Builder\MultiLangEntity;

use PhpParser\Node;
use PhpParser\Node\Stmt;
use PhpParser\NodeTraverser;
use PhpParser\NodeVisitorAbstract;
use PhpParser\ParserFactory;
use PhpParser\PrettyPrinter;
use Symfony\Bundle\MakerBundle\Doctrine\EntityRelation;
use Symfony\Bundle\MakerBundle\Str;
use Symfony\Bundle\MakerBundle\Util\ClassNameDetails;
use Symfony\Bundle\MakerBundle\Util\ClassSourceManipulator;

final class LangEntityBuilder
{
    /**
     * @var ClassNameDetails
     */
    private $entityClassNameDetails;

    public function __construct(ClassNameDetails $entityClassNameDetails)
    {
        $this->entityClassNameDetails = $entityClassNameDetails;
    }

    public function removeIdProperty(string $sourceCode): string
    {
        $traverser = new NodeTraverser();
        $traverser->addVisitor(new RemoveIdVisitor());

        $parser = (new ParserFactory())->create(ParserFactory::PREFER_PHP7);

        $ast = $parser->parse($sourceCode);
        if (!$ast) {
            return $sourceCode;
        }
        $ast = $traverser->traverse($ast);

        $prettyPrinter = new PrettyPrinter\Standard();

        return $prettyPrinter->prettyPrintFile($ast);
    }

    public function addEntityRelation(
        ClassSourceManipulator $entityManipulator,
        ClassSourceManipulator $langEntityManipulator
    ): void {
        $entityFullName = $this->entityClassNameDetails->getFullName();

        $relation = new EntityRelation(
            EntityRelation::MANY_TO_ONE,
            "{$entityFullName}Lang",
            "{$entityFullName}"
        );

        $entityName = $this->entityClassNameDetails->getShortName();
        $relation->setOwningProperty(Str::asLowerCamelCase($entityName));
        $relation->setInverseProperty(Str::asLowerCamelCase($entityName).'Langs');

        $langEntityManipulator->addManyToOneRelation($relation->getOwningRelation());
        $entityManipulator->addOneToManyRelation($relation->getInverseRelation());
    }

    public function addLangRelation(ClassSourceManipulator $manipulator): void
    {
        $entityFullName = $this->entityClassNameDetails->getFullName();

        $relation = new EntityRelation(
            EntityRelation::MANY_TO_ONE,
            "{$entityFullName}Lang",
            "PrestaShopBundle\Entity\Lang"
        );

        $relation->setOwningProperty('lang');
        $relation->setMapInverseRelation(false);

        $manipulator->addManyToOneRelation($relation->getOwningRelation());
    }
}

class RemoveIdVisitor extends NodeVisitorAbstract
{
    /**
     * @return int|Node|Node[]|null
     */
    public function leaveNode(Node $node)
    {
        switch (get_class($node)) {
            case Stmt\Property::class:
                if (1 === count($node->props)) {
                    if ('id' == $node->props[0]->name) {
                        return NodeTraverser::REMOVE_NODE;
                    }

                    return null;
                } else {
                    foreach ($node->props as $i => $prop) {
                        if ('id' == $prop->name) {
                            unset($node->props[$i]);
                            break;
                        }
                    }

                    return $node;
                }
                // no break
            case Stmt\ClassMethod::class:
                if ('getId' == $node->name) {
                    return NodeTraverser::REMOVE_NODE;
                }

                return null;
        }

        return null;
    }
}
