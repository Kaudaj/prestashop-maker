<?php echo "<?php\n"; ?>

namespace <?php echo $namespace; ?>;

use PrestaShop\PrestaShop\Core\CommandBus\CommandBusInterface;

final class <?php echo $entity_class_name; ?>FormDataProvider implements FormDataProviderInterface
{
    /**
     * @var CommandBusInterface
     */
    private $queryBus;

    /**
     * @param CommandBusInterface $queryBus
     */
    public function __construct(CommandBusInterface $queryBus)
    {
        $this->queryBus = $queryBus;
    }

    /**
     * {@inheritdoc}
     */
    public function getData($<?php echo $entity_var; ?>Id)
    {
        /** @var Editable<?php echo $entity_class_name; ?> $editable<?php echo $entity_class_name; ?> */
        //$editable<?php echo $entity_class_name; ?> = $this->queryBus->handle(new Get<?php echo $entity_class_name; ?>ForEditing($<?php echo $entity_var; ?>Id));

        return [
            <?php foreach ($form_fields as $field => $value) { ?>
                '<?php echo $field; ?>' => <?php echo $value; ?>
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
