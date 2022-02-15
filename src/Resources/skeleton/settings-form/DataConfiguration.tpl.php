<?= "<?php\n"; ?>

declare(strict_types=1);

namespace <?= $namespace; ?>;

use PrestaShop\PrestaShop\Core\Configuration\AbstractMultistoreConfiguration;
use <?= $psr_4; ?>Form\<?= $form_name; ?>\<?= $form_short_name; ?>Type;
use Symfony\Component\OptionsResolver\OptionsResolver;

/**
 * Manages the configuration data about <?= $form_human_words; ?> options.
 */
class <?= $class_name; ?> extends AbstractMultistoreConfiguration
{
    private const CONFIGURATION_FIELDS = [
<?php foreach ($form_fields as $name => $field) { ?>
        <?= $form_short_name; ?>Type::<?= $field['constant']; ?>,
<?php } ?>
    ];

    /**
     * {@inheritdoc}
     */
    public function getConfiguration()
    {
        $shopConstraint = $this->getShopConstraint();

        return [
<?php foreach ($form_fields as $name => $field) { ?>
<?php if ($field['php_type']) { ?>
            <?= $form_short_name; ?>Type::<?= $field['constant']; ?> 
                => (<?= $field['php_type']; ?>) $this->configuration->get(<?= $field['options']['multistore_configuration_key']; ?>, null, $shopConstraint),
<?php } else { ?>
            <?= $form_short_name; ?>Type::<?= $field['constant']; ?> 
                => $this->configuration->get(<?= $field['options']['multistore_configuration_key']; ?>, null, $shopConstraint),
<?php } ?>
<?php } ?>
        ];
    }

    /**
     * {@inheritdoc}
     */
    public function updateConfiguration(array $configuration)
    {
        $errors = [];

        if ($this->validateConfiguration($configuration)) {
            $shopConstraint = $this->getShopConstraint();

            $updateConfigurationValue = function(string $configurationKey, string $fieldName) use ($configuration, $shopConstraint) {
                return $this->updateConfigurationValue($configurationKey, $fieldName, $configuration, $shopConstraint);
            };

<?php foreach ($form_fields as $name => $field) { ?>
            $updateConfigurationValue(<?= $field['options']['multistore_configuration_key']; ?>, <?= $form_short_name; ?>Type::<?= $field['constant']; ?>);
<?php } ?>
        }

        return $errors;
    }

    /**
     * @return OptionsResolver
     */
    protected function buildResolver(): OptionsResolver
    {
        $resolver = (new OptionsResolver())
            ->setDefined(self::CONFIGURATION_FIELDS)
<?php foreach ($form_fields as $name => $field) { ?>
<?php if (is_array($field['php_type'])) { ?>
            ->setAllowedTypes(<?= $form_short_name; ?>Type::<?= $field['constant']; ?>, ['<?= implode("', '", $field['php_type']); ?>'])
<?php } else { ?>
            ->setAllowedTypes(<?= $form_short_name; ?>Type::<?= $field['constant']; ?>, ['<?= $field['php_type']; ?>'])
<?php } ?>
<?php } ?>
        ;

        return $resolver;
    }
}
