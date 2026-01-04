# WordPress Plugin Development Guide

## Table of Contents
- Plugin Structure
- Plugin Headers
- Hooks and Filters
- Database Operations
- Ajax Functionality
- Settings and Options
- Custom Admin Pages

## Plugin Structure

```
plugin-name/
├── plugin-name.php           # Main plugin file (REQUIRED)
├── uninstall.php            # Cleanup on uninstall
├── readme.txt               # WordPress.org readme
├── includes/
│   ├── class-plugin-name.php
│   ├── class-admin.php
│   └── class-public.php
├── admin/
│   ├── css/
│   │   └── admin-styles.css
│   ├── js/
│   │   └── admin-scripts.js
│   └── partials/
│       └── admin-display.php
└── public/
    ├── css/
    │   └── public-styles.css
    └── js/
        └── public-scripts.js
```

## Plugin Header

Every WordPress plugin must have a header in the main PHP file:

```php
<?php
/**
 * Plugin Name:       Plugin Name
 * Plugin URI:        https://example.com/plugin
 * Description:       Brief description of what the plugin does
 * Version:           1.0.0
 * Requires at least: 6.0
 * Requires PHP:      7.4
 * Author:            Author Name
 * Author URI:        https://example.com
 * License:           GPL v2 or later
 * License URI:       https://www.gnu.org/licenses/gpl-2.0.html
 * Text Domain:       plugin-slug
 * Domain Path:       /languages
 */

// If this file is called directly, abort.
if ( ! defined( 'WPINC' ) ) {
    die;
}

// Define plugin constants
define( 'PLUGIN_NAME_VERSION', '1.0.0' );
define( 'PLUGIN_NAME_PATH', plugin_dir_path( __FILE__ ) );
define( 'PLUGIN_NAME_URL', plugin_dir_url( __FILE__ ) );
```

## Basic Plugin Template

```php
<?php
/**
 * Plugin Name: My Custom Plugin
 * Description: A custom plugin example
 * Version: 1.0.0
 */

// Exit if accessed directly
if ( ! defined( 'ABSPATH' ) ) {
    exit;
}

class My_Custom_Plugin {

    private static $instance = null;

    public static function get_instance() {
        if ( null === self::$instance ) {
            self::$instance = new self();
        }
        return self::$instance;
    }

    private function __construct() {
        $this->init_hooks();
    }

    private function init_hooks() {
        // Activation and deactivation hooks
        register_activation_hook( __FILE__, array( $this, 'activate' ) );
        register_deactivation_hook( __FILE__, array( $this, 'deactivate' ) );

        // WordPress hooks
        add_action( 'init', array( $this, 'init' ) );
        add_action( 'admin_menu', array( $this, 'add_admin_menu' ) );
        add_action( 'wp_enqueue_scripts', array( $this, 'enqueue_scripts' ) );
    }

    public function activate() {
        // Activation code
        flush_rewrite_rules();
    }

    public function deactivate() {
        // Deactivation code
        flush_rewrite_rules();
    }

    public function init() {
        // Initialization code
        load_plugin_textdomain( 'plugin-slug', false, dirname( plugin_basename( __FILE__ ) ) . '/languages' );
    }

    public function add_admin_menu() {
        add_menu_page(
            __( 'Plugin Name', 'plugin-slug' ),
            __( 'Plugin Name', 'plugin-slug' ),
            'manage_options',
            'plugin-slug',
            array( $this, 'admin_page' ),
            'dashicons-admin-generic',
            30
        );
    }

    public function admin_page() {
        ?>
        <div class="wrap">
            <h1><?php echo esc_html( get_admin_page_title() ); ?></h1>
            <!-- Admin page content -->
        </div>
        <?php
    }

    public function enqueue_scripts() {
        wp_enqueue_style( 'plugin-slug-style', plugin_dir_url( __FILE__ ) . 'public/css/styles.css', array(), '1.0.0' );
        wp_enqueue_script( 'plugin-slug-script', plugin_dir_url( __FILE__ ) . 'public/js/script.js', array( 'jquery' ), '1.0.0', true );
    }
}

// Initialize the plugin
function my_custom_plugin() {
    return My_Custom_Plugin::get_instance();
}
my_custom_plugin();
```

## Custom Post Type Plugin

```php
<?php
/**
 * Plugin Name: Articles Manager
 * Description: Manage publication articles with custom fields
 */

class Articles_Manager {

    public function __construct() {
        add_action( 'init', array( $this, 'register_post_type' ) );
        add_action( 'init', array( $this, 'register_taxonomy' ) );
        add_action( 'add_meta_boxes', array( $this, 'add_meta_boxes' ) );
        add_action( 'save_post', array( $this, 'save_meta' ) );
    }

    public function register_post_type() {
        $labels = array(
            'name'               => __( 'Articles', 'articles' ),
            'singular_name'      => __( 'Article', 'articles' ),
            'add_new'            => __( 'Add New', 'articles' ),
            'add_new_item'       => __( 'Add New Article', 'articles' ),
            'edit_item'          => __( 'Edit Article', 'articles' ),
            'new_item'           => __( 'New Article', 'articles' ),
            'view_item'          => __( 'View Article', 'articles' ),
            'search_items'       => __( 'Search Articles', 'articles' ),
            'not_found'          => __( 'No articles found', 'articles' ),
            'not_found_in_trash' => __( 'No articles found in Trash', 'articles' ),
        );

        $args = array(
            'labels'              => $labels,
            'public'              => true,
            'has_archive'         => true,
            'publicly_queryable'  => true,
            'query_var'           => true,
            'rewrite'             => array( 'slug' => 'articles' ),
            'capability_type'     => 'post',
            'hierarchical'        => false,
            'menu_position'       => 5,
            'menu_icon'           => 'dashicons-media-document',
            'supports'            => array( 'title', 'editor', 'thumbnail', 'excerpt', 'author', 'comments', 'revisions' ),
            'show_in_rest'        => true, // Enable Gutenberg
        );

        register_post_type( 'article', $args );
    }

    public function register_taxonomy() {
        $labels = array(
            'name'              => __( 'Topics', 'articles' ),
            'singular_name'     => __( 'Topic', 'articles' ),
            'search_items'      => __( 'Search Topics', 'articles' ),
            'all_items'         => __( 'All Topics', 'articles' ),
            'parent_item'       => __( 'Parent Topic', 'articles' ),
            'parent_item_colon' => __( 'Parent Topic:', 'articles' ),
            'edit_item'         => __( 'Edit Topic', 'articles' ),
            'update_item'       => __( 'Update Topic', 'articles' ),
            'add_new_item'      => __( 'Add New Topic', 'articles' ),
            'new_item_name'     => __( 'New Topic Name', 'articles' ),
            'menu_name'         => __( 'Topics', 'articles' ),
        );

        register_taxonomy( 'topic', 'article', array(
            'labels'            => $labels,
            'hierarchical'      => true,
            'show_ui'           => true,
            'show_admin_column' => true,
            'query_var'         => true,
            'rewrite'           => array( 'slug' => 'topic' ),
            'show_in_rest'      => true,
        ) );
    }

    public function add_meta_boxes() {
        add_meta_box(
            'article_details',
            __( 'Article Details', 'articles' ),
            array( $this, 'render_meta_box' ),
            'article',
            'normal',
            'high'
        );
    }

    public function render_meta_box( $post ) {
        wp_nonce_field( 'article_meta_box', 'article_meta_box_nonce' );

        $reading_time = get_post_meta( $post->ID, '_reading_time', true );
        $featured = get_post_meta( $post->ID, '_featured', true );
        $editors_pick = get_post_meta( $post->ID, '_editors_pick', true );
        ?>
        <p>
            <label for="reading_time"><?php _e( 'Reading Time (minutes):', 'articles' ); ?></label>
            <input type="number" id="reading_time" name="reading_time" value="<?php echo esc_attr( $reading_time ); ?>" min="1" max="60">
        </p>
        <p>
            <label>
                <input type="checkbox" name="featured" value="1" <?php checked( $featured, '1' ); ?>>
                <?php _e( 'Featured Article', 'articles' ); ?>
            </label>
        </p>
        <p>
            <label>
                <input type="checkbox" name="editors_pick" value="1" <?php checked( $editors_pick, '1' ); ?>>
                <?php _e( "Editor's Pick", 'articles' ); ?>
            </label>
        </p>
        <?php
    }

    public function save_meta( $post_id ) {
        // Verify nonce
        if ( ! isset( $_POST['article_meta_box_nonce'] ) || ! wp_verify_nonce( $_POST['article_meta_box_nonce'], 'article_meta_box' ) ) {
            return;
        }

        // Check autosave
        if ( defined( 'DOING_AUTOSAVE' ) && DOING_AUTOSAVE ) {
            return;
        }

        // Check permissions
        if ( ! current_user_can( 'edit_post', $post_id ) ) {
            return;
        }

        // Save reading time
        if ( isset( $_POST['reading_time'] ) ) {
            update_post_meta( $post_id, '_reading_time', absint( $_POST['reading_time'] ) );
        }

        // Save featured status
        $featured = isset( $_POST['featured'] ) ? '1' : '0';
        update_post_meta( $post_id, '_featured', $featured );

        // Save editor's pick status
        $editors_pick = isset( $_POST['editors_pick'] ) ? '1' : '0';
        update_post_meta( $post_id, '_editors_pick', $editors_pick );
    }
}

new Articles_Manager();
```

## Settings API Plugin

```php
<?php
/**
 * Plugin Name: Site Settings Manager
 */

class Site_Settings_Manager {

    private $option_group = 'site_settings';
    private $option_name = 'site_settings_options';

    public function __construct() {
        add_action( 'admin_menu', array( $this, 'add_settings_page' ) );
        add_action( 'admin_init', array( $this, 'register_settings' ) );
    }

    public function add_settings_page() {
        add_options_page(
            __( 'Site Settings', 'site-settings' ),
            __( 'Site Settings', 'site-settings' ),
            'manage_options',
            'site-settings',
            array( $this, 'render_settings_page' )
        );
    }

    public function register_settings() {
        register_setting( $this->option_group, $this->option_name, array( $this, 'sanitize_settings' ) );

        // Add section
        add_settings_section(
            'general_section',
            __( 'General Settings', 'site-settings' ),
            array( $this, 'section_callback' ),
            'site-settings'
        );

        // Add fields
        add_settings_field(
            'newsletter_enabled',
            __( 'Enable Newsletter', 'site-settings' ),
            array( $this, 'checkbox_field' ),
            'site-settings',
            'general_section',
            array( 'id' => 'newsletter_enabled', 'label' => 'Enable newsletter signup' )
        );

        add_settings_field(
            'newsletter_api_key',
            __( 'Newsletter API Key', 'site-settings' ),
            array( $this, 'text_field' ),
            'site-settings',
            'general_section',
            array( 'id' => 'newsletter_api_key', 'placeholder' => 'Enter API key' )
        );
    }

    public function section_callback() {
        echo '<p>' . __( 'Configure general site settings.', 'site-settings' ) . '</p>';
    }

    public function checkbox_field( $args ) {
        $options = get_option( $this->option_name );
        $value = isset( $options[ $args['id'] ] ) ? $options[ $args['id'] ] : '';
        ?>
        <label>
            <input type="checkbox" name="<?php echo $this->option_name; ?>[<?php echo $args['id']; ?>]" value="1" <?php checked( $value, '1' ); ?>>
            <?php echo $args['label']; ?>
        </label>
        <?php
    }

    public function text_field( $args ) {
        $options = get_option( $this->option_name );
        $value = isset( $options[ $args['id'] ] ) ? $options[ $args['id'] ] : '';
        ?>
        <input type="text" name="<?php echo $this->option_name; ?>[<?php echo $args['id']; ?>]" value="<?php echo esc_attr( $value ); ?>" placeholder="<?php echo esc_attr( $args['placeholder'] ); ?>" class="regular-text">
        <?php
    }

    public function sanitize_settings( $input ) {
        $sanitized = array();

        if ( isset( $input['newsletter_enabled'] ) ) {
            $sanitized['newsletter_enabled'] = '1';
        }

        if ( isset( $input['newsletter_api_key'] ) ) {
            $sanitized['newsletter_api_key'] = sanitize_text_field( $input['newsletter_api_key'] );
        }

        return $sanitized;
    }

    public function render_settings_page() {
        ?>
        <div class="wrap">
            <h1><?php echo esc_html( get_admin_page_title() ); ?></h1>
            <form action="options.php" method="post">
                <?php
                settings_fields( $this->option_group );
                do_settings_sections( 'site-settings' );
                submit_button();
                ?>
            </form>
        </div>
        <?php
    }
}

new Site_Settings_Manager();
```

## Ajax Plugin

```php
<?php
/**
 * Plugin Name: Ajax Handler
 */

class Ajax_Handler {

    public function __construct() {
        add_action( 'wp_enqueue_scripts', array( $this, 'enqueue_scripts' ) );
        add_action( 'wp_ajax_load_more_posts', array( $this, 'load_more_posts' ) );
        add_action( 'wp_ajax_nopriv_load_more_posts', array( $this, 'load_more_posts' ) );
    }

    public function enqueue_scripts() {
        wp_enqueue_script( 'ajax-handler', plugin_dir_url( __FILE__ ) . 'assets/ajax.js', array( 'jquery' ), '1.0.0', true );

        wp_localize_script( 'ajax-handler', 'ajaxData', array(
            'ajaxurl' => admin_url( 'admin-ajax.php' ),
            'nonce'   => wp_create_nonce( 'load_more_nonce' )
        ) );
    }

    public function load_more_posts() {
        // Verify nonce
        check_ajax_referer( 'load_more_nonce', 'nonce' );

        $paged = isset( $_POST['page'] ) ? absint( $_POST['page'] ) : 1;

        $args = array(
            'post_type'      => 'post',
            'posts_per_page' => 6,
            'paged'          => $paged,
        );

        $query = new WP_Query( $args );

        if ( $query->have_posts() ) {
            ob_start();
            while ( $query->have_posts() ) {
                $query->the_post();
                get_template_part( 'template-parts/content', 'card' );
            }
            $html = ob_get_clean();

            wp_send_json_success( array(
                'html'      => $html,
                'has_more'  => $paged < $query->max_num_pages,
                'next_page' => $paged + 1
            ) );
        } else {
            wp_send_json_error( array(
                'message' => __( 'No more posts found.', 'ajax-handler' )
            ) );
        }

        wp_reset_postdata();
        wp_die();
    }
}

new Ajax_Handler();
```

### JavaScript for Ajax (assets/ajax.js)

```javascript
jQuery(document).ready(function($) {
    var page = 2;
    var loading = false;

    $('#load-more').on('click', function(e) {
        e.preventDefault();

        if (loading) return;
        loading = true;

        $(this).text('Loading...');

        $.ajax({
            url: ajaxData.ajaxurl,
            type: 'POST',
            data: {
                action: 'load_more_posts',
                page: page,
                nonce: ajaxData.nonce
            },
            success: function(response) {
                if (response.success) {
                    $('#articles-container').append(response.data.html);
                    page = response.data.next_page;

                    if (!response.data.has_more) {
                        $('#load-more').hide();
                    } else {
                        $('#load-more').text('Load More');
                    }
                } else {
                    $('#load-more').text('No More Posts');
                }
                loading = false;
            },
            error: function() {
                $('#load-more').text('Error - Try Again');
                loading = false;
            }
        });
    });
});
```

## Database Plugin

```php
<?php
/**
 * Plugin Name: Custom Database Plugin
 */

class Custom_Database {

    private $table_name;

    public function __construct() {
        global $wpdb;
        $this->table_name = $wpdb->prefix . 'custom_data';

        register_activation_hook( __FILE__, array( $this, 'create_table' ) );
    }

    public function create_table() {
        global $wpdb;

        $charset_collate = $wpdb->get_charset_collate();

        $sql = "CREATE TABLE {$this->table_name} (
            id bigint(20) NOT NULL AUTO_INCREMENT,
            user_id bigint(20) NOT NULL,
            data_value text NOT NULL,
            created_at datetime DEFAULT CURRENT_TIMESTAMP,
            PRIMARY KEY  (id),
            KEY user_id (user_id)
        ) $charset_collate;";

        require_once( ABSPATH . 'wp-admin/includes/upgrade.php' );
        dbDelta( $sql );
    }

    public function insert_data( $user_id, $data_value ) {
        global $wpdb;

        return $wpdb->insert(
            $this->table_name,
            array(
                'user_id'    => $user_id,
                'data_value' => $data_value,
            ),
            array( '%d', '%s' )
        );
    }

    public function get_data( $user_id ) {
        global $wpdb;

        return $wpdb->get_results(
            $wpdb->prepare(
                "SELECT * FROM {$this->table_name} WHERE user_id = %d ORDER BY created_at DESC",
                $user_id
            )
        );
    }

    public function delete_data( $id ) {
        global $wpdb;

        return $wpdb->delete(
            $this->table_name,
            array( 'id' => $id ),
            array( '%d' )
        );
    }
}

new Custom_Database();
```
