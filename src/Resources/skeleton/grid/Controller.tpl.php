<?= "<?php\n"; ?>

declare(strict_types=1);

namespace <?= $namespace; ?>;

use PrestaShopBundle\Controller\Admin\FrameworkBundleAdminController;
use <?= $psr_4; ?>Search\Filters\<?= $entity_class_name; ?>Filters;

class <?= $class_name; ?> extends FrameworkBundleAdminController
{
}
