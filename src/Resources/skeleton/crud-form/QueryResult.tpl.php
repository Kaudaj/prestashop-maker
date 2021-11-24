<?php echo "<?php\n"; ?>

namespace <?php echo $namespace; ?>;

use <?php echo $value_object_full_class_name; ?>;
use <?php echo $exception_full_class_name; ?>;

/**
 * Transfers <?php echo $entity_lower_words; ?> data for editing.
 */
class <?php echo $class_name; ?>
{
    /**
     * @var <?php echo $value_object_class_name; ?>
     */
    private $<?php echo $value_object_var; ?>;

    /**
     * @return <?php echo $value_object_class_name; ?>
     */
    public function get<?php echo $value_object_class_name; ?>()
    {
        return $this-><?php echo $value_object_var; ?>;
    }
}
