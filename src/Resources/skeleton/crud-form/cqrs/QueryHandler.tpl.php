<?= $php_common; ?>

use <?= $psr_4; ?>Domain\<?= $entity_class_name; ?>\Query\Get<?= $entity_class_name; ?>ForEditing;
use <?= $psr_4; ?>Domain\<?= $entity_class_name; ?>\QueryResult\Editable<?= $entity_class_name; ?>;
use <?= $psr_4; ?>Domain\<?= $entity_class_name; ?>\Exception\<?= $entity_class_name; ?>Exception;
use <?= $psr_4; ?>Domain\<?= $entity_class_name; ?>\Exception\<?= $entity_class_name; ?>NotFoundException;
use PrestaShopException;
use Doctrine\ORM\EntityManagerInterface;

/**
 * Class <?= $class_name; ?> is responsible for getting the data for <?= $entity_lower_words; ?> edit page.
 *
 * @internal
 */
final class <?= "$class_name\n"; ?>
{
    /**
     * @throws PrestaShopException
     * @throws <?= $entity_class_name; ?>NotFoundException
     */
    public function handle(Get<?= $entity_class_name; ?>ForEditing $query): Editable<?= "$entity_class_name\n"; ?>
    {
        try {
            $<?= $entity_var; ?> = $this->get<?= $entity_class_name; ?>Entity(
                $query->get<?= $entity_class_name; ?>Id()->getValue()
            );

            $editable<?= $entity_class_name; ?> = new Editable<?= $entity_class_name; ?>(
                $query->get<?= $entity_class_name; ?>Id()->getValue(),
<?php for ($i = 0; $i < count($entity_get_methods); ++$i) { ?>
                $<?= $entity_var; ?>-><?= $entity_get_methods[$i]; ?>()<?php if ($i < count($entity_get_methods) - 1) { ?>,<?php } ?><?= "\n"; ?>
<?php } ?>
            );
        } catch (PrestaShopException $e) {
            throw new <?= $entity_class_name; ?>Exception(sprintf(
                'An unexpected error occurred when retrieving <?= $entity_lower_words; ?> with id %s', 
                var_export($query->get<?= $entity_class_name; ?>Id()->getValue(), true)
            ), 0, $e);
        }

        return $editable<?= "$entity_class_name\n"; ?>;
    }
}
