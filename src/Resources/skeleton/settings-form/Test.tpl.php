<?php include $php_common_path; ?>

use <?= $psr_4; ?><?= !$destination_is_module ? 'Adapter\\' : ''; ?><?= "$form_name\\{$form_short_name}Configuration"; ?>;
use PrestaShop\PrestaShop\Core\Domain\Shop\ValueObject\ShopConstraint;
use <?= $psr_4; ?><?= $form_namespace; ?><?= "$form_name\\{$form_short_name}Type"; ?>;
use Symfony\Component\OptionsResolver\Exception\InvalidOptionsException;
use Symfony\Component\OptionsResolver\Exception\UndefinedOptionsException;
use Tests\TestCase\AbstractConfigurationTestCase;

class <?= $class_name; ?> extends AbstractConfigurationTestCase
{
    /**
     * @var <?= $form_short_name; ?>Configuration
     */
    private $<?= $form_var; ?>Configuration;

    // TODO: Define valid values for each field
    private const VALID_CONFIGURATION = [
<?php foreach ($form_fields as $name => $field) { ?>
        <?= $form_short_name; ?>Type::<?= $field['constant']; ?> => 'valid_value',
<?php } ?>
    ];

    private const SHOP_ID = 42;

    protected function setUp(): void
    {
        parent::setUp();

        $this-><?= $form_var; ?>Configuration = new <?= $form_short_name; ?>Configuration(
            $this->mockConfiguration,
            $this->mockShopConfiguration,
            $this->mockMultistoreFeature
        );
    }

    /**
     * @dataProvider provideShopConstraints
     *
     * @param ShopConstraint $shopConstraint
     */
    public function testGetConfiguration(ShopConstraint $shopConstraint): void
    {
        $this->mockShopConfiguration
            ->method('getShopConstraint')
            ->willReturn($shopConstraint);

        $this->mockConfiguration
            ->method('get')
            ->willReturnMap(
                [
<?php foreach ($form_fields as $name => $field) { ?>
                    [<?= $field['options']['multistore_configuration_key']; ?>, null, $shopConstraint, self::VALID_CONFIGURATION[<?= $form_short_name; ?>Type::<?= $field['constant']; ?>]],
<?php } ?>
                ]
            );

        $result = $this-><?= $form_var; ?>Configuration->getConfiguration();

        $this->assertSame(self::VALID_CONFIGURATION, $result);
    }

    /**
     * @dataProvider provideInvalidConfiguration
     *
     * @param string $exception
     * @param array $values
     */
    public function testUpdateConfigurationWithInvalidConfiguration(string $exception, array $values): void
    {
        $this->expectException($exception);

        $this-><?= $form_var; ?>Configuration->updateConfiguration($values);
    }

    /**
     * @return array[]
     */
    public function provideInvalidConfiguration(): array
    {
        return [
            [UndefinedOptionsException::class, ['does_not_exist' => 'does_not_exist']],
            // TODO: Replace 'wrong_type' with real wrong type values
<?php foreach ($form_fields as $name => $field) { ?>
            [InvalidOptionsException::class, array_merge(self::VALID_CONFIGURATION, [<?= $form_short_name; ?>Type::<?= $field['constant']; ?> => 'wrong_type'])],
<?php } ?>
        ];
    }

    public function testSuccessfulUpdate(): void
    {
        $res = $this-><?= $form_var; ?>Configuration->updateConfiguration(self::VALID_CONFIGURATION);

        $this->assertSame([], $res);
    }

    /**
     * @return array[]
     */
    public function provideShopConstraints(): array
    {
        return [
            [ShopConstraint::shop(self::SHOP_ID)],
            [ShopConstraint::shopGroup(self::SHOP_ID)],
            [ShopConstraint::allShops()],
        ];
    }
}
