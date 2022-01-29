<?= "<?php\n"; ?>

namespace <?= $namespace; ?>;

use PrestaShop\PrestaShop\Core\Grid\Definition\Factory\AbstractGridDefinitionFactory;
use PrestaShop\PrestaShop\Core\Grid\Column\ColumnCollection;
use PrestaShop\PrestaShop\Core\Grid\Column\Type\DataColumn;

final class <?= $class_name; ?> extends AbstractGridDefinitionFactory
{
    protected function getId()
    {
        return '<?= $entity_snake; ?>';
    }

    protected function getName()
    {
        return $this->trans('<?= $entity_human_words; ?>', [], 'Admin.Advparameters.Feature');
    }

    protected function getColumns()
    {
        return (new ColumnCollection())
            ->add((new DataColumn('id_<?= $entity_snake; ?>'))
                ->setName($this->trans('ID', [], 'Admin.Global'))
                ->setOptions([
                    'field' => 'id_<?= $entity_snake; ?>',
                ])
            )
            <?php foreach ($entity_properties as $property => $wording) { ?>
                ->add((new DataColumn('<?= $property; ?>'))
                    ->setName($this->trans('<?= $wording; ?>', [], 'Admin.Advparameters.Feature'))
                    ->setOptions([
                        'field' => '<?= $property; ?>',
                    ])
                )
            <?php } ?>
        ;
    }
}