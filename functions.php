<?php

/**
 * Registers any plugin dependancies the theme has.
 *
 * Requires TGMPA
 */
function register_plugins () {
	$plugins = array(
		/* Register any required plugins:
		array(
			'name'               => 'Example Plugin', // Required. The plugin name.
			'slug'               => 'example-plugin', // Requried. The plugin slug (typically the folder name).
			'source'             => 'http://example-plugin.com', // The plugin source. Often a .zip file. Do not include this if the plugin is from the Wordpress Repository.
			'required'           => true, // If false, the plugin is only 'recommended' instead of required.
			'version'            => '', // E.g. 1.0.0. If set, the active plugin must be this version or higher. If the plugin version is higher than the plugin version installed, the user will be notified to update the plugin.
			'force_activation'   => false, // If true, plugin is activated upon theme activation and cannot be deactivated until theme switch.
			'force_deactivation' => false, // If true, plugin is deactivated upon theme switch, useful for theme-specific plugins.
			'external_url'       => '', // If set, overrides default API URL and points to an external URL.
			'is_callable'        => '', // If set, this callable will be be checked for availability to determine if a plugin is active.
        ),*/
        array(
            'name' => 'Timber',
            'slug' => 'timber-library',
            'required' => true,
            'force_activation' => true
        ),
		array(
			'name' => 'Advanced Custom Fields Pro',
            'slug' => 'advanced-custom-fields-pro',
            'source' => get_template_directory_uri() . '/includes/plugins/advanced-custom-fields-pro.zip',
			'required' => true,
            'force_activation' => true
        ),
        array(
            'name' => 'Advanced Custom Fields: Font Awesome Field',
            'slug' => 'advanced-custom-fields-font-awesome',
            'required' => true,
            'force_activation' => true
        ),
        array(
            'name' => 'Yoast SEO',
            'slug' => 'wordpress-seo',
            'required' => true,
            'force_activation' => true
        ),
        array(
            'name' => 'Safe SVG',
            'slug' => 'safe-svg',
            'required' => true,
            'force_activation' => true
        ),
        array(
            'name' => 'WPS Hide Login',
            'slug' => 'wps-hide-login',
            'required' => false
        )
	);
	register_required_plugins ($plugins);
}

// Plugin Dependancies
require_once('includes/required-plugins/class-tgm-plugin-activation.php');
require_once('includes/required-plugins/register-plugin.php');

if ( is_admin() && function_exists('register_required_plugins')) {
    add_action ('tgmpa_register', 'register_plugins');
}

if ( ! class_exists( 'Timber' ) ) {
    add_action( 'admin_notices', function() {
        echo '<div class="error"><p>Timber not activated. Make sure you activate the plugin in <a href="' . esc_url( admin_url( 'plugins.php#timber' ) ) . '">' . esc_url( admin_url( 'plugins.php' ) ) . '</a></p></div>';
    } );
    return;
}

Timber::$dirname = array('templates', 'components');

class StarterSite extends TimberSite {

    function __construct() {
        //add_theme_support( 'post-formats' );

        add_theme_support(
			'post-formats',
			array(
				'aside',
				'image',
				'video',
				'quote',
				'link',
				'gallery',
				'audio',
			)
		);


        
        add_theme_support( 'post-thumbnails' );
        add_theme_support( 'menus' );

        // Timber filters
        add_filter( 'timber_context', array( $this, 'add_to_context' ) );
        add_filter( 'get_twig', array( $this, 'add_to_twig' ) );
        add_filter( 'upload_mimes', array($this, 'svg_mime_types' ));

        // Comment out to Enable oEmbed (responsible for embedding twitter etc)
        remove_action( 'wp_head', 'wp_oembed_add_discovery_links', 10 );
        remove_action('wp_head', 'wp_oembed_add_host_js');
        remove_action('rest_api_init', 'wp_oembed_register_route');
        remove_filter('oembed_dataparse', 'wp_filter_oembed_result', 10);


        add_action('init', [$this, 'add_excerpt_support']);

        // Header Removal
        remove_action('wp_head', 'rsd_link');
        remove_action('wp_head', 'wlwmanifest_link');
        remove_action('wp_head', 'wp_generator'); // Hide WP Version for security
        remove_action('wp_head', 'wp_shortlink_wp_head');
        remove_action('wp_head', 'rest_output_link_wp_head', 10); //Remove wp-json/ link
        add_action( 'wp_enqueue_scripts', 'bs_dequeue_dashicons' );
            function bs_dequeue_dashicons() {
                if ( ! is_user_logged_in() ) {
                    wp_deregister_style( 'dashicons' );
                }
            }


        add_filter( 'emoji_svg_url', '__return_false' );

        // Timber Actions
        add_action( 'init', array( $this, 'register_post_types' ) );
        add_action( 'init', array( $this, 'register_taxonomies' ) );
        add_action( 'init', array( $this, 'register_acf_blocks' ) );

        add_action( 'wp_enqueue_scripts', array( $this, 'assets' ) );

        // First party actions
        add_action('inline_file', array($this, 'inline_file'));
        add_action('admin_head', array($this, 'fix_svg_thumb_display'));
        add_action( 'init', 'disable_wp_emojicons' );

        // Add Advanced Custom Fields options page
        if( function_exists('acf_add_options_page') ) {
            acf_add_options_sub_page('Theme'); 
            acf_add_options_sub_page('Analytics/Tracking');

            if (current_user_can('administrator') || get_field('show_debug_menu', 'option')) {
              acf_add_options_sub_page('Debug Options');
            }
        }

        parent::__construct();
    }

    function register_post_types() {
        // require_once custom post types here
        //include_once "includes/post-types/services.php";


        // This is where you can register custom/post types
        $args = array(
            'post_type' => 'post', 
            // 'tax_query' => array(
            //     'relation' => 'AND',
            //     array(
            //         'taxonomy' => 'category',
            //         'field' => 'id',
            //         'terms' => array( 4,5,6,8,9 ),
            //         'operator' => 'NOT IN'
            //     )
            // )
        );
        $context['post_news'] = Timber::get_posts($args);

    }

    function register_taxonomies() {
        // require_once custom taxonomies here
    }

    function add_excerpt_support() {
        // Adding excerpt for all post
        add_post_type_support('posts', 'excerpt');
        add_post_type_support('page', 'excerpt');
        add_post_type_support('career', 'excerpt');
        add_post_type_support('services', 'excerpt');
    }

    function register_acf_blocks() {
        if ( ! function_exists( 'acf_register_block' ) ) {
            return;
        }
        // require_once custom acf blocks here
    }

    function add_to_context( $context ) {
        // $context['current_lang'] = ICL_LANGUAGE_CODE;
        $context['main_menu'] = new TimberMenu('Main Menu');
 
        $context['site'] = $this;
        if (function_exists('get_fields')) {
            $context['options'] = get_fields('option');
        }
        $context['page_stats'] = TimberHelper::start_timer();

        $context['mobile_only'] = is_mobile() && !is_tablet() ? true : false;
        $context['is_mobile'] = is_mobile() ? true : false;
        $context['is_tablet'] = is_tablet() ? true : false;

        return $context;
    }

    function add_to_twig( $twig ) {
        // Add your own twig functions
        $twig->addFunction( new Twig_SimpleFunction('query_cat', array($this, 'query_cat')));
        $twig->addFilter(new Twig_SimpleFilter('json', array($this, 'json')));
        return $twig;
    }

    function assets( $twig ) {
        // Get rid of default media element
        // wp_deregister_script('wp-mediaelement'); // Uncomment to disable Media Element
        // wp_deregister_style('wp-mediaelement'); // Uncomment to disable Media Element

        // Remove Wp's jQuery
        // wp_deregister_script('jquery'); // Uncomment to disable jQuery

        // Define globals with for cache busting
        require_once 'enqueues.php';
        require('includes/cache_bust.php');

        //wp_enqueue_script( 'essential.js', BUNDLE_JS_SRC, array(), $cache_ver, true); // These will appear at the bottom of the page
        //wp_enqueue_script( 'deferred.js', DEFERRED_BUNDLE_JS_SRC, array(), $cache_ver, true); // These will appear in the footer

        // Enqueue a main stylesheet as a sensible default
        //wp_enqueue_style( 'main.css', MAIN_CSS_SRC, array(), $cache_ver, 'all' );
    }

    /**
     * Inline File
     *
     * This action will echo the contents of a file when passed a relative path, ath
     * the point the function was called.
     *
     * The intended use of this function is for inlining files within templates, for
     * example: embedding an SVG.
     */
    function inline_file($path) {
        if ( $path ) {
            echo file_get_contents($_SERVER['DOCUMENT_ROOT'] . parse_url($path)['path']);
        }
    }

    /**
     * Allows SVGs to be uploaded in the wordpress media library
     */
    function svg_mime_types( $mimes ) {
        $mimes['svg'] = 'image/svg+xml';
        return $mimes;
    }

    /**
     * Limits sizes of SVGs in WordPress backend
     */
    function fix_svg_thumb_display() {
        echo '<style> td.media-icon img[src$=".svg"], img[src$=".svg"].attachment-post-thumbnail { width: 100% !important; height: auto !important; } </style>';
    }

    /**
     * Query Cat
     * Queries passed category id's and limits results to passed limit
     *
     * This is registered as a Timber function and can be called in templates
     * with the following syntax:
     *
     *      {{ query_cat([1, 2, 3], 3) }}
     *
     * This would return posts in categories 1, 2, or 3 and limit the response
     * to 3 results.
     */
    function query_cat(
        $cats = [],
        $limit = 3,
        $post_type = 'any',
        $orderby = 'date',
        $offset = 0,
        $exclude = []
    ) {
        return Timber::get_posts(array(
            'post_type' => $post_type,
            'cat' => $cats,
            'posts_per_page' => $limit,
            'orderby' => $orderby,
            'offset' => $offset,
            'post__not_in' => $exclude
        ));
    }

    /**
     * JSON - Twig Filter
     *
     * Returns object as JSON string
     *
     * Features:
     * - Strips newline characters from String
     * - Escapes and quotes properly, preventing double-encoding of JSON data.
     *
     * Usage:
     *
     *     <script>
     *         var jsonData = '{{ twigObject|json }}';
     *     </script>
     */
    function json($o) {
        return str_replace(array('\r', '\n'), '', str_replace("\u0022","\\\\\"", json_encode($o, JSON_NUMERIC_CHECK | JSON_HEX_QUOT)));
    }

}

new StarterSite();

/*******************************************************************************
 * Global Functions
 ******************************************************************************/

/**
 * Console Log
 *
 * Takes array of strings and returns a javascript console.log.
 */
function console_log($args, $delimiter = ' ') {
    $s = '<script>console.log("';
    $s .= join($delimiter, $args);
    $s .= '")</script>';

    return $s;
}

if (!class_exists('Mobile_Detect'))
    require_once('includes/Mobile_Detect.php');

function is_mobile() {
    $md = new Mobile_Detect();

    if ($md->isMobile() && !$md->isTablet()) {
        return true;
    } else {
        return false;
    }
}

function is_tablet() {

    $md = new Mobile_Detect();

    if ($md->isTablet()) {
        return true;
    } else {
        return false;
    }
}

/**
 * Stop Timber Timer
 *
 * A timer is started at the beginning of every page load that times how long it
 * takes to generate a page. This function stops the timer and reports the
 * following stats using the console_log function:
 *
 * - How long the page took to generate
 * - How many database queries did it take
 */
function stop_timber_timer() {
    $context = Timber::get_context();

    return console_log([
        'Page generated in ' . TimberHelper::stop_timer($context['page_stats']),
        get_num_queries() . ' database queries'
    ]);
}

function custom_login_style() {
  echo '<link rel="stylesheet" type="text/css" href="' . get_bloginfo('stylesheet_directory') . '/login/custom-login-styles.css" />';
}

add_action('login_head', 'custom_login_style');


/**
 * Disables Emjois in TinyMCE
 *
 * Is a filter.
 */
function disable_emojicons_tinymce( $plugins ) {
    if ( is_array( $plugins ) ) {
        return array_diff( $plugins, array( 'wpemoji' ) );
    } else {
        return array();
    }
}

/**
 * Dequeues all scripts and plugins relating to Wordpress emoji defaults
 */
function disable_wp_emojicons() {
    // all actions related to emojis
    remove_action( 'admin_print_styles', 'print_emoji_styles' );
    remove_action( 'wp_head', 'print_emoji_detection_script', 7 );
    remove_action( 'admin_print_scripts', 'print_emoji_detection_script' );
    remove_action( 'wp_print_styles', 'print_emoji_styles' );
    remove_filter( 'wp_mail', 'wp_staticize_emoji_for_email' );
    remove_filter( 'the_content_feed', 'wp_staticize_emoji' );
    remove_filter( 'comment_text_rss', 'wp_staticize_emoji' );

    // filter to remove TinyMCE emojis
    add_filter( 'tiny_mce_plugins', 'disable_emojicons_tinymce' );
}

/*
*   Remove the Back-End code editor
*/
function remove_editor_menu() {
    remove_action('admin_menu', '_add_themes_utility_last', 101);
    if (!function_exists('get_field')) {
        return;
    }
    if (!get_field('enable_comments_menu', 'option')) {
      remove_menu_page( 'edit-comments.php' );
    }
}
add_action('_admin_menu', 'remove_editor_menu', 1);

/*
*   Remove Gutenburg CSS
*/
function remove_block_css(){
    wp_dequeue_style( 'wp-block-library' );
    wp_dequeue_style( 'global-styles' );
}
add_action( 'wp_enqueue_scripts', 'remove_block_css', 100 );

/**
 * Disable Yoast's Hidden love letter about using the WordPress SEO plugin.
 */
add_action( 'template_redirect', function () {

    if ( ! class_exists( 'WPSEO_Frontend' ) ) {
        return;
    }

    $instance = WPSEO_Frontend::get_instance();

    // make sure, future version of the plugin does not break our site.
    if ( ! method_exists( $instance, 'debug_mark') ) {
        return ;
    }

    // ok, let us remove the love letter.
     remove_action( 'wpseo_head', array( $instance, 'debug_mark' ), 2 );
}, 9999 );


/*
*   Remove the detail from the wordpress errors
*/
function no_wordpress_errors() {
    return 'Something is wrong';
}
add_filter('login_errors', 'no_wordpress_errors');


/*
*   Add the async attribute to loaded script tags.
*/
function add_async_attribute($tag, $handle) {
    $scripts_to_async = array('iss-suggest', 'iss', 'addthis');
    foreach($scripts_to_async as $async_script) {
        if($async_script === $handle) {
            return str_replace('src', 'async="async" src', $tag);
        }
    }
    return $tag;
}

add_filter('script_loader_tag', 'add_async_attribute', 10, 2);

/*
*   Replaces the WP logo in the admin bar.
*/
function ec_dashboard_custom_logo() {
    echo '
    <style type="text/css">
        #wpadminbar #wp-admin-bar-wp-logo > .ab-item .ab-icon:before {
        background-image: url(' . get_bloginfo('stylesheet_directory') . '/src/img/admin_logo.svg)
        !important; background-position: 0 0; color:rgba(0, 0, 0, 0);background-size:cover;
    }

    #wpadminbar #wp-admin-bar-wp-logo.hover > .ab-item .ab-icon { background-position: 0 0; }

    </style>
    ';
}
add_action('wp_before_admin_bar_render', 'ec_dashboard_custom_logo');

/*
 *  Noindex Author
 *  Adds a noindex meta tag on author archives so they are not indexed by Google
 */

function noindex_author() {
    if (is_author()) {
        echo '<meta name="robots" content="noindex" />';
    }
}
add_action('wp_head', 'noindex_author');

// add_action('admin_head', 'disable_icl_metabox', 99);
// function disable_icl_metabox() {
//     global $post;
//     remove_meta_box('icl_div_config', $post->posttype, 'normal');
// }

function lower_wpseo_priority($html) {
    return 'low';
}

add_filter('wpseo_metabox_prio', 'lower_wpseo_priority');


// disable code for xmlrpc
add_filter( 'xmlrpc_enabled', '__return_false' );


// Admin menu
function menu() 
{
	add_menu_page( 'Contact Form Entries', 'Contact Form Entries', 'manage_options', 'wpcf7_entries', 'wpcf7_entries' );
}
add_action('admin_menu', 'menu');

// Menu function 

$uri_path = parse_url($_SERVER['REQUEST_URI'], PHP_URL_PATH);
$uri_segments = explode('/', $uri_path);
if(isset($uri_segments[2]) && $uri_segments[2] == 'cronjob_unblock_event_users')
{
   unblock_event_users();
}

// Send Reminder email to registered users
if(isset($_GET['send_reminder_emails']))
{
    send_reminder_emails();
}

$uri_path = parse_url($_SERVER['REQUEST_URI'], PHP_URL_PATH);
$uri_segments = explode('/', $uri_path);
if(isset($uri_segments[2]) && $uri_segments[2] == 'cronjob_send_reminder_emails')
{
    send_reminder_emails();
}
function email_template($activity) //  activity => Sub event name
{ 
    $email_template = file_get_contents(get_template_directory().'/includes/emails/'.$activity.'_template.php');
    return $email_template;
}


// WMPL permalink request
function get_url_for_language($original_url, $language) {
    $original_url   = "https://$_SERVER[HTTP_HOST]$_SERVER[REQUEST_URI]";
    $wpml_permalink = apply_filters('wpml_permalink', $original_url, $language);

    return $wpml_permalink;
}

// pre development Checklist

add_filter( 'xmlrpc_enabled', '__return_false' );

// Add Custom code file
include_once("includes/custom_code.php");
// ----------------------

// Random Number generator
function genTicketString() {

    $posts = get_posts(array(
        'post_type'     => 'flamingo_inbound',
        'post_title' => 'Spreadshop - AI',
        'posts_per_page' => -1
    ));
    $db_random_array=array();
    foreach($posts as $p){
        $id = $p->ID;
        $db_rand_nums = get_post_meta($id, '_field_sequence-generator', true);
        if( !empty($db_rand_nums)){
            array_push($db_random_array, $db_rand_nums);
        }
    }

    do {
        $random_number = substr(str_shuffle("0123456789"), 0, 4);
    } while(in_array($random_number, $db_random_array));

    return $random_number;
}
add_shortcode('ticket', 'genTicketString');



