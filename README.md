# PrestaShop Maker

[![GitHub release](https://img.shields.io/github/release/Kaudaj/prestashop-maker.svg)](https://GitHub.com/Kaudaj/kjmodulebedrock/releases/)
[![GitHub license](https://img.shields.io/github/license/Kaudaj/prestashop-maker)](https://github.com/Kaudaj/kjmodulebedrock/LICENSE.md)
[![Minimum PHP Version](https://img.shields.io/badge/php-%3E%3D%207.4-8892BF.svg?style=flat-square)](https://php.net/)

[![PHP tests](https://github.com/Kaudaj/prestashop-maker/actions/workflows/php.yml/badge.svg)](https://github.com/Kaudaj/kjmodulebedrock/actions/workflows/php.yml)

PrestaShop Maker is a tool to generate boilerplate code for [PrestaShop][prestashop] projects.<br>
It uses [Symfony MakerBundle](https://symfony.com/bundles/SymfonyMakerBundle/current/index.html) to generate code and then move the changes to your own project.

## Guide

### Installation

- Stable version

[Download a release](https://github.com/Kaudaj/prestashop-maker/releases/).

- Latest version

Clone the repository.

```bash
git clone https://github.com/Kaudaj/prestashop-maker.git
cd prestashop-maker
composer install
```

### Usage

PrestaShop Maker adds a new Command: make-to.

```bash
php bin/console make-to <destination-path> <make-command>
```

**Example**: In your PrestaShop project, you're working on a module and you want to generate an entity.

```bash
php /path/to/prestashop-maker/bin/console make-to modules/yourmodule make:entity
```

**Recommended**: As you can see, it will be quickly tiring to type the whole prestashop-maker console path each time.
It is highly recommended to define a simpler shortcut that you can use anywhere.

- Linux

```bash
echo "alias ps-make-to='php /path/to/prestashop-maker/bin/console make-to'" >> ~/.bash_aliases
source ~/.bash_aliases
```

- Windows PowerShell

```powershell
Add-Content "$Home\Documents\profile.ps1" "`nNew-Alias ps-make-to 'php /path/to/prestashop-maker/bin/console make-to'"
. "$Home\Documents\profile.ps1"
```

## Makers

### Current makers

To get the list of all the available makers, you can run the following command:

```bash
php bin/console list make
```

### Planned makers

- `make:prestashop:controller` PrestaShop [Controller](https://devdocs.prestashop.com/1.7/modules/concepts/controllers/)
- `make:prestashop:kpi` PrestaShop [KPI](https://devdocs.prestashop.com/1.7/modules/concepts/controllers/kpi-blocks/)
- `make:prestashop:grid` PrestaShop [Grid](https://devdocs.prestashop.com/1.7/development/components/grid/)
- `make:prestashop:form` PrestaShop [Form](https://devdocs.prestashop.com/1.7/development/architecture/migration-guide/forms/crud-forms/) (either CRUD or Settings)
- `make:prestashop:multi-lang-entity` PrestaShop [Multi-Lang Entity](https://devdocs.prestashop.com/1.7/modules/concepts/doctrine/how-to-handle-multi-lang-doctrine-entity/)

## To go further

### Reporting issues

You can report issues with this module in this very repository. [Click here to report an issue](https://github.com/Kaudaj/kjmodulebedrock/issues/new/choose).

### Contributing

As it is an open source project, everyone is welcome and even encouraged to contribute with their own improvements!

To contribute in the best way possible, you want to follow the [PrestaShop contribution guidelines](https://devdocs.prestashop.com/1.7/contribute/contribution-guidelines/project-modules/).

### License

This module is released under the [Academic Free License 3.0](https://opensource.org/licenses/AFL-3.0).

### Contact

Feel free to contact me by email at [info@kaudaj.com](mailto:info@kaudaj.com).

[prestashop]: https://www.prestashop.com/
