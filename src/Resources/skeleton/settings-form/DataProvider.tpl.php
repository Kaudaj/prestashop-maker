<?= "<?php\n"; ?>

declare(strict_types=1);

namespace <?= $namespace; ?>;

use PrestaShop\PrestaShop\Core\Configuration\DataConfigurationInterface;
use PrestaShop\PrestaShop\Core\Form\FormDataProviderInterface;

class <?= $class_name; ?> implements FormDataProviderInterface
{
    /**
     * @var DataConfigurationInterface
     */
    private $<?= $form_var; ?>Configuration;

    /**
     * @param DataConfigurationInterface $<?= $form_var; ?>Configuration
     */
    public function __construct(DataConfigurationInterface $<?= $form_var; ?>Configuration)
    {
        $this-><?= $form_var; ?>Configuration = $<?= $form_var; ?>Configuration;
    }

    /**
     * {@inheritdoc}
     *
     * @return array<string, mixed> The form data as an associative array
     */
    public function getData(): array
    {
        return $this-><?= $form_var; ?>Configuration->getConfiguration();
    }

    /**
     * {@inheritdoc}
     *
     * @param array<string, mixed> $data
     *
     * @return array<int, array<string, mixed>> An array of errors messages if data can't persisted
     */
    public function setData(array $data): array
    {
        return $this-><?= $form_var; ?>Configuration->updateConfiguration($data);
    }
}
