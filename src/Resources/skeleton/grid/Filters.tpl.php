<?= "<?php\n"; ?>

namespace <?= $namespace; ?>;

use PrestaShop\PrestaShop\Core\Grid\Definition\Factory\CmsPageDefinitionFactory;
use PrestaShop\PrestaShop\Core\Search\Filters;

final class <?= $class_name; ?> extends Filters
{
    /** @var string */
    protected $filterId = <?= $entity_class_name; ?> DefinitionFactory::GRID_ID;

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
