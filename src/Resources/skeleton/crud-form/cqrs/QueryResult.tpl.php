<?php include $php_common_path; ?>

use <?= $psr_4; ?><?= $domain_namespace; ?>ValueObject\<?= $entity_class_name; ?>Id;
use <?= $psr_4; ?><?= $domain_namespace; ?>Exception\<?= $entity_class_name; ?>Exception;

/**
 * Transfers <?= $entity_lower_words; ?> data for editing.
 */
class <?= "$class_name\n"; ?>
{
    /**
     * @var <?= $entity_class_name; ?>Id
     */
    private $<?= $entity_var; ?>Id;

    public function get<?= $entity_class_name; ?>Id(): <?= $entity_class_name; ?>Id
    {
        return $this-><?= $entity_var; ?>Id;
    }
}
