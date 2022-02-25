<?php include $php_common_path; ?>

use Doctrine\ORM\EntityManager;
use <?= $psr_4; ?><?= $domain_namespace; ?>Exception\<?= $entity_class_name; ?>NotFoundException;
use <?= $psr_4; ?><?= !$destination_is_module ? 'PrestaShopBundle\\' : ''; ?>Entity\<?= $entity_class_name; ?>;
use Doctrine\Persistence\ObjectRepository;
use PrestaShopDatabaseException;
use PrestaShopException;

/**
 * Class <?= $class_name; ?>.
 */
abstract class <?= $class_name; ?>
{
    /**
     * @var EntityManager
     */
    protected $entityManager;

    /**
     * @var ObjectRepository<<?= $entity_class_name; ?>>
     */
    protected $entityRepository;

    public function __construct(EntityManager $entityManager)
    {
        $this->entityManager = $entityManager;

        /** @var ObjectRepository<<?= $entity_class_name; ?>> */
        $entityRepository = $this->entityManager->getRepository(<?= $entity_class_name; ?>::class);

        $this->entityRepository = $entityRepository;
    }

    /**
     * Gets <?= $entity_lower_words; ?> entity.
     *
     * @throws <?= $entity_class_name; ?>NotFoundException
     * @throws PrestaShopException
     * @throws PrestaShopDatabaseException
     */
    protected function get<?= $entity_class_name; ?>Entity(int $id): <?= $entity_class_name; ?>
    {
        /** @var <?= $entity_class_name; ?>|null */
        $<?= $entity_var; ?> = $this->entityRepository->find($id);

        if (!$<?= $entity_var; ?>) {
            throw new <?= $entity_class_name; ?>NotFoundException();
        }

        return $<?= $entity_var; ?>;
    }
}
