<?php

//Prevents file from being accessed directly.
if ( ! defined( 'ABSPATH' ) ) exit;

class WPGamify_Mission_Schema {

    /** Capability type to use when registering the custom post type. */
    private $post_capability_type;

    /** Name to use when registering the custom post type. */
    private $post_type_name;
    
    /** Label to identify post type */
    private $post_type_label;
    
    private $all_missions = array();
    
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

    private function set_post_type_name($new_val = 'mission') {
        $this->post_type_name = apply_filters('wpgamify_badge_template_post_type_name', $new_val);
    }

    private function set_post_type_label($new_val = 'Mission') {
        $this->post_type_label = apply_filters('wpgamify_badge_template_post_type_label', $new_val);
    }
    
    function __construct() {
        add_action('init', array($this, 'init'));
        add_action('load-post.php', array($this, 'meta_boxes_setup'));
        add_action('load-post-new.php', array($this, 'meta_boxes_setup'));

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
            'menu_icon' => plugins_url() . '/gamify/images/moebius-triangle.png',
            'capability_type' => $this->get_post_capability_type(),
            'has_archive' => true,
            'hierarchical' => false,
            'supports' => array('title'),
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
    
    function add_mission($value, $label){
        $this->all_missions[$value] = $label;
    }
    
    function meta_boxes_add() {
        add_meta_box(
                'wpgamify_mission_type', // Unique ID
                esc_html__('Mission Type', 'wpgamify'), // Title
                array($this, 'meta_box_type'), // Callback function
                $this->get_post_type_name(), // Admin page (or post type)
                'normal', // Context
                'core'      // Priority
        );
        add_meta_box(
                'wpgamify_mission_options', // Unique ID
                esc_html__('Mission Options', 'wpgamify'), // Title
                array($this, 'meta_box_options'), // Callback function
                $this->get_post_type_name(), // Admin page (or post type)
                'normal', // Context
                'low'      // Priority
        );
        add_meta_box(
                'wpgamify_mission_description', // Unique ID
                esc_html__('Mission Description', 'wpgamify'), // Title
                array($this, 'meta_box_description'), // Callback function
                $this->get_post_type_name(), // Admin page (or post type)
                'normal', // Context
                'low'      // Priority
        );
    }
    
    function meta_boxes_setup() {
        add_action('add_meta_boxes', array($this, 'meta_boxes_add'));
        add_action('save_post_'.$this->get_post_type_name(), array($this, 'save_post'), 10, 2);
        add_action('pre_post_update', array($this, 'save_post'), 10, 2);
    }

    function meta_box_type($object, $box) {
        wp_nonce_field(basename(__FILE__), 'wpgamify_mission_nonce');
        $current_mission = esc_attr(get_post_meta($object->ID, 'wpgamify_mission_type', true));
        echo '<label for="wpgamify-award-choose-badge">'. $this->get_post_type_label().': </label>';
        echo "<select name='wpgamify_mission_type' id='wpgamify_mission_type'>";
        foreach($this->all_missions as $k => $v){
            if($k == $current_mission){
                echo '<option value="'.$k.'" selected="selected" selected>'.$v.'</option>';
            }else{
                echo '<option value="'.$k.'">'.$v.'</option>';
            }
        }
        echo '</select>';
    }
    
    function meta_box_options($object, $box) {
        $current_mission = esc_attr(get_post_meta($object->ID, 'wpgamify_mission_type', true));
        if(empty($current_mission) || !array_key_exists($current_mission, $this->all_missions)){
            echo "<p>Choose a mission type and save to get mission options</p>";
        }else{
            $description = apply_filters("wpgamify_mission_".$current_mission."_options",'',$object->ID);
            echo $description;
        }
    }
    
    function meta_box_description($object, $box) {
        if (get_post_type() != $this->get_post_type_name())
            return;
        ?>
        <p>
            <label class="screen-reader-text" id="wpgamify_mission_description_prompt_text" for="wpgamify_mission_description"><?php _e("Enter description here", "wpgamify") ?></label>
            <textarea type="text" class="widefat" rows="2" name="wpgamify_mission_description" id="wpgamify_mission_description" /><?php esc_attr_e(get_post_meta($object->ID, 'wpgamify_mission_description', true)) ?></textarea>
        </p>
        <?php
    }


    function save_post($post_id, $post){
        $desc_key = 'wpgamify_mission_desc';
        if ($post->post_type != $this->get_post_type_name())
            return $post_id;

        $nonce = filter_input(INPUT_POST, 'wpgamify_mission_nonce');
        if (empty($nonce) || !wp_verify_nonce($nonce, basename(__FILE__))){
            return $post_id;
        }

        $post_type = get_post_type_object($post->post_type);
        if (!current_user_can($post_type->cap->edit_post, $post_id)){
            return $post_id;
        }
        
        $this->update_meta($post_id,'wpgamify_mission_type', filter_input(INPUT_POST, 'wpgamify_mission_type'));
        $this->update_meta($post_id,'wpgamify_mission_description', filter_input(INPUT_POST, 'wpgamify_mission_description'));
        do_action( "wpgamify_save_mission", $post_id );
    }
    
    function update_meta($post_id,$meta_key,$new_value){
        $old_value = get_post_meta($post_id,$meta_key,true);
        if ($new_value && empty($old_value))
            add_post_meta($post_id, $meta_key, $new_value, true);
        elseif (current_user_can('manage_options')) {
            if ($new_value && $new_value != $old_value){
                delete_post_meta($post_id, $meta_key);
                update_post_meta($post_id, $meta_key, $new_value);
            }elseif (empty($new_value)){
                delete_post_meta($post_id, $meta_key);
            }
        }
    }
}

$GLOBALS['wpgamify_mission_schema'] = new WPGamify_Mission_Schema();
?>