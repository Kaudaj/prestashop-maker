<?php
/**
 * Copyright since 2019 Kaudaj.
 *
 * NOTICE OF LICENSE
 *
 * This source file is subject to the Academic Free License 3.0 (AFL-3.0)
 * that is bundled with this package in the file LICENSE.md.
 * It is also available through the world-wide-web at this URL:
 * https://opensource.org/licenses/AFL-3.0
 * If you did not receive a copy of the license and are unable to
 * obtain it through the world-wide-web, please send an email
 * to info@kaudaj.com so we can send you a copy immediately.
 *
 * @author    Kaudaj <info@kaudaj.com>
 * @copyright Since 2019 Kaudaj
 * @license   https://opensource.org/licenses/AFL-3.0 Academic Free License 3.0 (AFL-3.0)
 */

declare(strict_types=1);

namespace Kaudaj\PrestaShopMaker\Util;

use Symfony\Bridge\Doctrine\Form\Type\EntityType;
use Symfony\Bundle\MakerBundle\ConsoleStyle;
use Symfony\Bundle\MakerBundle\Str;
use Symfony\Component\Form\Extension\Core\Type\BirthdayType;
use Symfony\Component\Form\Extension\Core\Type\ButtonType;
use Symfony\Component\Form\Extension\Core\Type\CheckboxType;
use Symfony\Component\Form\Extension\Core\Type\ChoiceType;
use Symfony\Component\Form\Extension\Core\Type\CollectionType;
use Symfony\Component\Form\Extension\Core\Type\ColorType;
use Symfony\Component\Form\Extension\Core\Type\CountryType;
use Symfony\Component\Form\Extension\Core\Type\CurrencyType;
use Symfony\Component\Form\Extension\Core\Type\DateIntervalType;
use Symfony\Component\Form\Extension\Core\Type\DateTimeType;
use Symfony\Component\Form\Extension\Core\Type\DateType;
use Symfony\Component\Form\Extension\Core\Type\EmailType;
use Symfony\Component\Form\Extension\Core\Type\FileType;
use Symfony\Component\Form\Extension\Core\Type\FormType;
use Symfony\Component\Form\Extension\Core\Type\HiddenType;
use Symfony\Component\Form\Extension\Core\Type\IntegerType;
use Symfony\Component\Form\Extension\Core\Type\LanguageType;
use Symfony\Component\Form\Extension\Core\Type\LocaleType;
use Symfony\Component\Form\Extension\Core\Type\MoneyType;
use Symfony\Component\Form\Extension\Core\Type\NumberType;
use Symfony\Component\Form\Extension\Core\Type\PasswordType;
use Symfony\Component\Form\Extension\Core\Type\PercentType;
use Symfony\Component\Form\Extension\Core\Type\RadioType;
use Symfony\Component\Form\Extension\Core\Type\RangeType;
use Symfony\Component\Form\Extension\Core\Type\RepeatedType;
use Symfony\Component\Form\Extension\Core\Type\ResetType;
use Symfony\Component\Form\Extension\Core\Type\SearchType;
use Symfony\Component\Form\Extension\Core\Type\SubmitType;
use Symfony\Component\Form\Extension\Core\Type\TelType;
use Symfony\Component\Form\Extension\Core\Type\TextareaType;
use Symfony\Component\Form\Extension\Core\Type\TextType;
use Symfony\Component\Form\Extension\Core\Type\TimeType;
use Symfony\Component\Form\Extension\Core\Type\TimezoneType;
use Symfony\Component\Form\Extension\Core\Type\UrlType;
use Symfony\Component\Form\Extension\Core\Type\WeekType;

final class FormTypesMapper
{
    private const PS_TYPES_NAMESPACE = 'PrestaShopBundle\\Form\\Admin\\Type\\';

    /**
     * @return array<string, string|string[]|null>
     */
    public function getMap(): array
    {
        return array_merge(
            $this->getTextFieldsMap(),
            $this->getChoiceFieldsMap(),
            $this->getDateAndTimeFieldsMap(),
            $this->getOtherFieldsMap(),
            $this->getFieldGroupsMap(),
            $this->getHiddenFieldsMap(),
            $this->getButtonsMap(),
            $this->getBaseFieldsMap()
        );
    }

    public function printMap(ConsoleStyle $io): void
    {
        $printSection = function (array $sectionTypes) use ($io) {
            $formattedSectionTypes = [];

            foreach ($sectionTypes as $formType => $phpType) {
                $formattedSectionTypes[] = $this->getFormattedFormType($formType);
            }

            sort($formattedSectionTypes);

            foreach ($formattedSectionTypes as $typeName) {
                $line = sprintf('  * <comment>%s</comment>', $typeName);

                $io->writeln($line);
            }

            $io->writeln('');
        };

        $io->writeln('<info>Text Fields</info>');
        $printSection($this->getTextFieldsMap());

        $io->writeln('<info>Choice Fields</info>');
        $printSection($this->getChoiceFieldsMap());

        $io->writeln('<info>Date and Time Fields</info>');
        $printSection($this->getDateAndTimeFieldsMap());

        $io->writeln('<info>Other Fields</info>');
        $printSection($this->getOtherFieldsMap());

        $io->writeln('<info>Field Groups Fields</info>');
        $printSection($this->getFieldGroupsMap());

        $io->writeln('<info>Hidden Fields</info>');
        $printSection($this->getHiddenFieldsMap());

        $io->writeln('<info>Buttons Fields</info>');
        $printSection($this->getButtonsMap());

        $io->writeln('<info>Base Fields</info>');
        $printSection($this->getBaseFieldsMap());
    }

    public function getFormattedFormType(string $formType): string
    {
        $typeName = Str::getShortClassName($formType);
        $origin = str_contains($formType, 'PrestaShop') ? 'PrestaShop' : 'Symfony';

        return "$typeName ($origin)";
    }

    /**
     * @return array<string, string|string[]|null>
     */
    public function getTextFieldsMap(): array
    {
        return array_merge(
            [
                TextType::class => 'string',
                TextareaType::class => 'string',
                EmailType::class => 'string',
                IntegerType::class => 'int',
                MoneyType::class => 'float',
                NumberType::class => 'float',
                PasswordType::class => 'string',
                PercentType::class => 'float',
                SearchType::class => 'string',
                UrlType::class => 'string',
                RangeType::class => 'int',
                TelType::class => 'string',
                ColorType::class => 'string',
            ],
            $this->getMapWithPrestaShopClassNames([
                'Email' => null,
                'ChangePassword' => null,
                'CustomContent' => null,
                'CustomMoney' => null,
                'DeltaQuantity' => null,
                'EntitySearchInput' => null,
                'FormattedTextarea' => null,
                'GeneratableText' => null,
                'IntegerMinMaxFilter' => null,
                'IpAddress' => null,
                'MoneyWithSuffix' => null,
                'NumberMinMaxFilter' => null,
                'Reduction' => null,
                'ResizableText' => null,
                'SubmittableDeltaQuantity' => null,
                'TextareaEmpty' => null,
                'TextEmpty' => null,
                'TextPreview' => null,
                'TextWithLengthCounter' => null,
                'TextWithRecommendedLength' => null,
                'TextWithUnit' => null,
                'TranslateText' => null,
            ])
        );
    }

    /**
     * @return array<string, string|string[]|null>
     */
    public function getChoiceFieldsMap(): array
    {
        return array_merge(
            [
                ChoiceType::class => null,
                EntityType::class => null,
                CountryType::class => 'string',
                LanguageType::class => 'string',
                LocaleType::class => 'string',
                TimezoneType::class => 'string',
                CurrencyType::class => 'string',
            ],
            $this->getMapWithPrestaShopClassNames([
                'CategoryChoiceTree' => null,
                'ChoiceCategories' => null,
                'CountryChoice' => null,
                'EntityItem' => null,
                'LogSeverityChoice' => null,
                'RadioWithChoice' => null,
                'ShopChoiceTree' => null,
                'ShopRestrictionCheckbox' => null,
                'TranslatableChoice' => null,
            ])
        );
    }

    /**
     * @return array<string, string|string[]|null>
     */
    public function getDateAndTimeFieldsMap(): array
    {
        return array_merge(
            [
                DateType::class => 'string',
                DateIntervalType::class => 'string',
                DateTimeType::class => 'string',
                TimeType::class => 'string',
                BirthdayType::class => 'string',
                WeekType::class => 'string',
            ],
            $this->getMapWithPrestaShopClassNames([
                'DatePicker' => null,
                'DateRange' => null,
            ])
        );
    }

    /**
     * @return array<string, string|string[]|null>
     */
    public function getOtherFieldsMap(): array
    {
        return array_merge(
            [
                CheckboxType::class => 'bool',
                FileType::class => 'string',
                RadioType::class => 'bool',
            ],
            $this->getMapWithPrestaShopClassNames([
                'ColorPicker' => null,
                'ImagePreview' => null,
                'Switch' => 'bool',
                'Translatable' => null,
                'TranslatorAware' => null,
                'Unavailable' => null,
                'YesAndNoChoice' => 'bool',
            ])
        );
    }

    /**
     * @return array<string, string|string[]|null>
     */
    public function getFieldGroupsMap(): array
    {
        return array_merge(
            [
                CollectionType::class => 'array',
                RepeatedType::class => 'array',
            ],
            $this->getMapWithPrestaShopClassNames([
                'TypeaheadCustomerCollection' => null,
                'TypeaheadProductCollection' => null,
                'TypeaheadProductPackCollection' => null,
            ])
        );
    }

    /**
     * @return array<string, string|string[]|null>
     */
    public function getHiddenFieldsMap(): array
    {
        return [
            HiddenType::class => null,
        ];
    }

    /**
     * @return array<string, string|string[]|null>
     */
    public function getButtonsMap(): array
    {
        return array_merge(
            [
                ButtonType::class => null,
                ResetType::class => null,
                SubmitType::class => null,
            ],
            [
                'IconButton' => null,
                'SearchAndReset' => null,
                'SubmittableInput' => null,
            ]
        );
    }

    /**
     * @return array<string, string|string[]|null>
     */
    public function getBaseFieldsMap(): array
    {
        return [
            FormType::class => 'mixed',
        ];
    }

    /**
     * @param array<string, string|string[]|null> $typesNames
     *
     * @return array<string, string|string[]|null>
     */
    private function getMapWithPrestaShopClassNames(array $typesNames): array
    {
        $typesClassNames = [];

        foreach ($typesNames as $type => $phpType) {
            $className = self::PS_TYPES_NAMESPACE.$type.'Type';

            $typesClassNames[$className] = $phpType;
        }

        return $typesClassNames;
    }
}
