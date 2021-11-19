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
            //TODO: Set form data with query result
            //'field' => $editable<?php echo $entity_class_name; ?>->getField()
        ];
    }

    /**
     * {@inheritdoc}
     */
    public function getDefaultData()
    {
        return [
            //TODO: Set form data with query result
            //'field' => $editableTest->getField()
        ];
    }
}
