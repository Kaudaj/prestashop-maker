<?= "<?php\n"; ?>

declare(strict_types=1);

namespace <?= $namespace; ?>;

use <?= $psr_4; ?>Grid\Definition\Factory\<?= $entity_class_name; ?>GridDefinitionFactory;
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
