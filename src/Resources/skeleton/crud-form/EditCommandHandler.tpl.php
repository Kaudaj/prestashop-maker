<?php echo "<?php\n"; ?>

namespace <?php echo $namespace; ?>

use PrestaShop\PrestaShop\Adapter\Domain\AbstractObjectModelHandler;
<?php echo $command_full_class_name; ?>;
use PrestaShopException;
<?php echo $exception_full_class_name; ?>;
<?php echo $cannot_update_exception_full_class_name; ?>;
<?php echo $not_found_exception_full_class_name; ?>;
<?php echo $value_object_full_class_name; ?>;
use PrestaShopDatabaseException;
use PrestaShopException;
use Doctrine\ORM\EntityManagerInterface;

/**
 * Class <?php echo $class_name; ?> is responsible for editing <?php echo $entity_lower_words; ?> data.
 *
 * @internal
 */
final class <?php echo $class_name; ?> extends AbstractObjectModelHandler
{
    /**
     * {@inheritdoc}
     *
     * @throws <?php echo $exception_class_name; ?>
     */
    public function handle(<?php echo $command_class_name; ?> $command)
    {
        try {
            $entity = $this->get<?php echo $entity_class_name; ?>EntityIfFound($command->get<?php echo $value_object_class_name; ?>()->getValue());

            //TODO: Set entity properties like this:
            // if (null !== $command->getProperty()) {
            //     $entity->setProperty($command->getProperty);
            // }
            // for following properties:
            <?php foreach ($entity_properties as $property) { ?>
                //<?php echo $property; ?>
            <?php } ?>

            if (false === $entity->update()) {
                throw new <?php echo $cannot_update_exception_class_name; ?>(sprintf(
                    'Unable to update <?php echo $entity_lower_words; ?> object with id %s', 
                    $command->get<?php echo $value_object_class_name; ?>->getValue()
                ));
            }
        } catch (PrestaShopException $e) {
            throw new <?php echo $exception_class_name; ?>(sprintf(
                'An unexpected error occurred when retrieving <?php echo $entity_lower_words; ?> with id %s', 
                var_export($command->get<?php echo $value_object_class_name; ?>()->getValue(), true)
            ), 0, $e);
        }
    }

    /**
     * Gets <?php echo $entity_lower_words; ?> entity.
     *
     * @param int $<?php echo $entity_var; ?>Id
     *
     * @return <?php echo $entity_class_name; ?>
     *
     * @throws <?php echo $not_found_exception_class_name; ?>
     * @throws PrestaShopException
     * @throws PrestaShopDatabaseException
     */
    private function get<?php echo $entity_class_name; ?>EntityIfFound($<?php echo $entity_var; ?>Id)
    {
        /** @var EntityManagerInterface $entityManager */
        $entityManager = $this->container->get('doctrine.orm.entity_manager');
        $entityRepository = $entityManager->getRepository(<?php echo $entity_class_name; ?>::class);
        $entity = $<?php echo $entity_var; ?>Repository->findById($<?php echo $entity_var; ?>Id);

        if (!$entity) {
            throw new <?php echo $not_found_exception_class_name; ?>(sprintf(
                '<?php echo $entity_class_name; ?> object with id %s was not found', var_export($<?php echo $entity_var; ?>Id, true)
            ));
        }

        return $entity;
    }
}
