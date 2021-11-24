<?php echo "<?php\n"; ?>

namespace <?php echo $namespace; ?>;

use PrestaShop\PrestaShop\Core\Domain\Exception\DomainException;

/**
 * An abstraction for all <?php echo $entity_lower_words; ?> related exceptions. Use this one in catch clause to detect all related exceptions.
 */
class <?php echo $class_name; ?> extends DomainException
{
}
