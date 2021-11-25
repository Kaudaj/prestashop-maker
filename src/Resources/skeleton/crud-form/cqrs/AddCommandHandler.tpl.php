<?= "<?php\n"; ?>

namespace <?= $namespace; ?>;

use PrestaShop\PrestaShop\Adapter\Domain\AbstractObjectModelHandler;
use PrestaShopException;
use use <?= $psr_4; ?>Entity\<?= $entity_class_name; ?>;
use <?= $psr_4; ?>Domain\<?= $entity_class_name; ?>\Command\Add<?= $entity_class_name; ?>Command;
use <?= $psr_4; ?>Domain\<?= $entity_class_name; ?>\Exception\<?= $entity_class_name; ?>Exception;
use <?= $psr_4; ?>Domain\<?= $entity_class_name; ?>\Exception\CannotAdd<?= $entity_class_name; ?>Exception;
use <?= $psr_4; ?>Domain\<?= $entity_class_name; ?>\ValueObject\<?= $entity_class_name; ?>Id;

/**
 * Class <?= $class_name; ?> is used for adding <?= $entity_lower_words; ?> data.
 */
final class <?= $class_name; ?> extends AbstractObjectModelHandler
{
    /**
     * {@inheritdoc}
     *
     * @throws CannotAdd<?= $entity_class_name; ?>Exception
     * @throws <?= $entity_class_name; ?>Exception
     */
    public function handle(Add<?= $entity_class_name; ?>Command $command)
    {
        try {
            $entity = new <?= $entity_class_name; ?>();

            //TODO: Set entity properties like this:
            // if (null !== $command->getProperty()) {
            //     $entity->setProperty($command->getProperty);
            // }
            // for following properties:
            <?php foreach ($entity_properties as $property) { ?>
                //<?= $property; ?>
            <?php } ?>

            /** @var EntityManagerInterface $entityManager */
            $entityManager = $this->container->get('doctrine.orm.entity_manager');
            $entityManager->persist($entity);
            $entityManager->flush();
        } catch (PrestaShopException $exception) {
            throw new <?= $entity_class_name; ?>Exception('An unexpected error occurred when adding <?= $entity_lower_words; ?>', 0, $exception);
        }

        return new <?= $entity_class_name; ?>Id((int) $entity->id);
    }
}
