<?php
namespace TwwGlossary\Admin;

use TwwGlossary\Options\TwwgOptions;

use Carbon_Fields\Container;
use Carbon_Fields\Field;

class TwwgAdminMenu {
    const PAGE_TEMPLATE = 'dashboard';
    const TWWG_PAGE_IDENTIFIER = 'twwg-research';
    const TWWG_COMMON_SETTINGS_PAGE = 'twwg-common-settings';

    private $menu = null;

    private $option_group = 'twwg__common_settings';
    private $option_name = 'twwg__common_settings';

    public function __construct() {
        $settings = TwwgOptions::get_option($this->option_name, []);

        $this->prefix = TwwgOptions::PREFIX;
        $this->settings = $settings ?? [];

        $this->option_group = $this->prefix . $this->option_group;
        $this->option_name  = $this->prefix . $this->option_name;
    }

    public function register_hooks(): void {
        add_action('admin_menu', [$this, 'register_pages']);
        // add_action('carbon_fields_register_fields', [$this, 'register_common_settings']);
        add_action('after_setup_theme', [$this, 'boot_carbon_fields'], 5);
        add_action('admin_enqueue_scripts', [$this, 'enqueue_admin_styles']);
    }

    public function enqueue_admin_styles(): void {
        $version = '1.0.0';

        wp_enqueue_style('twwg-admin-styles', TWWG_GLOSSARY_PLUGIN_URL . 'assets/css/twwg-admin-style.css', [], $version, 'all');
    }

    public function register_pages(): void {
        $manage_capability = $this->get_manage_capability();
        $page_identifier = $this->get_page_identifier();

        $menu = add_menu_page(
            'TWW Glossary',
            'Research',
            $manage_capability,
            $page_identifier,
            [$this, 'show_page'],
            'dashicons-book-alt',
            20
        );

        $submenuIndexes = add_submenu_page(
            $page_identifier,
            'Glossaries',
            'Glossaries',
            'edit_posts',
            'edit.php?post_type=tww_study_index'
        );

        $submenuStudyCat = add_submenu_page(
            $page_identifier,
            'Study Categories',
            'Study Categories',
            'edit_posts',
            'edit.php?post_type=tww_study'
        );

        $submenuStudy = add_submenu_page(
            $page_identifier,
            'Studies',
            'Studies',
            'edit_posts',
            'edit.php?post_type=tww_study_single'
        );
    }

    public function get_pages_for_dropdown() {
        $pages = get_pages();
        $options = [];
        foreach ($pages as $page) {
            $options[$page->ID] = $page->post_title;
        }
        return $options;
    }

    public function boot_carbon_fields(): void {
        if (!did_action('carbon_fields_boot')) {
            \Carbon_Fields\Carbon_Fields::boot();
        }
    }

    public function validate_common_settings() {
        // Validation logic if needed
    }

    public function get_page_identifier(): string {
        return self::TWWG_PAGE_IDENTIFIER;
    }

    public function get_manage_capability(): string {
        return 'manage_options';
    }

    public function show_page(): void {
        require_once TWWG_GLOSSARY_PLUGIN_DIR . 'pages/' . self::PAGE_TEMPLATE . '.php';
    }
}
