<?php
/**
 * Plugin Name: TWW Glossary
 * Plugin URI: https://www.thewellnessway.com/
 * Description: A plugin to create a glossary of terms.
 * Version: 1.0.0
 * Requires at least: 5.0
 * Requires PHP: 5.6.20
 * Author: The Wellness Way
 * Author URI: https://www.thewellnessway.com/
 * License: GPLv2
 * License URI: https://www.gnu.org/licenses/old-licenses/gpl-2.0.html
 * Text Domain: tww-glossary
 * Domain Path: /languages/
 */
 if(! defined('ABSPATH')) {
     exit;
 }

 if(! defined('TWW_GLOSSARY_VERSION')) {
     define('TWW_GLOSSARY_VERSION', '1.0.0');
 }

 if(! defined('TWWG_GLOSSARY_PLUGIN_DIR')) {
     define('TWWG_GLOSSARY_PLUGIN_DIR', plugin_dir_path(__FILE__));
 }

 if(! defined('TWWG_GLOSSARY_PLUGIN_URL')) {
     define('TWWG_GLOSSARY_PLUGIN_URL', plugin_dir_url(__FILE__));
 }

 if(! defined('TWWG_GLOSSARY_PLUGIN_FILE')) {
     define('TWWG_GLOSSARY_PLUGIN_FILE', __FILE__);
 }

require_once 'vendor/autoload.php';

require_once 'tww-glossary-scraper.php';

use Carbon_Fields\Carbon_Fields;
use Carbon_Fields\Container;
use Carbon_Fields\Field;

add_action( 'after_theme_setup', 'crb_load' );
function crb_load() {    
    Carbon_Fields::boot();
}

use TwwGlossary\Admin\TwwgAdminMenu;
$TwwgAdminMenu = new TwwgAdminMenu();
$TwwgAdminMenu->register_hooks();
 class TWW_Glossary {
    const TWW_GLOSSARY_CSS_VERSION = '1.0.43';
     public function __construct() {
        add_action('init', [$this, 'register_post_types']);
        add_action('single_template', [$this, 'load_single_template']);
        add_action('rest_api_init', [$this, 'register_rest_routes']);
        add_filter('theme_templates', [$this, 'add_glossary_template']);
        add_filter('template_include', [$this, 'load_glossary_template']);
        add_action('wp_enqueue_scripts', [$this, 'enqueue_styles']);
        add_action( 'carbon_fields_register_fields', [$this, 'crb_attach_post_type_selection'] );
        add_filter('use_block_editor_for_post_type', [$this, 'disable_gutenberg_for_post_types'], 10, 2);
        //  add_filter('the_content', [$this, 'tww_study_content_convert_to_link']);

        if (class_exists('MeprUser')) {
            add_filter('body_class', [$this, 'add_body_class']);
        }
     }

     public function disable_gutenberg_for_post_types($can_edit, $post_type) {
        if ($post_type === 'tww_study_single') {
            return false;
        }
        return $can_edit;
    }

     public function add_body_class($classes) {
        $active_memberships = [];
        global $post;

        if(is_page_template('template-tww_glossary.php') || $post->post_type == 'tww_study') {
            $user_id = get_current_user_id();

            if($user_id) {
                $user = new \MeprUser( $user_id );
                if( (int)$user->ID !== 0 ) { 
                    $active_memberships = array_unique( $user->active_product_subscriptions( 'ids' ), true );
                 }
            }   

            if( ($active_memberships && count($active_memberships) > 0) || current_user_can('manage_options') ) {
                $classes[] = 'tww-plus-member';
                $classes[] = 'tww-plus-member-active';
            } else {
                $classes[] = 'tww-plus-non-member';
            }
         }         

         return $classes;
     }

     public function tww_study_content_convert_to_link($content) {
        if(get_post_type() !== 'tww_study') {
            return $content;
        }

        $url_pattern = '#\bhttps?://[a-zA-Z0-9\-\.]+\.[a-zA-Z]{2,}(/[^\s]*)?#';
        // Replacement pattern to convert URLs into links
        $replacement = '<a href="$0" target="_blank">$0</a>';
        // Perform the replacement
        $content = preg_replace($url_pattern, $replacement, $content);
    
        return $content;
     }

     public function crb_attach_post_type_selection() {
        Container::make( 'post_meta', 'Choose Letter(s) to index the study under in the glossary.' )
        ->where( 'post_type', '=', 'tww_study' ) 
        ->add_fields( [
            Field::make( 'association', 'tww_indexed_letters', __( 'Select Letter(s)' ) )
                ->set_types([
                    [
                        'type'      => 'post',
                        'post_type' => 'letter', 
                    ]
                ])
                ->set_max( 5 ), 
        ]);

        Container::make( 'post_meta', 'Study Details' )
            ->where('post_type', '=', 'tww_study_single')
                ->add_fields([
                    Field::make('association', 'tww_study_single_category', __('Select Study Category'))
                        ->set_types([
                            [
                                'type'      => 'post',
                                'post_type' => 'tww_study',
                            ]
                        ])
                        ->set_max(-1),
            Field::make('rich_text', 'tww_study_single_description', __('Study Description')),
            Field::make('complex', 'tww_study_single_links', __('Study Links'))
                ->add_fields([
                    Field::make('text', 'link_url', __('Link URL')),
                    Field::make('text', 'link_text', __('Link Text'))
                ])
                ->set_max(-1)
        ]);
     }

     public function enqueue_styles() {
         wp_enqueue_style('tww-glossary-css', TWWG_GLOSSARY_PLUGIN_URL . 'assets/css/tww-glossary.css', [], self::TWW_GLOSSARY_CSS_VERSION);
         wp_enqueue_script('tww-glossary-js', TWWG_GLOSSARY_PLUGIN_URL . 'assets/js/tww-glossary.js', ['jquery'], self::TWW_GLOSSARY_CSS_VERSION, true);
     }

     public function add_glossary_template($templates) {
         $templates['template-tww_glossary.php'] = __('TWW Glossary', 'tww-glossary');
         return $templates;
     }

     public function load_glossary_template($template) {
         if(is_page_template('template-tww_glossary.php')) {
             $template = TWWG_GLOSSARY_PLUGIN_DIR . 'templates/template-tww_glossary.php';
         }

         return $template;
     }

     public function load_single_template($single_template) {
        global $post;

        if ($post->post_type == 'tww_study') {
            $plugin_template = plugin_dir_path(__FILE__) . 'templates/single-tww_study.php';
            if (file_exists($plugin_template)) {
                return $plugin_template;
            }
        }

        return $single_template;
     }

     public function register_post_types() {
            register_post_type('tww_study', [
                'labels' => array(
                    'name' => __('Study Category', 'tww-glossary'),
                    'singular_name' => __('Study Category', 'tww-glossary'),
                    'plural_name' => __('Study Categories', 'tww-glossary'),
                    'add_new' => __('Add New Study Category', 'tww-glossary'),
                    'edit_item' => __('Edit Study Category', 'tww-glossary'),    
                    'search_items' => __('Search Study Categories', 'tww-glossary'),
                ),
                'public' => true,
                'publicly_queryable' => true,
                'query_var' => true,
                'has_archive' => false,
                'rewrite' => array('slug' => 'research'),
                'supports' => array('title', 'editor', 'permalink'),
                'taxonomies' => array('post_tag'),
                'menu_icon' => 'dashicons-book-alt',
                'show_ui' => true,
                'show_in_rest' => true,
                'exclude_from_search' => true,
                // Specify the slug of the parent menu here
                'show_in_menu' => 'edit.php?page=tww-glossary',
                'show_in_nav_menus' => true,
                'show_in_admin_bar' => true,
            ]);            

            register_post_type('letter', [
                'labels' => array(
                    'name' => __('Letter', 'tww-glossary'),
                    'singular_name' => __('Letter', 'tww-glossary'),
                    'add_new' => __('Add New Letter', 'tww-glossary'),
                    'edit_item' => __('Edit Letter', 'tww-glossary'),
                ),
                'public' => false,
                'has_archive' => false,
                'rewrite' => array('slug' => 'glossary/letter'),
                'supports' => array('title', 'editor', 'permalink'),
                'menu_icon' => 'dashicons-book-alt',
                'show_ui' => false,
                'show_in_rest' => true,
                'show_in_menu' => false,
                'show_in_nav_menus' => false,
                'show_in_admin_bar' => false,
                'exclude_from_search' => true,
            ]);

            register_post_type('tww_study_single', [
                'labels' => array(
                    'name' => __('Study', 'tww-glossary'),
                    'singular_name' => __('Study', 'tww-glossary'),
                    'plural_name' => __('Studies', 'tww-glossary'),
                    'add_new' => __('Add New Study', 'tww-glossary'),
                    'edit_item' => __('Edit Study', 'tww-glossary'),    
                    'search_items' => __('Search Studies', 'tww-glossary'),
                ),
                'public' => false,
                'publicly_queryable' => false,
                'query_var' => true,
                'has_archive' => false,
                'supports' => array('title', 'permalink'),
                'taxonomies' => array('post_tag'),
                'menu_icon' => 'dashicons-book-alt',
                'show_ui' => true,
                'show_in_rest' => true,
                // Specify the slug of the parent menu here
                'show_in_menu' => 'edit.php?page=tww-glossary',
                'show_in_nav_menus' => true,
                'show_in_admin_bar' => true,
                'exclude_from_search' => true,
            ]);       
            
            register_post_type('tww_study_index', [
                'labels' => array(
                    'name' => __('Glossary', 'tww-glossary'),
                    'singular_name' => __('Glossary', 'tww-glossary'),
                    'plural_name' => __('Glossaries', 'tww-glossary'),
                    'add_new' => __('Add New Glossary', 'tww-glossary'),
                    'edit_item' => __('Edit Glossary', 'tww-glossary'),    
                    'search_items' => __('Search Glossaries', 'tww-glossary'),
                ),
                'public' => false,
                'publicly_queryable' => false,
                'query_var' => true,
                'has_archive' => false,
                'supports' => array('title', 'permalink'),
                'taxonomies' => array('post_tag'),
                'menu_icon' => 'dashicons-book-alt',
                'show_ui' => true,
                'show_in_rest' => true,
                'show_in_menu' => 'edit.php?page=tww-glossary',
                'show_in_nav_menus' => true,
                'show_in_admin_bar' => true,
                'exclude_from_search' => true,
            ]); 
    }

    
    public function register_rest_routes() {
        register_rest_route('tww-glossary/v1', '/add-letter', [
            'methods' => 'POST',
            'callback' => [$this, 'add_letter'],
            'permission_callback' => '__return_true'
        ]);

        register_rest_route('tww-glossary/v1', '/add-alphabet', [
            'methods' => 'POST',
            'callback' => [$this, 'add_alphabet'],
            'permission_callback' => '__return_true'
        ]);

        register_rest_route('tww-glossary/v1', '/get-letters', [
            'methods' => 'GET',
            'callback' => [$this, 'get_letters'],
            'permission_callback' => '__return_true'
        ]);

        // Get every study and list title and content and id
        register_rest_route('tww-glossary/v1', '/get-studies', [
            'methods' => 'GET',
            'callback' => [$this, 'get_studies'],
            'permission_callback' => '__return_true'
        ]);

        // Get a single study
        register_rest_route('tww-glossary/v1', '/get-study/(?P<id>\d+)', [
            'methods' => 'GET',
            'callback' => [$this, 'get_study'],
            'permission_callback' => '__return_true'
        ]);

        register_rest_route('tww-glossary/v1', '/update-study/(?P<post_id>\d+)', [
            'methods' => 'POST',
            'callback' => [$this, 'update_study_content'],
            'permission_callback' => '__return_true'
        ]);
    }


    public function get_study(\WP_REST_Request $request) {
        $id = $request->get_param('id') ?? null;

        if(! $id) {
            return new WP_Error('missing-id', 'Missing ID parameter', ['status' => 400]);
        }

        $post = get_post($id);

        if(! $post) {
            return new WP_Error('post-not-found', 'Post not found', ['status' => 404]);
        }

        return [
            'id' => $post->ID,
            'title' => $post->post_title,
            'content' => $post->post_content
        ];
    }

    public function update_study_content(\WP_REST_Request $request) {
        $post_id = $request->get_param('post_id') ?? null;
        $content = $request->get_param('post_content') ?? null;
        $title = $request->get_param('post_title') ?? null;
        $post_type = $request->get_param('post_type') ?? null;

        if(! $post_id ) {
            return new WP_Error('missing-params', 'Missing required parameters', ['status' => 400]);
        }

        $post = get_post($post_id);

        if(! $post) {
            return new WP_Error('post-not-found', 'Post not found', ['status' => 404]);
        }

        if($content) {
            $content_updated = wp_update_post([
                'ID' => $post_id,
                'post_content' => $content
            ]);
        }

        if($title) {
            $tite_updated = wp_update_post([
                'ID' => $post_id,
                'post_title' => $title
            ]);
        }

        $updated = $content_updated || $tite_updated;

        if (!$updated) {
            return new WP_Error('update-failed', 'Failed to update post', ['status' => 500]);
        }

        return rest_ensure_response([
            'success' => true,
            'message' => 'Post updated successfully'
        ]);
    }

    public function get_studies() {
        $query = new WP_Query([
            'post_type' => 'tww_study',
            'posts_per_page' => -1,
            'orderby' => 'title',
            'order' => 'ASC'
        ]);

        $studies = [];

        foreach($query->posts as $post) {
            $studies[] = [
                'id' => $post->ID,
                'title' => $post->post_title,
                'content' => $post->post_content
            ];
        }

        return $studies;
    }

    public function add_letter($request) {
        $letter = $request->get_param('letter');

        if(! $letter) {
            return new WP_Error('no-letter', 'No letter provided', ['status' => 400]);
        }

        $post_id = wp_insert_post([
            'post_title' => $letter,
            'post_type' => 'letter',
            'post_status' => 'publish'
        ]);

        return $post_id;
    }

    public function add_alphabet($request) {
        $alphabet = range('A', 'Z');

        foreach($alphabet as $letter) {
            if(get_page_by_title($letter, OBJECT, 'letter')) {
                continue;
            }

            wp_insert_post([
                'post_title' => $letter,
                'post_type' => 'letter',
                'post_status' => 'publish'
            ]);
        }

        return $alphabet;
    }

    public function get_letters() {
        $query = new WP_Query([
            'post_type' => 'letter',
            'posts_per_page' => -1,
            'orderby' => 'title',
            'order' => 'ASC'
        ]);

        $letters = [];

        foreach($query->posts as $post) {
            $letters[] = $post->post_title;
        }

       return $letters;
    }

    /**
     * Retrieves the values of the 'tww_indexed_letters' field for a given 'tww_study' post ID.
     * 
     * @param int $post_id The ID of the 'tww_study' post.
     * @return mixed The value of the 'tww_indexed_letters' field, or null if not found.
     */
    public function get_tww_indexed_letters_by_study_id($post_id) {
        // Ensure Carbon Fields is loaded
        if (function_exists('carbon_get_post_meta')) {
            // Retrieve the 'tww_indexed_letters' field value
            $letters = carbon_get_post_meta($post_id, 'tww_indexed_letters');
            return $letters;
        } else {
            // Carbon Fields is not available
            return null;
        }
    }

    /**
     * Query 'tww_study' posts associated with a specific 'letter' post ID in the 'tww_indexed_letters' field.
     * 
     * @param int $letter_post_id The ID of the 'letter' post to filter by.
     * @return WP_Query The query object containing the matching 'tww_study' posts.
     */
    public function query_tww_studies_by_letter($letter_post_id) {
        // The meta query
        $meta_query = array(
            array(
                'key' => 'tww_indexed_letters', // The custom field key
                'value' => '"' . $letter_post_id . '"', // The ID of the letter post, wrapped in quotes for exact match
                'compare' => 'LIKE' // Use LIKE to find the value within serialized arrays
            )
        );
        
        // The main query
        $query = new WP_Query(array(
            'post_type' => 'tww_study', // The custom post type
            'posts_per_page' => -1, // Get all matching posts
            'meta_query' => $meta_query // The meta query from above
        ));
        
        return $query;
    }

    public static function last_updated_date() {
        $query = new WP_Query([
            'post_type' => 'tww_study',
            'posts_per_page' => 1,
            'orderby' => 'modified',
            'order' => 'DESC'
        ]);

        $last_updated_date = null;
        
        if($query->have_posts()) {
            $post = $query->posts[0];
            $last_updated_date = $post->post_modified;
        }

        return self::pretty_string_date_of_last_updated($last_updated_date);
    }

    /**
     * Date as follows:
     * Time on current date. E.g. Updated at 8:42am CT
     * Yesterday for the day before with the time. E.g. Updated Yesterday at 8:42am CT
     * Day of the week for the day before that with the time. E.g. Updated Monday at 8:42am CT
     * DD/MM/YYYY for anything older than that without the time. E.g. Updated 01/01/2021
     */
    public static function pretty_string_date_of_last_updated($last_updated_date = null) {

        if(null === $last_updated_date) {
            return '';
        }

        $current_date = new DateTime();
        $last_updated_date = new DateTime($last_updated_date);

        $diff = $current_date->diff($last_updated_date);

        $string = '';
        if($diff->days === 0) {
            $string = 'Updated at ' . $last_updated_date->format('g:ia T');
        } elseif($diff->days === 1) {
            $string = 'Updated Yesterday at ' . $last_updated_date->format('g:ia T');
        } elseif($diff->days < 7) {
            $string = 'Updated ' . $last_updated_date->format('l') . ' at ' . $last_updated_date->format('g:ia T');
        } else {
            $string = 'Updated ' . $last_updated_date->format('d/m/Y');
        }

        //replace 'UTC' with 'CT'
        $string = str_replace('UTC', 'CT', $string);

        return $string;
    }
 }

 new TWW_Glossary();

 add_filter('manage_tww_study_single_posts_columns', function($columns) {
    $columns['custom_field_column'] = __('Study Catgegory', 'twwg-glossary');
    return $columns;
});

add_action('manage_tww_study_single_posts_custom_column', function($column, $post_id) {
    if ($column === 'custom_field_column') {
        $custom_field = carbon_get_post_meta($post_id, 'tww_study_single_category');
        if ($custom_field) {
            foreach ($custom_field as $item) {
                if (preg_match('/^post:tww_study:(\d+)$/', $item['value'], $matches)) {
                    $associated_post_id = $matches[1];
                    $post_title = get_the_title($associated_post_id);
                    echo esc_html($post_title) . '<br>';
                } else {
                    echo esc_html($item['key'] . ': ' . $item['value']) . '<br>';
                }
            }
        }
    }
}, 10, 2);


add_filter('pre_get_posts', function($query) {
    global $pagenow;
    $typenow = $query->get('post_type');
    if ($typenow === 'tww_study_single' && $pagenow === 'edit.php' && isset($_GET['custom_field_filter']) && $_GET['custom_field_filter'] !== '') {
        $meta_query = $query->get('meta_query');
        if (!$meta_query) {
            $meta_query = [];
        }
        $meta_query[] = [
            'key' => 'tww_study_single_category',
            'value' => $_GET['custom_field_filter'],
            'compare' => 'LIKE',
        ];
        $query->set('meta_query', $meta_query);
    }
});

function get_distinct_custom_field_values() {
    global $wpdb;
    $results = $wpdb->get_col("
        SELECT DISTINCT meta_value
        FROM {$wpdb->postmeta}
        WHERE meta_key = 'tww_study_single_category'
    ");

    // Process the meta values to extract unique values from the serialized data
    $values = [];
    foreach ($results as $result) {
        $meta_values = maybe_unserialize($result);
        if (is_array($meta_values)) {
            foreach ($meta_values as $meta_value) {
                if (isset($meta_value['value']) && !in_array($meta_value['value'], $values)) {
                    $values[] = $meta_value['value'];
                }
            }
        }
    }

    return $values;
}



