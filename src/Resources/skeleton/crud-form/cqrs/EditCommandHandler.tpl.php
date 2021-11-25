<?= "<?php\n"; ?>

namespace <?= $namespace; ?>;

use PrestaShop\PrestaShop\Adapter\Domain\AbstractObjectModelHandler;
use <?= $psr_4; ?>Domain\<?= $entity_class_name; ?>\Command\Edit<?= $entity_class_name; ?>Command;
use <?= $psr_4; ?>Domain\<?= $entity_class_name; ?>\Exception\<?= $entity_class_name; ?>Exception;
use <?= $psr_4; ?>Domain\<?= $entity_class_name; ?>\Exception\CannotUpdate<?= $entity_class_name; ?>Exception;
use <?= $psr_4; ?>Domain\<?= $entity_class_name; ?>\Exception\<?= $entity_class_name; ?>NotFoundException;
use PrestaShopDatabaseException;
use PrestaShopException;
use Doctrine\ORM\EntityManagerInterface;

/**
 * Class <?= $class_name; ?> is responsible for editing <?= $entity_lower_words; ?> data.
 *
 * @internal
 */
final class <?= $class_name; ?> extends AbstractObjectModelHandler
{
    /**
     * {@inheritdoc}
     *
     * @throws <?= $entity_class_name; ?>Exception
     */
    public function handle(Edit<?= $entity_class_name; ?>Command $command)
    {
        try {
            $entity = $this->get<?= $entity_class_name; ?>EntityIfFound($command->get<?= $entity_class_name; ?>Id()->getValue());

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
        } catch (PrestaShopException $e) {
            throw new <?= $entity_class_name; ?>Exception(sprintf(
                'An unexpected error occurred when retrieving <?= $entity_lower_words; ?> with id %s', 
                var_export($command->get<?= $entity_class_name; ?>Id()->getValue(), true)
            ), 0, $e);
        }
    }

    /**
     * Gets <?= $entity_lower_words; ?> entity.
     *
     * @param int $<?= $entity_var; ?>Id
     *
     * @return <?= $entity_class_name; ?>
     *
     * @throws <?= $entity_class_name; ?>NotFoundException
     * @throws PrestaShopException
     * @throws PrestaShopDatabaseException
     */
    private function get<?= $entity_class_name; ?>EntityIfFound($<?= $entity_var; ?>Id)
    {
        /** @var EntityManagerInterface $entityManager */
        $entityManager = $this->container->get('doctrine.orm.entity_manager');
        $entityRepository = $entityManager->getRepository(<?= $entity_class_name; ?>::class);
        $entity = $entityRepository->findById($<?= $entity_var; ?>Id);

        if (!$entity) {
            throw new <?= $entity_class_name; ?>NotFoundException(sprintf(
                '<?= $entity_class_name; ?> object with id %s was not found', var_export($<?= $entity_var; ?>Id, true)
            ));
        }

        return $entity;
    }
}
