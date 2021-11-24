<?php echo "<?php\n"; ?>

namespace <?php echo $namespace; ?>;

use <?php echo $query_full_class_name; ?>;
use <?php echo $query_result_full_class_name; ?>;
use Doctrine\ORM\EntityManagerInterface;

/**
 * Class <?php echo $class_name; ?> is responsible for getting the data for <?php echo $entity_lower_words; ?> edit page.
 *
 * @internal
 */
final class <?php echo $class_name; ?>
{
    /**
     * @param <?php echo $query_class_name; ?> $query
     *
     * @return <?php echo $query_result_class_name; ?>
     */
    public function handle(<?php echo $query_class_name; ?> $query)
    {
        try {
            /** @var EntityManagerInterface $entityManager */
            $entityManager = $this->container->get('doctrine.orm.entity_manager');
            $<?php echo $entity_var; ?>Repository = $entityManager->getRepository(<?php echo $entity_class_name; ?>::class);

            $<?php echo $entity_var; ?> = $<?php echo $entity_var; ?>Repository->findById($query->get<?php echo $entity_class_name; ?>Id()->getValue());

            /*if (!$<?php echo $entity_var; ?>) {
                throw new <?php echo $entity_class_name; ?>NotFoundException(sprintf(
                    '<?php echo $entity_words; ?> object with id %s was not found',
                    var_export($query->get<?php echo $entity_class_name; ?>Id()->getValue(), 
                    true)
                ));
            }*/

            $<?php echo $query_result_var; ?> = new <?php echo $query_result_class_name; ?>(
                $query->get<?php echo $value_object_class_name; ?>()->getValue(),
                <?php foreach ($entity_get_methods as $get_method) { ?>
                    $<?php echo $entity_var; ?>-><?php echo $get_method; ?>(),
                <?php } ?>
            );
        } catch (PrestaShopException $e) {
            throw new <?php echo $exception_class_name; ?>(sprintf(
                'An unexpected error occurred when retrieving <?php echo $entity_lower_words; ?> with id %s', 
                var_export($query->get<?php echo $value_object_class_name; ?>()->getValue(), true)
            ), 0, $e);
        }

        return $<?php echo $query_result_var; ?>;
    }
}
