<?php include $php_common_path; ?>

use <?= $psr_4; ?><?= $domain_namespace; ?>Exception\<?= $entity_class_name; ?>Exception;
use <?= $psr_4; ?><?= $domain_namespace; ?>ValueObject\<?= $entity_class_name; ?>Id;

/**
 * Class <?= $class_name; ?> is responsible for deleting <?= $entity_lower_words; ?> data.
 */
class <?= "$class_name\n"; ?>
{
    /**
     * @var <?= $entity_class_name; ?>Id
     */
    private $<?= $entity_var; ?>Id;

    /**
     * @throws <?= $entity_class_name; ?>Exception
     */
    public function __construct(int $<?= $entity_var; ?>Id)
    {
        $this-><?= $entity_var; ?>Id = new <?= $entity_class_name; ?>Id($<?= $entity_var; ?>Id);
    }

    public function get<?= $entity_class_name; ?>Id(): <?= $entity_class_name; ?>Id
    {
        return $this-><?= $entity_var; ?>Id;
    }
}

