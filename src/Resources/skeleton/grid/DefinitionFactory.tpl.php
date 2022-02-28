<?php include $php_common_path; ?>

use PrestaShop\PrestaShop\Core\Grid\Definition\Factory\AbstractGridDefinitionFactory;
use PrestaShop\PrestaShop\Core\Grid\Column\ColumnCollection;
use PrestaShop\PrestaShop\Core\Grid\Column\Type\DataColumn;

final class <?= $class_name; ?> extends AbstractGridDefinitionFactory
{
    public const GRID_ID = '<?= $grid_id; ?>';

    protected function getId()
    {
        return self::GRID_ID;
    }

    protected function getName()
    {
        return $this->trans('<?= $grid_name; ?>', [], '<?= $translation_domain; ?>');
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
<?php foreach ($grid_columns as $field => $title) { ?>
            ->add((new DataColumn('<?= $field; ?>'))
                ->setName($this->trans('<?= $title; ?>', [], '<?= $translation_domain; ?>'))
                ->setOptions([
                    'field' => '<?= $field; ?>',
                ])
            )
<?php } ?>
        ;
    }
}