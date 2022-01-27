<?= "<?php\n"; ?>

namespace <?= $namespace; ?>;

use PrestaShop\PrestaShop\Core\Grid\Query\AbstractDoctrineQueryBuilder;

final class <?= $class_name; ?> extends AbstractDoctrineQueryBuilder
{
    /**
     * @var int
     */
    private $contextLangId;

    /**
     * @var int
     */
    private $contextShopId;

    /**
     * @param Connection $connection
     * @param string $dbPrefix
     * @param int $contextLangId
     * @param int $contextShopId
     */
    public function __construct(Connection $connection, $dbPrefix, $contextLangId, $contextShopId)
    {
        parent::__construct($connection, $dbPrefix);

        $this->contextLangId = $contextLangId;
        $this->contextShopId = $contextShopId;
    }

    public function getSearchQueryBuilder(SearchCriteriaInterface $searchCriteria)
    {
        $qb = $this->getBaseQuery();

        <?php
        $tableAlias = implode(
            array_map(
                function ($word) { return substr($word, 0, 1); },
                explode('_', $entity_snake)
            )
        );

        $selectStm = "$tableAlias.id_$entitySnake";
        foreach ($entity_properties as $property) {
            $selectStm .= "$tableAlias.$property";
        }
        ?>

        $qb->select(<?= $selectStm; ?>)
            ->orderBy(
                $searchCriteria->getOrderBy(),
                $searchCriteria->getOrderWay()
            )
            ->setFirstResult($searchCriteria->getOffset())
            ->setMaxResults($searchCriteria->getLimit());
    
        foreach ($searchCriteria->getFilters() as $filterName => $filterValue) {
            if ('id_<?= $entity_snake; ?>' === $filterName) {
                $qb->andWhere("<?= $tableAlias; ?>.id_<?= $entity_snake; ?> = :$filterName");
                $qb->setParameter($filterName, $filterValue);

                continue;
            }

            $qb->andWhere("$filterName LIKE :$filterName");
            $qb->setParameter($filterName, '%'.$filterValue.'%');
        }

        return $qb;
    }
    
    public function getCountQueryBuilder(SearchCriteriaInterface $searchCriteria)
    {
        $qb = $this->getBaseQuery();
        $qb->select('COUNT(<?= $tableAlias; ?>.id_<?= $entity_snake; ?>)');

        return $qb;
    }
    
    private function getBaseQuery()
    {
        return $this->connection
            ->createQueryBuilder()
            ->from($this->dbPrefix.'<?= $entity_snake; ?>', '<?= $tableAlias; ?>')
            ->setParameter('context_lang_id', $this->contextLangId)
            ->setParameter('context_shop_id', $this->contextShopId)
        ;
    }
}