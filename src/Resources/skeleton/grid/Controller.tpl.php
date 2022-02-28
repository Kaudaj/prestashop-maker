<?php include $php_common_path; ?>

use PrestaShopBundle\Controller\Admin\FrameworkBundleAdminController;
use <?= $psr_4; ?><?= !$destination_is_module ? 'Core\\' : ''; ?>Search\Filters\<?= $entity_class_name; ?>Filters;

class <?= $class_name; ?> extends FrameworkBundleAdminController
{
}
