<?= "<?php\n"; ?>

declare(strict_types=1);

namespace <?= $namespace; ?>;

use <?= $psr_4; ?>Entity\<?= $entity_class_name; ?>;
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
final class <?= "$class_name\n"; ?> extends Abstract<?= $entity_class_name; ?>QueryHandler
{
    /**
     * @throws PrestaShopException
     * @throws <?= $entity_class_name; ?>NotFoundException
     */
    public function handle(Get<?= $entity_class_name; ?> $query): <?= "$entity_class_name\n"; ?>
    {
        try {
            $<?= $entity_var; ?> = $this->get<?= $entity_class_name; ?>Entity(
                $query->get<?= $entity_class_name; ?>Id()->getValue()
            );
        } catch (PrestaShopException $e) {
            throw new <?= $entity_class_name; ?>Exception(sprintf(
                'An unexpected error occurred when retrieving <?= $entity_lower_words; ?> with id %s', 
                var_export($query->get<?= $entity_class_name; ?>Id()->getValue(), true)
            ), 0, $e);
        }

        return $<?= $entity_var; ?>;
    }
}
