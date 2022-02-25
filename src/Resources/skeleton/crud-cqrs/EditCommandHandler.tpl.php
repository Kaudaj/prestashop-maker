<?php include $php_common_path; ?>

use <?= $psr_4; ?><?= $domain_namespace; ?>Command\Edit<?= $entity_class_name; ?>Command;
use <?= $psr_4; ?><?= $domain_namespace; ?>Exception\<?= $entity_class_name; ?>Exception;
use <?= $psr_4; ?><?= $domain_namespace; ?>Exception\CannotUpdate<?= $entity_class_name; ?>Exception;
use PrestaShopDatabaseException;
use PrestaShopException;
use Doctrine\ORM\EntityManagerInterface;

/**
 * Class <?= $class_name; ?> is responsible for editing <?= $entity_lower_words; ?> data.
 *
 * @internal
 */
final class <?= $class_name; ?> extends Abstract<?= $entity_class_name; ?>CommandHandler
{
    /**
     * @throws <?= $entity_class_name; ?>Exception
     */
    public function handle(Edit<?= $entity_class_name; ?>Command $command): void
    {
        try {
            $entity = $this->get<?= $entity_class_name; ?>Entity(
                $command->get<?= $entity_class_name; ?>Id()->getValue()
            );

<?php foreach ($entity_properties as $property) { ?>
            if (null !== $command->get<?= ucfirst($property); ?>()) {
                $<?= $entity_var; ?>->set<?= ucfirst($property); ?>($command->get<?= ucfirst($property); ?>());
            }
<?php } ?>

            $this->entityManager->persist($entity);
            $this->entityManager->flush();
        } catch (PrestaShopException $exception) {
            throw new CannotUpdate<?= $entity_class_name; ?>Exception('An unexpected error occurred when editing <?= $entity_lower_words; ?>', 0, $exception);
        }
    }
}
