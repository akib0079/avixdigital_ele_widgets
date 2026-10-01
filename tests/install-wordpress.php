<?php
define('WP_INSTALLING', true);
$_SERVER['HTTP_HOST'] = '127.0.0.1:4174';
$_SERVER['SERVER_NAME'] = '127.0.0.1';
require __DIR__.'/wordpress/wp-load.php';
require_once ABSPATH.'wp-admin/includes/upgrade.php';
require_once ABSPATH.'wp-admin/includes/plugin.php';
$password = bin2hex(random_bytes(18));
if (!is_blog_installed()) {
    wp_install('Avix widget verification', 'widget-review', 'review@example.test', false, '', $password);
    file_put_contents(__DIR__.'/local-credentials.json', json_encode(['username'=>'widget-review', 'password'=>$password]));
}
foreach (['elementor/elementor.php','avix-elementor-widgets/avix-elementor-widgets.php'] as $plugin) {
    $result = activate_plugin($plugin);
    if (is_wp_error($result)) throw new RuntimeException($result->get_error_message());
}
update_option('elementor_onboarded', true);
update_option('elementor_disable_color_schemes', 'yes');
update_option('elementor_disable_typography_schemes', 'yes');
echo 'Local WordPress installation ready. Plugins activated.'.PHP_EOL;
