<?php include $php_common_path; ?>

use PrestaShopException;
use <?= $psr_4; ?><?= !$destination_is_module ? 'PrestaShopBundle\\' : ''; ?>Entity\<?= $entity_class_name; ?>;
use <?= $psr_4; ?><?= $domain_namespace; ?>Command\Add<?= $entity_class_name; ?>Command;
use <?= $psr_4; ?><?= $domain_namespace; ?>Exception\<?= $entity_class_name; ?>Exception;
use <?= $psr_4; ?><?= $domain_namespace; ?>Exception\CannotAdd<?= $entity_class_name; ?>Exception;
use <?= $psr_4; ?><?= $domain_namespace; ?>ValueObject\<?= $entity_class_name; ?>Id;

/**
 * Class <?= $class_name; ?> is used for adding <?= $entity_lower_words; ?> data.
 */
final class <?= $class_name; ?> extends Abstract<?= $entity_class_name; ?>CommandHandler
{
    /**
     * @throws CannotAdd<?= $entity_class_name; ?>Exception
     * @throws <?= $entity_class_name; ?>Exception
     */
    public function handle(Add<?= $entity_class_name; ?>Command $command): <?= $entity_class_name; ?>Id
    {
        try {
            $entity = new <?= $entity_class_name; ?>();

<?php foreach ($entity_properties as $property) { ?>
            if (null !== $command->get<?= ucfirst($property); ?>()) {
                $<?= $entity_var; ?>->set<?= ucfirst($property); ?>($command->get<?= ucfirst($property); ?>());
            }
<?php } ?>

            $this->entityManager->persist($entity);
            $this->entityManager->flush();
        } catch (PrestaShopException $exception) {
            throw new <?= $entity_class_name; ?>Exception('An unexpected error occurred when adding <?= $entity_lower_words; ?>', 0, $exception);
        }

        return new <?= $entity_class_name; ?>Id((int) $entity->getId());
    }
}
