<?php include $php_common_path; ?>

use PrestaShop\PrestaShop\Core\Grid\Query\AbstractDoctrineQueryBuilder;
use PrestaShop\PrestaShop\Core\Grid\Search\SearchCriteriaInterface;
use Doctrine\DBAL\Connection;

final class <?= $class_name; ?> extends AbstractDoctrineQueryBuilder
{
    /**
     * @var DoctrineSearchCriteriaApplicatorInterface
     */
    private $searchCriteriaApplicator;

    /**
     * @var int
     */
    private $contextLangId;

    /**
     * @var int
     */
    private $contextShopId;

    public function __construct(
        Connection $connection, 
        string $dbPrefix,
        DoctrineSearchCriteriaApplicator $searchCriteriaApplicator,
        int $contextLangId, 
        int $contextShopId
    ) {
        parent::__construct($connection, $dbPrefix);

        $this->searchCriteriaApplicator = $searchCriteriaApplicator;
        $this->contextLangId = $contextLangId;
        $this->contextShopId = $contextShopId;
    }

    public function getSearchQueryBuilder(SearchCriteriaInterface $searchCriteria)
    {
        $qb = $this->getQueryBuilder($searchCriteria->getFilters());

        $qb->select('<?= $select_statement; ?>');

        $this->searchCriteriaApplicator
            ->applyPagination($searchCriteria, $qb)
            ->applySorting($searchCriteria, $qb)
        ;
        
        return $qb;
    }

    public function getCountQueryBuilder(SearchCriteriaInterface $searchCriteria)
    {
        $qb = $this->getQueryBuilder($searchCriteria->getFilters());
        $qb->select('COUNT(<?= $table_alias; ?>.id_<?= $entity_snake; ?>)');

        return $qb;
    }

    /**
     * @param array<string, mixed> $filters
     */
    private function getQueryBuilder(array $filters): QueryBuilder
    {
        $availableFilters = [];

        $qb = $this->connection
            ->createQueryBuilder()
            ->from($this->dbPrefix.'<?= $entity_snake; ?>', '<?= $table_alias; ?>')
            ->setParameter('context_lang_id', $this->contextLangId)
            ->setParameter('context_shop_id', $this->contextShopId)
        ;

        foreach ($filters as $filterName => $value) {
            if (!in_array($filterName, $availableFilters, true)) {
                continue;
            }

            switch ($filterName) {
                case 'id_<?= $entity_snake; ?>':
                    $qb->andWhere('<?= $table_alias; ?>.`' . $filterName . '` = :' . $filterName);
                    $qb->setParameter($filterName, $value);

                    break;
                default:
                    $qb->andWhere('<?= $table_alias; ?>.`' . $filterName . '` LIKE :' . $filterName);
                    $qb->setParameter($filterName, '%' . $value . '%');
            }
        }

        return $qb;
    }
}