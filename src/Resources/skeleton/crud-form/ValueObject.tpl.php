<?php echo "<?php\n"; ?>

namespace <?php echo $namespace; ?>;

use <?php echo $exception_full_class_name; ?>;

/**
 * Class <?php echo $class_name; ?>
 */
class <?php echo $class_name; ?>
{
    /**
     * @var int
     */
    private $<?php echo $entity_var; ?>Id;

    /**
     * @param int $<?php echo $entity_var; ?>Id
     *
     * @throws <?php echo $exception_class_name; ?>
     */
    public function __construct($<?php echo $entity_var; ?>Id)
    {
        $this->assertIsIntegerOrMoreThanZero($<?php echo $entity_var; ?>Id);

        $this-><?php echo $entity_var; ?>Id = $<?php echo $entity_var; ?>Id;
    }

    /**
     * @param int $<?php echo $entity_var; ?>Id
     *
     * @throws <?php echo $entity_class_name; ?>
     */
    private function assertIsIntegerOrMoreThanZero($<?php echo $entity_var; ?>Id)
    {
        if (!is_int($<?php echo $entity_var; ?>Id) || 0 >= $<?php echo $entity_var; ?>Id) {
            throw new <?php echo $exception_class_name; ?>(sprintf('Invalid <?php echo $entity_class_name; ?> id: %s', var_export($<?php echo $entity_var; ?>Id, true)));
        }
    }

    /**
     * @return int
     */
    public function getValue()
    {
        return $this-><?php echo $entity_var; ?>Id;
    }
}
