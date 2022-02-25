<?php include $php_common_path; ?>

use PrestaShopBundle\Form\Admin\Type\MultistoreConfigurationType;
use PrestaShopBundle\Form\Admin\Type\TranslatorAwareType;
<?php foreach (array_unique(array_column($form_fields, 'type')) as $type) { ?>
use <?= $type; ?>;
<?php } ?>
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\OptionsResolver\OptionsResolver;

class <?= $class_name; ?> extends TranslatorAwareType
{
<?php foreach ($form_fields as $name => $field) { ?>
    public const <?= $field['constant']; ?> = '<?= $field['name']; ?>';
<?php } ?>

    /**
     * {@inheritdoc}
     */
    public function buildForm(FormBuilderInterface $builder, array $options)
    {
        $builder
<?php foreach ($form_fields as $name => $field) { ?>
            ->add(self::<?= $field['constant']; ?>, <?= $field['short_type']; ?>::class, [
<?php foreach ($field['options'] as $option => $value) { ?>
                '<?= $option; ?>' => <?= $value; ?>,
<?php } ?>
            ])
<?php } ?>
        ;
    }

    /**
     * {@inheritdoc}
     */
    public function configureOptions(OptionsResolver $resolver)
    {
        $resolver->setDefaults([
            'translation_domain' => '<?= $translation_domain; ?>',
        ]);
    }

    /**
     * {@inheritdoc}
     */
    public function getBlockPrefix()
    {
        return '<?= $block_prefix; ?>';
    }

    /**
     * {@inheritdoc}
     *
     * @see MultistoreConfigurationTypeExtension
     */
    public function getParent(): string
    {
        return MultistoreConfigurationType::class;
    }
}
