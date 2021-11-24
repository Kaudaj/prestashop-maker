<?php echo "<?php\n"; ?>

namespace <?php echo $namespace; ?>;

use PrestaShop\PrestaShop\Adapter\Domain\AbstractObjectModelHandler;
<?php echo $command_full_class_name; ?>;
use PrestaShopException;
<?php echo $exception_full_class_name; ?>;
<?php echo $cannot_add_exception_full_class_name; ?>;
<?php echo $value_object_full_class_name; ?>;

/**
 * Class <?php echo $class_name; ?> is used for adding <?php echo $entity_lower_words; ?> data.
 */
final class <?php echo $class_name; ?> extends AbstractObjectModelHandler
{
    /**
     * {@inheritdoc}
     *
     * @throws <?php echo $cannot_add_exception_class_name; ?>
     * @throws <?php echo $exception_class_name; ?>
     */
    public function handle(<?php echo $command_class_name; ?> $command)
    {
        try {
            $entity = new <?php echo $entity_class_name; ?>();

            //TODO: Set entity properties like this:
            // if (null !== $command->getProperty()) {
            //     $entity->setProperty($command->getProperty);
            // }
            // for following properties:
            <?php foreach ($entity_properties as $property) { ?>
                //<?php echo $property; ?>
            <?php } ?>

            if (false === $entity->add()) {
                throw new <?php echo $cannot_add_exception_class_name; ?>('Unable to add <?php echo $entity_lower_words; ?>');
            }
        } catch (PrestaShopException $exception) {
            throw new <?php echo $exception_class_name; ?>('An unexpected error occurred when adding <?php echo $entity_lower_words; ?>', 0, $exception);
        }

        return new <?php echo $value_object_class_name; ?>((int) $entity->id);
    }
}
