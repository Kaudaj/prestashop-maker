<?= "<?php\n"; ?>

namespace <?= $namespace; ?>;

use Exception;
use <?= $psr_4; ?>Domain\<?= $entity_class_name; ?>\Command\Delete<?= $entity_class_name; ?>Command;
use <?= $psr_4; ?>Domain\<?= $entity_class_name; ?>\Exception\CannotDelete<?= $entity_class_name; ?>Exception;
use <?= $psr_4; ?>Domain\<?= $entity_class_name; ?>\Exception\<?= $entity_class_name; ?>Exception;

/**
 * Class <?= $class_name; ?> is responsible for deleting <?= $entity_lower_words; ?> data.
 *
 * @internal
 */
final class Delete<?= $entity_class_name; ?>Handler extends Abstract<?= $entity_class_name; ?>Handler
{
    /**
     * @throws <?= $entity_class_name; ?>Exception
     */
    public function handle(Delete<?= $entity_class_name; ?>Command $command): void
    {
        $<?= $entity_var; ?> = $this->get<?= $entity_class_name; ?>Entity(
            $command->get<?= $entity_class_name; ?>Id()->getValue()
        );

        try {
            $this->entityManager->remove($<?= $entity_var; ?>);
            $this->entityManager->flush();
        } catch (Exception $exception) {
            throw new CannotDelete<?= $entity_class_name; ?>Exception('An unexpected error occurred when deleting <?= $entity_lower_words; ?>', 0, $exception);
        }
    }
}
