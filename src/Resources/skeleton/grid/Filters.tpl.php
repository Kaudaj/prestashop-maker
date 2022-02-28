<?php include $php_common_path; ?>

use <?= $psr_4; ?><?= $grid_namespace; ?>Definition\Factory\<?= $entity_class_name; ?>GridDefinitionFactory;
use PrestaShop\PrestaShop\Core\Search\Filters;

final class <?= $class_name; ?> extends Filters
{
    /** @var string */
    protected $filterId = <?= $entity_class_name; ?>GridDefinitionFactory::GRID_ID;

    /**
     * {@inheritdoc}
     */
    public static function getDefaults()
    {
        return [
            'limit' => static::LIST_LIMIT,
            'offset' => 0,
            'orderBy' => null,
            'sortOrder' => null,
            'filters' => [],
        ];
    }
}
