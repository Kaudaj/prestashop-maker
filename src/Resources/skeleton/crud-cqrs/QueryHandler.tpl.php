<?php include $php_common_path; ?>

use <?= !$destination_is_module ? 'PrestaShopBundle\\' : $psr_4; ?>Entity\<?= $entity_class_name; ?>;
use <?= $psr_4; ?><?= $domain_namespace; ?>Query\Get<?= $entity_class_name; ?>;
use <?= $psr_4; ?><?= $domain_namespace; ?>Exception\<?= $entity_class_name; ?>Exception;
use <?= $psr_4; ?><?= $domain_namespace; ?>Exception\<?= $entity_class_name; ?>NotFoundException;
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
            $message = sprintf(
                'An unexpected error occurred when retrieving <?= $entity_lower_words; ?> with id %s', 
                var_export($query->get<?= $entity_class_name; ?>Id()->getValue(), true)
            );

            throw new <?= $entity_class_name; ?>Exception($message, 0, $e);
        }

        return $<?= $entity_var; ?>;
    }
}
