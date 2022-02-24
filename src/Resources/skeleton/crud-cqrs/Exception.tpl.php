<?= $php_common; ?>

use PrestaShop\PrestaShop\Core\Domain\Exception\DomainException;

/**
 * An abstraction for all <?= $entity_lower_words; ?> related exceptions. Use this one in catch clause to detect all related exceptions.
 */
class <?= $class_name; ?> extends DomainException
{
}
