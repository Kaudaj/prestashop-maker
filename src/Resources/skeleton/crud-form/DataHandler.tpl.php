<?php echo "<?php\n"; ?>

namespace <?php echo $namespace; ?>;

use PrestaShop\PrestaShop\Core\CommandBus\CommandBusInterface;

final class <?php echo $entity_class_name; ?>FormDataHandler implements FormDataHandlerInterface
{
    /**
     * @var CommandBusInterface
     */
    private $commandBus;

    /**
     * @param CommandBusInterface $commandBus
     */
    public function __construct(CommandBusInterface $commandBus)
    {
        $this->commandBus = $commandBus;
    }

    /**
     * {@inheritdoc}
     */
    public function create(array $data)
    {
        $add<?php echo $entity_class_name; ?>Command = new Add<?php echo $entity_class_name; ?>Command(
            //TODO: Fill with data array
            //$data['field']
        );
        
        $<?php echo $entity_var; ?>Id = $this->commandBus->handle($add<?php echo $entity_class_name; ?>Command);
        
        return $<?php echo $entity_var; ?>Id->getValue();
    }

    /**
     * {@inheritdoc}
     */
    public function update($id, array $data)
    {
        $edit<?php echo $entity_class_name; ?>Command = (new Edit<?php echo $entity_class_name; ?>Command((int) $id))
            //TODO: Set values with command set methods
            //->setProperty($data['field']);
        ;

        $this->commandBus->handle($edit<?php echo $entity_class_name; ?>Command);
    }
}