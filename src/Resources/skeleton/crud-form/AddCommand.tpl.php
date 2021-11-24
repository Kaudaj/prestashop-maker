<?php echo "<?php\n"; ?>

namespace <?php echo $namespace; ?>;

use <?php echo $exception_full_class_name; ?>;
use <?php echo $value_object_full_class_name; ?>;

/**
 * Class <?php echo $class_name; ?> is responsible for editing <?php echo $entity_lower_words; ?> data.
 */
class <?php echo $class_name; ?>
{
    /**
     * @var <?php echo $value_object_class_name; ?>
     */
    private $<?php echo $value_object_var; ?>;

    /**
     * @param int $<?php echo $value_object_var; ?>
     *
     * @throws <?php echo $exception_class_name; ?>
     */
    public function __construct($<?php echo $value_object_var; ?>)
    {
        $this-><?php echo $value_object_var; ?> = new <?php echo $value_object_class_name; ?>(<?php echo $value_object_var; ?>);
    }

    /**
     * @return <?php echo $value_object_class_name; ?>
     */
    public function get<?php echo $value_object_class_name; ?>()
    {
        return $this-><?php echo $value_object_var; ?>;
    }
}
