<?php include $php_common_path; ?>

use <?= $psr_4; ?><?= $domain_namespace; ?>Exception\<?= $entity_class_name; ?>Exception;

/**
 * Class <?= "$class_name\n"; ?>
 */
class <?= "$class_name\n"; ?>
{
    /**
     * @var int
     */
    private $<?= $entity_var; ?>Id;

    /**
     * @throws <?= $entity_class_name; ?>Exception
     */
    public function __construct(int $<?= $entity_var; ?>Id)
    {
        $this->assertIsIntegerOrMoreThanZero($<?= $entity_var; ?>Id);

        $this-><?= $entity_var; ?>Id = $<?= $entity_var; ?>Id;
    }

    /**
     * @throws <?= $entity_class_name; ?>Exception
     */
    private function assertIsIntegerOrMoreThanZero(int $<?= $entity_var; ?>Id): void
    {
        if (!is_int($<?= $entity_var; ?>Id) || 0 >= $<?= $entity_var; ?>Id) {
            throw new <?= $entity_class_name; ?>Exception(sprintf('Invalid <?= $entity_class_name; ?> id: %s', var_export($<?= $entity_var; ?>Id, true)));
        }
    }

    public function getValue(): int
    {
        return $this-><?= $entity_var; ?>Id;
    }
}
