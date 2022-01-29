<?= "<?php\n"; ?>

namespace <?= $namespace; ?>;

use <?= $psr_4; ?>Domain\<?= $entity_class_name; ?>\Query\Get<?= $entity_class_name; ?>;
use <?= $psr_4; ?>Domain\<?= $entity_class_name; ?>\Exception\<?= $entity_class_name; ?>Exception;
use <?= $psr_4; ?>Domain\<?= $entity_class_name; ?>\Exception\<?= $entity_class_name; ?>NotFoundException;
use PrestaShopException;
use Doctrine\ORM\EntityManagerInterface;

/**
 * Class <?= $class_name; ?> is responsible for getting <?= $entity_lower_words; ?> entity.
 *
 * @internal
 */
final class <?= "$class_name\n"; ?>
{
    /**
     * @throws PrestaShopException
     * @throws <?= $entity_class_name; ?>NotFoundException
     */
    public function handle(Get<?= $entity_class_name; ?> $query): <?= "$entity_class_name\n"; ?>
    {
        try {
            /** @var EntityManagerInterface $entityManager */
            $entityManager = $this->container->get('doctrine.orm.entity_manager');
            $<?= $entity_var; ?>Repository = $entityManager->getRepository(<?= $entity_class_name; ?>::class);

            $<?= $entity_var; ?> = $<?= $entity_var; ?>Repository->findById($query->get<?= $entity_class_name; ?>Id()->getValue());

            if (!$<?= $entity_var; ?>) {
                throw new <?= $entity_class_name; ?>NotFoundException(sprintf(
                    '<?= $entity_human_words; ?> object with id %s was not found',
                    var_export($query->get<?= $entity_class_name; ?>Id()->getValue(), 
                    true)
                ));
            }
        } catch (PrestaShopException $e) {
            throw new <?= $entity_class_name; ?>Exception(sprintf(
                'An unexpected error occurred when retrieving <?= $entity_lower_words; ?> with id %s', 
                var_export($query->get<?= $entity_class_name; ?>Id()->getValue(), true)
            ), 0, $e);
        }

        return $<?= "$entity_var\n"; ?>;
    }
}
