<?= "<?php\n"; ?>

namespace <?= $namespace; ?>;

use PrestaShop\PrestaShop\Core\CommandBus\CommandBusInterface;
use PrestaShop\PrestaShop\Core\Form\IdentifiableObject\DataHandler\FormDataHandlerInterface;
use <?= $psr_4; ?>Domain\<?= $entity_class_name; ?>\Command\Add<?= $entity_class_name; ?>Command;
use <?= $psr_4; ?>Domain\<?= $entity_class_name; ?>\Command\Edit<?= $entity_class_name; ?>Command;

final class <?= $entity_class_name; ?>FormDataHandler implements FormDataHandlerInterface
{
    /**
     * @var CommandBusInterface
     */
    private $commandBus;

    public function __construct(CommandBusInterface $commandBus)
    {
        $this->commandBus = $commandBus;
    }

    /**
     * {@inheritdoc}
     */
    public function create(array $data)
    {
        $add<?= $entity_class_name; ?>Command = new Add<?= $entity_class_name; ?>Command();
            //TODO: Set values with command set methods
            //->setProperty($data['field'])
        ;
        
        $<?= $entity_var; ?>Id = $this->commandBus->handle($add<?= $entity_class_name; ?>Command);
        
        return $<?= $entity_var; ?>Id->getValue();
    }

    /**
     * {@inheritdoc}
     */
    public function update($id, array $data)
    {
        $edit<?= $entity_class_name; ?>Command = (new Edit<?= $entity_class_name; ?>Command((int) $id))
            //TODO: Set values with command set methods
            //->setProperty($data['field']);
        ;

        $this->commandBus->handle($edit<?= $entity_class_name; ?>Command);
    }
}