# PrestaShop Maker

[![GitHub release](https://img.shields.io/github/release/Kaudaj/prestashop-maker.svg)](https://GitHub.com/Kaudaj/prestashop-maker/releases/)
[![GitHub license](https://img.shields.io/github/license/Kaudaj/prestashop-maker)](https://github.com/Kaudaj/prestashop-maker/LICENSE.md)
[![Minimum PHP Version](https://img.shields.io/badge/php-%3E%3D%207.4-8892BF.svg?style=flat-square)](https://php.net/)

[![PHP tests](https://github.com/Kaudaj/prestashop-maker/actions/workflows/php.yml/badge.svg)](https://github.com/Kaudaj/prestashop-maker/actions/workflows/php.yml)

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

PrestaShop Maker adds a new command: `make-to`.

```bash
php bin/console make-to <destination-path> <make-command>
```

Destination path argument is your PrestaShop destination project root path.

#### Working on a module

If you want to generate the files to a module instead of PrestaShop core, use `-m` option with the module class name.

You can also define `DESTINATION_MODULE` environment variable instead (in `/.env.local`). It's practical if you're actively working on the same module, so you don't have to retype it everytime in the `make-to` command.

#### Examples

```bash
php /path/to/prestashop-maker/bin/console make-to /path/to/prestashop/project make:entity
```

```bash
php /path/to/prestashop-maker/bin/console make-to /path/to/prestashop/project make:ps:grid -d MyModule
```

#### Recommended: Create an alias

As you can see, it will be quickly tiring to type the whole prestashop-maker console path each time.
It is highly recommended to define a simpler shortcut that you can use anywhere.

Instructions are available in this [gist](https://gist.github.com/Kaudaj/cf416de07a615c000a69da5ea44b1e86).<br>
Replace `<your-command>` with `php /path/to/prestashop-maker/bin/console make-to` and `your-alias` with whatever you want.

## Makers

### Current makers

To get the list of all the available makers, you can run the following command:

```bash
php bin/console list make
```

To get the list of the makers from PrestaShop Maker only, run this one:

```bash
php bin/console list make:prestashop
# or
php bin/console list make:ps
```

### Planned makers

- `make:prestashop:controller` PrestaShop [Controller](https://devdocs.prestashop.com/1.7/modules/concepts/controllers/)
- `make:prestashop:kpi` PrestaShop [KPI](https://devdocs.prestashop.com/1.7/modules/concepts/controllers/kpi-blocks/)
- ~~`make:prestashop:grid` PrestaShop [Grid](https://devdocs.prestashop.com/1.7/development/components/grid/)~~
- ~~`make:prestashop:crud-form` PrestaShop [CRUD Form](https://devdocs.prestashop.com/1.7/development/architecture/migration-guide/forms/crud-forms/)~~
- ~~`make:prestashop:crud-cqrs` PrestaShop CRUD [CQRS](https://devdocs.prestashop.com/1.7/development/architecture/domain/cqrs/)~~
- ~~`make:prestashop:cqrs` PrestaShop [CQRS](https://devdocs.prestashop.com/1.7/development/architecture/domain/cqrs/)~~
- ~~`make:prestashop:settings-form` PrestaShop [Settings Form](https://devdocs.prestashop.com/1.7/development/architecture/migration-guide/forms/settings-forms/)~~
- ~~`make:prestashop:multi-lang-entity` PrestaShop [Multi-Lang Entity](https://devdocs.prestashop.com/1.7/modules/concepts/doctrine/how-to-handle-multi-lang-doctrine-entity/)~~

## To go further

### Reporting issues

You can report issues with this module in this very repository. [Click here to report an issue](https://github.com/Kaudaj/prestashop-maker/issues/new/choose).

### Contributing

As it is an open source project, everyone is welcome and even encouraged to contribute with their own improvements!

To contribute in the best way possible, you want to follow the [PrestaShop contribution guidelines](https://devdocs.prestashop.com/1.7/contribute/contribution-guidelines/project-modules/).

### License

This module is released under the [Academic Free License 3.0](https://opensource.org/licenses/AFL-3.0).

### Contact

Feel free to contact me by email at [info@kaudaj.com](mailto:info@kaudaj.com).

[prestashop]: https://www.prestashop.com/
