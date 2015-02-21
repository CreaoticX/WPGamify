<?php
/**
 * Badge custom post type.
 *
 * @package wpgamify
 */

//Prevents file from being accessed directly.
if ( ! defined( 'ABSPATH' ) ) exit;

require_once __DIR__ . '/../classes/ClassBadgeIssued.php';
require_once __DIR__ . '/../classes/ClassBadgeTemplate.php';

/**
 * Implements all the filters and actions needed to make the badge
 * custom post type work.
 */
class WPGamify_Badge_Template_Schema {

    /** Capability type to use when registering the custom post type. */
    private $post_capability_type;

    /** Name to use when registering the custom post type. */
    private $post_type_name;
    
    /** Label to identify post type */
    private $post_type_label;

    /**
     * Constructs the WPGamify Badge Schema instance. It registers all the hooks
     * needed to support the custom post type. This should only be called once.
     */
    function __construct() {
        add_action('init', array($this, 'init'));

        add_action('load-post.php', array($this, 'meta_boxes_setup'));
        add_action('load-post-new.php', array($this, 'meta_boxes_setup'));
        
        add_action('generate_rewrite_rules', array($this, 'generate_rewrite_rules'));
        add_action( 'template_redirect', array($this, 'template_include'));

        /* Filter the content of the badge post type in the display, so badge metadata
          including badge image are displayed on the page. */
        add_filter('the_content', array($this, 'content_filter'));

        /* Filter the title of a badge post type in its display to include version */
        add_filter('the_title', array($this, 'title_filter'), 10, 3);

        add_action('save_post', array($this, 'save_post_validate'), 99, 2);
        add_filter('display_post_states', array($this, 'display_post_states'));
        add_action('admin_notices', array($this, 'admin_notices'));

        add_filter('manage_badge_posts_columns', array($this, 'manage_posts_columns'), 10);
        add_action('manage_badge_posts_custom_column', array($this, 'manage_posts_custom_column'), 10, 2);
    }

    // Accessors and Mutators

    public function get_post_capability_type() {
        return $this->post_capability_type;
    }

    public function get_post_type_name() {
        return $this->post_type_name;
    }

    public function get_post_type_label() {
        return $this->post_type_label;
    }

    private function set_post_capability_type($new_val = 'post') {
        $this->post_capability_type = apply_filters('wpgamify_badge_template_post_capability_type', $new_val);
    }

    private function set_post_type_name($new_val = 'badgetemplate') {
        $this->post_type_name = apply_filters('wpgamify_badge_template_post_type_name', $new_val);
    }

    private function set_post_type_label($new_val = 'Badge Template') {
        $this->post_type_label = apply_filters('wpgamify_badge_template_post_type_label', $new_val);
    }

    // General Filters and Actions

    /**
     * Initialize the custom post type. This registers what we need to
     * support the Badge type.
     */
    function init() {
        $this->set_post_type_name();
        $this->set_post_capability_type();
        $this->set_post_type_label();

        $labels = array(
            'name' => _x($this->get_post_type_label().'s', 'post type general name', 'wpgamify'),
            'singular_name' => _x($this->get_post_type_label(), 'post type singular name', 'wpgamify'),
            'add_new' => _x('Add New', $this->get_post_type_name(), 'wpgamify'),
            'add_new_item' => __('Add New '.$this->get_post_type_label(), 'wpgamify'),
            'edit_item' => __('Edit '.$this->get_post_type_label(), 'wpgamify'),
            'new_item' => __('New '.$this->get_post_type_label(), 'wpgamify'),
            'all_items' => __('All '.$this->get_post_type_label().'s', 'wpgamify'),
            'view_item' => __('View '.$this->get_post_type_label(), 'wpgamify'),
            'search_items' => __('Search '.$this->get_post_type_label().'s', 'wpgamify'),
            'not_found' => __('No '.$this->get_post_type_label().'s found', 'wpgamify'),
            'not_found_in_trash' => __('No '.$this->get_post_type_label().'s found in Trash', 'wpgamify'),
            'parent_item_colon' => '',
            'menu_name' => __($this->get_post_type_label().'s', 'wpgamify')
        );

        $args = array(
            'labels' => $labels,
            'public' => true,
            'query_var' => true,
            'rewrite' => array(
                'slug' => $this->get_post_type_name(),
                'with_front' => false,
            ),
            'menu_icon' => plugins_url() . '/gamify/images/shield.png',
            'capability_type' => $this->get_post_capability_type(),
            'has_archive' => true,
            'hierarchical' => false,
            'supports' => array('title', 'thumbnail'),
            'taxonomies' => array($this->get_post_type_name().'categories',$this->get_post_type_name().'tags')
        );

        register_post_type($this->get_post_type_name(), $args);

        $taxlabels = array(
            'name' => _x('Categories', 'taxonomy general name', 'wpgamify'),
            'singular_name' => _x('Category', 'taxonomy singular name', 'wpgamify'),
            'search_items' => __('Search Category', 'wpgamify'),
            'all_items' => __('All Categories', 'wpgamify'),
            'parent_item' => __('Parent Category', 'wpgamify'),
            'parent_item_colon' => __('Parent Category:', 'wpgamify'),
            'edit_item' => __('Edit Category', 'wpgamify'),
            'update_item' => __('Update Category', 'wpgamify'),
            'add_new_item' => __('Add New Category', 'wpgamify'),
            'new_item_name' => __('New Category Name', 'wpgamify'),
            'menu_name' => __('Categories', 'wpgamify'),
        );
        register_taxonomy(
            $this->get_post_type_name().'categories', 
            array($this->get_post_type_name()), 
            array(
                'hierarchical' => true,
                'labels' => $taxlabels,
                'show_ui' => true,
                'show_admin_column' => true,
                'query_var' => true,
                'rewrite' => array('slug' => 'categories')
            )
        );

        $taglabels = array(
            'name' => _x('Tags', 'taxonomy general name', 'wpgamify'),
            'singular_name' => _x('Tag', 'taxonomy singular name', 'wpgamify'),
            'search_items' => __('Search Tags', 'wpgamify'),
            'popular_items' => __('Popular Tags', 'wpgamify'),
            'all_items' => __('All Tags', 'wpgamify'),
            'parent_item' => null,
            'parent_item_colon' => null,
            'edit_item' => __('Edit Tag', 'wpgamify'),
            'update_item' => __('Update Tag', 'wpgamify'),
            'add_new_item' => __('Add New Tag', 'wpgamify'),
            'new_item_name' => __('New Tag Name', 'wpgamify'),
            'separate_items_with_commas' => __('Separate tags with commas', 'wpgamify'),
            'add_or_remove_items' => __('Add or remove tags', 'wpgamify'),
            'choose_from_most_used' => __('Choose from the most used tags', 'wpgamify'),
            'menu_name' => __('Tags', 'wpgamify'),
        );

        register_taxonomy(
            $this->get_post_type_name().'tags', 
            array($this->get_post_type_name()), 
            array(
                'hierarchical' => false,
                'labels' => $taglabels,
                'show_ui' => true,
                'show_admin_column' => true,
                'query_var' => true,
                'rewrite' => array('slug' => 'tags'),
            )
        );


        # Actions and filters that depend on the post_type name, so can't run
        # until here
    }

    function generate_rewrite_rules($wp_rewrite) {
        $rules = array(
            // Create rewrite rules for each action
            $this->get_post_type_name() . '/([^/]+)/?$' =>
            'index.php?post_type=' . $this->get_post_type_name() . '&name=' . $wp_rewrite->preg_index(1),
            $this->get_post_type_name() . '/([^/]+)/json/?$' =>
            'index.php?post_type=' . $this->get_post_type_name() . '&name=' . $wp_rewrite->preg_index(1) . '&json=1',
            $this->get_post_type_name() . '/([^/]+)/issuer/?$' =>
            'index.php?post_type=' . $this->get_post_type_name() . '&name=' . $wp_rewrite->preg_index(1) . '&issuer=1',
        );

        // Merge new rewrite rules with existing
        $wp_rewrite->rules = array_merge($rules, $wp_rewrite->rules);

        return $wp_rewrite;
    }

    function template_include() {
        $json = get_query_var('json');
        if (get_post_type() == $this->get_post_type_name() && $json){
            include dirname(__FILE__) . '/badge_json.php';
            exit;
        }
        $json = get_query_var('issuer');
        if (get_post_type() == $this->get_post_type_name() && $json){
            include dirname(__FILE__) . '/issuer_json.php';
            exit;
        }
//        global $template;
//
//        if (get_post_type() != $this->get_post_type_name()){
//            return $template;
//        }
//
//        $json = get_query_var('json');
//
//        if ($json){
//            return dirname(__FILE__) . '/badge_json.php';
//        }
//
//        return $template;
    }

    // Loop Filters and Actions

    /**
     * Adds the badge image to the content when we are in The Loop.
     */
    function content_filter($content) {
        if (get_post_type() == $this->get_post_type_name() && in_the_loop())
            return '<p>' . get_the_post_thumbnail(get_the_ID(), 'thumbnail', array('class' => 'alignright')) . $content . '</p>';
        else
            return $content;
    }

    /**
     * Adds the badge version to the title when we are in The Loop.
     */
    function title_filter($title) {
        if (get_post_type() == $this->get_post_type_name() && in_the_loop())
            return $title . ' (Version ' . get_post_meta(get_the_ID(), 'wpgamify_badge_version', true) . ')';
        else
            return $title;
    }

    // Admin Filters and Actions

    /**
     * Display admin notices about invalid posts.
     */
    function admin_notices() {
        global $pagenow, $post;

        if ($pagenow != 'post.php')
            return;
        if (empty($post) || ($post->post_type != $this->get_post_type_name()))
            return;
        if ($post->post_status != 'publish')
            return;

        $valid = $this->check_valid($post->ID, $post);

        if (!$valid['image'])
            echo '<div class="error"><p>' . __("You must set a badge image that is a PNG file.", 'wpgamify') . '</p></div>';
        if (!$valid['description'])
            echo '<div class="error"><p>' . __("You must enter a badge description.", 'wpgamify') . '</p></div>';
        if (!$valid['description_length'])
            echo '<div class="error"><p>' . __("The description cannot be longer than 128 characters.", 'wpgamify') . '</p></div>';
        if (!$valid['criteria'])
            echo '<div class="error"><p>' . __("You must enter the badge criteria.", 'wpgamify') . '</p></div>';
    }

    /**
     * Checks that a badge post is valid. Returns an array with the parts checked, and
     * an overall results. Array keys:
     *
     * - image
     * - description
     * - description-length
     * - criteria
     * - status
     * - all
     *
     * @return array
     */
    function check_valid($post_id, $post = null) {
        if (is_null($post))
            $post = get_post($post_id);

        $rv = array(
            'image' => false,
            'description' => false,
            'description_length' => false,
            'criteria' => false,
            'status' => false
        );

        # Check for post image, and that it is a PNG
        $image_id = get_post_thumbnail_id($post_id);
        if ($image_id > 0) {
            $image_file = get_attached_file($image_id);
            if (!empty($image_file)) {
                $image_ext = pathinfo($image_file, PATHINFO_EXTENSION);
                if (strtolower($image_ext) == 'png')
                    $rv['image'] = true;
            }
        }

        # Check that the description is not empty.
        $desc = get_post_meta($post_id, 'wpgamify_badge_description', true);
        if (!empty($desc))
            $rv['description'] = true;
        if (strlen($desc) <= 128)
            $rv['description_length'] = true;

        # Check that the criteria is not empty.
        $criteria = get_post_meta($post_id, 'wpgamify_badge_criteria', true);
        if (!empty($criteria))
            $rv['criteria'] = true;

        if ($post->post_status == 'publish')
            $rv['status'] = true;

        $rv['all'] = $rv['image'] && $rv['description'] && $rv['description_length'] && $rv['criteria'] && $rv['status'];

        return $rv;
    }

    /**
     * Add a simple description metabox. We can't place this where we want
     * directly in the page, so just dump it wherever and use JS to reposition it.
     *
     * Also, since we're going to re-enable the media buttons, add the label for the criteria
     * box.
     */
    function description_meta_box() {
        if (get_post_type() != $this->get_post_type_name())
            return;
        ?>
        <div id="wpgamify-badge-descriptiondiv"><div id="wpgamify-badge-descriptionwrap">
                <h2>Description</h2>
                <label class="screen-reader-text" id="wpgamify_badge_description_prompt_text" for="wpgamify_badge_description"><?php _e("Enter description here", "wpgamify") ?></label>
                <input type="text" class="widefat" name="wpgamify_badge_description" id="wpgamify_badge_description" value="<?php $this->get_post_description(get_the_ID()) ?>" />
            </div></div>
        <?php
    }

    /**
     * If the badge is invalid, add it to the list of post states.
     */
    function display_post_states($post_states) {
        if (get_post_type() != $this->get_post_type_name())
            return $post_states;

        if (get_post_status() == 'publish') {
            $valid = get_post_meta(get_the_ID(), 'wpgamify_badge_valid', true);
            if (!$valid)
                $post_states['wpgamify_badge_state'] = '<span class="wpgamify_badge_state_invalid">' . __("Invalid", 'wpgamify') . '</span>';
        }

        return $post_states;
    }

    /**
     * Get the badge description metadata. For legacy reasons, this will
     * try to use the post_content if the description metadata isn't present.
     */
    function get_post_description($post_id, $post = null) {
        if (is_null($post))
            $post = get_post($post_id);

        $desc = get_post_meta($post_id, 'wpgamify_badge_description', true);
        if (empty($desc)) {
            $desc = strip_tags($post->post_content);
            $desc = str_replace(array("\r", "\n"), '', $desc);
        }

        return $desc;
    }

    /**
     * Modify the Feature Image metabox to be called the Badge Image.
     */
    function image_meta_box() {
        global $wp_meta_boxes;

        unset($wp_meta_boxes['post']['side']['core']['postimagediv']);
        add_meta_box(
                'postimagediv', esc_html__('Badge Image', 'wpgamify'), 'post_thumbnail_meta_box', $this->get_post_type_name(), 'normal', 'low'
        );
    }

    /**
     * Add the badge version column to the table listing badges.
     */
    function manage_posts_columns($defaults) {
        $defaults['badge_version'] = 'Badge Version';
        return $defaults;
    }

    /**
     * Echo data for the badge version when displaying the table.
     */
    function manage_posts_custom_column($column_name, $post_id) {
        if ($column_name == 'badge_version')
            esc_html_e(get_post_meta($post_id, 'wpgamify_badge_version', true));
    }

    /**
     * Display the Badge Version metabox.
     */
    function meta_box_version($object, $box) {
        wp_nonce_field(basename(__FILE__), 'wpgamify_badge_nonce');
        ?>
        <p>
            <input class="widefat" type="text" name="wpgamify_badge_version" id="wpgamify_badge_version" value="<?php esc_attr_e(get_post_meta($object->ID, 'wpgamify_badge_version', true)); ?>" size="30" />
        </p>
        <?php
    }

    /**
     * Display the Badge Criteria metabox.
     */
    function meta_box_criteria() {
        if (get_post_type() != $this->get_post_type_name())
            return;

        $post = get_post();
        ?>
        <p>
            <label class="screen-reader-text" id="wpgamify_badge_description_prompt_text" for="wpgamify_badge_criteria"><?php _e("Enter criteria here", "wpgamify") ?></label>
            <textarea type="text" class="widefat" rows="2" name="wpgamify_badge_criteria" id="wpgamify_badge_criteria" /><?php esc_attr_e(get_post_meta(get_the_ID(), 'wpgamify_badge_criteria', true)) ?></textarea>
        </p>
        <?php
    }

    /**
     * Display the Badge Description metabox.
     */
    function meta_box_description() {
        if (get_post_type() != $this->get_post_type_name())
            return;
        ?>
        <p>
            <label class="screen-reader-text" id="wpgamify_badge_description_prompt_text" for="wpgamify_badge_description"><?php _e("Enter description here", "wpgamify") ?></label>
            <textarea type="text" class="widefat" rows="2" name="wpgamify_badge_description" id="wpgamify_badge_description" /><?php esc_attr_e(get_post_meta(get_the_ID(), 'wpgamify_badge_description', true)) ?></textarea>
        </p>
        <?php
    }

    /**
     * Add the meta boxes to the badge post editor page.
     */
    function meta_boxes_add() {
        add_meta_box(
                'wpgamify_badge_version', // Unique ID
                esc_html__('Badge Version', 'wpgamify'), // Title
                array($this, 'meta_box_version'), // Callback function
                $this->get_post_type_name(), // Admin page (or post type)
                'normal', // Context
                'low'      // Priority
        );
        add_meta_box(
                'wpgamify_badge_description', // Unique ID
                esc_html__('Badge Description', 'wpgamify'), // Title
                array($this, 'meta_box_description'), // Callback function
                $this->get_post_type_name(), // Admin page (or post type)
                'normal', // Context
                'core'      // Priority
        );
        add_meta_box(
                'wpgamify_badge_criteria', // Unique ID
                esc_html__('Badge Criteria', 'wpgamify'), // Title
                array($this, 'meta_box_criteria'), // Callback function
                $this->get_post_type_name(), // Admin page (or post type)
                'normal', // Context
                'core'      // Priority
        );
    }

    /**
     * Add the action hooks needed to support badge post editor metaboxes.
     */
    function meta_boxes_setup() {
        add_action('add_meta_boxes', array($this, 'meta_boxes_add'));
        add_action('add_meta_boxes', array($this, 'image_meta_box'), 0);
        //add_action( 'edit_form_advanced', array( $this, 'description_meta_box' ) );

        add_action('save_post_'.$this->get_post_type_name(), array($this, 'save_post'), 50, 2);
        //add_action( 'pre_post_update', array( $this, 'save_post' ), 10, 2 );
    }

    /**
     * Save the meta information for a badge post.
     */
    function save_post($post_id, $post) {
        $post = get_post($post_id);
        if ( defined( 'DOING_AUTOSAVE' ) && DOING_AUTOSAVE ) {
            return $post_id;
        }
        if ($post->post_type != $this->get_post_type_name())
            return $post_id;

        $nonce = filter_input(INPUT_POST, 'wpgamify_badge_nonce');
        if (empty($nonce) || !wp_verify_nonce($nonce, basename(__FILE__))){
            return $post_id;
        }

        $post_type = get_post_type_object($post->post_type);
        if (!current_user_can($post_type->cap->edit_post, $post_id)){
            return $post_id;
        }
        
        $bt = new GamifyBadgeTemplate();
        $bt->load_by_key($post_id);
        $bt->set_value("version", filter_input(INPUT_POST, 'wpgamify_badge_version'));
        $bt->set_value("description", filter_input(INPUT_POST, 'wpgamify_badge_description'));
        $bt->set_value("criteria", filter_input(INPUT_POST, 'wpgamify_badge_criteria'));
        $bt->save_meta();

    }

    /**
     * Validate the post metadata and mark it as valid or not.
     */
    function save_post_validate($post_id, $post) {
        if ($post->post_type != $this->get_post_type_name())
            return;

        $valid = $this->check_valid($post_id, $post);

        update_post_meta($post_id, 'wpgamify_badge_valid', $valid['all']);
    }

}

$GLOBALS['wpgamify_badge_template_schema'] = new WPGamify_Badge_Template_Schema();

