<?= "<?php\n"; ?>

namespace <?= $namespace; ?>;

use Doctrine\ORM\EntityManager;
use <?= $psr_4; ?>Domain\<?= $entity_class_name; ?>\Exception\<?= $entity_class_name; ?>ConstraintException;
use <?= $psr_4; ?>Domain\<?= $entity_class_name; ?>\Exception\<?= $entity_class_name; ?>NotFoundException;
use <?= $psr_4; ?>Entity\<?= $entity_class_name; ?>;
use <?= $psr_4; ?>Repository\<?= $entity_class_name; ?>Repository;
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
     * @var ObjectRepository
     */
    protected $entityRepository;

    public function __construct(EntityManager $entityManager)
    {
        $this->entityManager = $entityManager;

        /** @var <?= $entity_class_name; ?>Repository */
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
