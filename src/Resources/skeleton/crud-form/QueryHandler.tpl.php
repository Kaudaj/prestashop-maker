<?= "<?php\n"; ?>

namespace <?= $namespace; ?>;

use <?= $psr_4; ?>Domain\<?= $entity_class_name; ?>\Query\Get<?= $entity_class_name; ?>ForEditing;;
use <?= $psr_4; ?>Domain\<?= $entity_class_name; ?>\QueryResult\Editable<?= $entity_class_name; ?>;;
use Doctrine\ORM\EntityManagerInterface;

/**
 * Class <?= $class_name; ?> is responsible for getting the data for <?= $entity_lower_words; ?> edit page.
 *
 * @internal
 */
final class <?= $class_name; ?>
{
    /**
     * @param Get<?= $entity_class_name; ?>ForEditing $query
     *
     * @return Editable<?= $entity_class_name; ?>
     */
    public function handle(Get<?= $entity_class_name; ?>ForEditing $query)
    {
        try {
            /** @var EntityManagerInterface $entityManager */
            $entityManager = $this->container->get('doctrine.orm.entity_manager');
            $<?= $entity_var; ?>Repository = $entityManager->getRepository(<?= $entity_class_name; ?>::class);

            $<?= $entity_var; ?> = $<?= $entity_var; ?>Repository->findById($query->get<?= $entity_class_name; ?>Id()->getValue());

            /*if (!$<?= $entity_var; ?>) {
                throw new <?= $entity_class_name; ?>NotFoundException(sprintf(
                    '<?= $entity_human_words; ?> object with id %s was not found',
                    var_export($query->get<?= $entity_class_name; ?>Id()->getValue(), 
                    true)
                ));
            }*/

            $editable<?= $entity_class_name; ?> = new Editable<?= $entity_class_name; ?>(
                $query->get<?= $entity_class_name; ?>Id()->getValue(),
                <?php foreach ($entity_get_methods as $get_method) { ?>
                    $<?= $entity_var; ?>-><?= $get_method; ?>(),
                <?php } ?>
            );
        } catch (PrestaShopException $e) {
            throw new <?= $entity_class_name; ?>Exception(sprintf(
                'An unexpected error occurred when retrieving <?= $entity_lower_words; ?> with id %s', 
                var_export($query->get<?= $entity_class_name; ?>Id()->getValue(), true)
            ), 0, $e);
        }

        return $editable<?= $entity_class_name; ?>;
    }
}
