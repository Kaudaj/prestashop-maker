<?= "<?php\n"; ?>

declare(strict_types=1);

namespace <?= $namespace; ?>;

use PrestaShop\PrestaShop\Core\CommandBus\CommandBusInterface;
use PrestaShop\PrestaShop\Core\Form\IdentifiableObject\DataProvider\FormDataProviderInterface;
use <?= $psr_4; ?>Domain\<?= $entity_class_name; ?>\Query\Get<?= $entity_class_name; ?>ForEditing;

final class <?= $entity_class_name; ?>FormDataProvider implements FormDataProviderInterface
{
    /**
     * @var CommandBusInterface
     */
    private $queryBus;

    public function __construct(CommandBusInterface $queryBus)
    {
        $this->queryBus = $queryBus;
    }

    /**
     * {@inheritdoc}
     */
    public function getData($<?= $entity_var; ?>Id)
    {
        /** @var Editable<?= $entity_class_name; ?> $editable<?= $entity_class_name; ?> */
        $editable<?= $entity_class_name; ?> = $this->queryBus->handle(new Get<?= $entity_class_name; ?>ForEditing($<?= $entity_var; ?>Id));

        return [
<?php foreach ($form_fields as $field => $value) { ?>
            '<?= $field; ?>' => <?= $value; ?>,
<?php } ?>
        ];
    }

    /**
     * {@inheritdoc}
     */
    public function getDefaultData()
    {
        return [
            //TODO: Set default data if needed
            'field_name' => null
        ];
    }
}
