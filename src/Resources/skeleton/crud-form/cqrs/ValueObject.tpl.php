<?= "<?php\n"; ?>

namespace <?= $namespace; ?>;

use <?= $psr_4; ?>\Domain\<?= $entity_class_name; ?>\Exception\<?= $entity_class_name; ?>Exception;

/**
 * Class <?= $class_name; ?>
 */
class <?= $class_name; ?>
{
    /**
     * @var int
     */
    private $<?= $entity_var; ?>Id;

    /**
     * @param int $<?= $entity_var; ?>Id
     *
     * @throws <?= $entity_class_name; ?>Exception
     */
    public function __construct($<?= $entity_var; ?>Id)
    {
        $this->assertIsIntegerOrMoreThanZero($<?= $entity_var; ?>Id);

        $this-><?= $entity_var; ?>Id = $<?= $entity_var; ?>Id;
    }

    /**
     * @param int $<?= $entity_var; ?>Id
     *
     * @throws <?= $entity_class_name; ?>
     */
    private function assertIsIntegerOrMoreThanZero($<?= $entity_var; ?>Id)
    {
        if (!is_int($<?= $entity_var; ?>Id) || 0 >= $<?= $entity_var; ?>Id) {
            throw new <?= $entity_class_name; ?>Exception(sprintf('Invalid <?= $entity_class_name; ?> id: %s', var_export($<?= $entity_var; ?>Id, true)));
        }
    }

    /**
     * @return int
     */
    public function getValue()
    {
        return $this-><?= $entity_var; ?>Id;
    }
}
