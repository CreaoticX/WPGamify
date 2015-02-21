<?php
/**
 * Award custom post type.
 *
 * @package wpgamify
 */
//Prevents file from being accessed directly.
if (!defined('ABSPATH'))
    exit;

require_once __DIR__ . '/../classes/ClassBadgeIssued.php';
require_once __DIR__ . '/../classes/ClassBadgeTemplate.php';

/**
 * Implements all the filters and actions needed to make the award
 * custom post type work.
 */
class WPGamify_Issued_Badge_Schema {

    /** Capability type to use when registering the custom post type. */
    private $post_capability_type;

    /** Name to use when registering the custom post type. */
    private $post_type_name;

    /** Label to identify post type */
    private $post_type_label;

    /**
     * Constructs the WPGamify Award Schema instance. It registers all the hooks
     * needed to support the custom post type. This should only be called once.
     */
    function __construct() {
        add_action('init', array($this, 'init'));

        add_action('load-post.php', array($this, 'meta_boxes_setup'));
        add_action('load-post-new.php', array($this, 'meta_boxes_setup'));

        // Add rewrite rules
        add_action('generate_rewrite_rules', array($this, 'generate_rewrite_rules'));

        add_action('parse_request', array($this, 'parse_request'));
        add_filter('posts_search', array($this, 'posts_search'), 10, 2);

        add_filter('template_include', array($this, 'template_include'));

        add_action('wp_ajax_nopriv_wpgamify_award_ajax', array($this, 'ajax'));

        add_action('publish_post', array($this, 'send_email'));

        // Runs before saving a new post, and filters the post data
        add_filter('wp_insert_post_data', array($this, 'save_title'), '99', 2);

        // Runs before saving a new post, and filters the post slug
        add_filter('name_save_pre', array($this, 'save_slug'));

        add_filter('the_content', array($this, 'content_filter'));

        add_action('save_post', array($this, 'save_post_validate'), 99, 2);
        add_filter('display_post_states', array($this, 'display_post_states'));
        add_action('admin_notices', array($this, 'admin_notices'));
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
        $this->post_capability_type = apply_filters('wpgamify_badge_issued_post_capability_type', $new_val);
    }

    private function set_post_type_name($new_val = 'badgeissued') {
        $this->post_type_name = apply_filters('wpgamify_badge_issued_post_type_name', $new_val);
    }

    private function set_post_type_label($new_val = 'Issued Badge') {
        $this->post_type_label = apply_filters('wpgamify_badge_issued_post_type_label', $new_val);
    }

    // General Filters and Actions

    /**
     * Add rewrite tags
     *
     * @since 1.2
     */
    function add_rewrite_tags() {
        add_rewrite_tag('%%accept%%', '([1]{1,})');
        add_rewrite_tag('%%json%%', '([1]{1,})');
        add_rewrite_tag('%%issuer%%', '([1]{1,})');
        add_rewrite_tag('%%reject%%', '([1]{1,})');
    }

    function ajax() {
        $award_id = intval(filter_input(INPUT_POST, 'award_id'));
        $award = get_post($award_id);
        if (is_null($award) || $award->post_type != $this->get_post_type_name())
            die();

        $admin_email = get_settings('admin_email');

        header('Content-Type: text/html');

        # Only actions are valid on awards that haven't been accepted or
        # rejected yet
        $award_status = get_post_meta($award_id, 'wpgamify-award-status', true);
        if ($award_status != 'Awarded') {
            ?>
            <div class="wpgamify-award-error">
                <p>This award has already been claimed.</p>
                <p>If you believe this was done in error, please contact the 
                    <a href="mailto:<?php esc_attr_e($admin_email) ?>">site administrator</a>.</p>
            </div>
            <?php
            die();
        }

        switch (filter_input(INPUT_POST, 'award_action')) {
            case 'accept':
                update_post_meta($award_id, 'wpgamify-award-status', 'Accepted');

                // If WP Super Cache Plugin installed, delete cache files for award post
                if (function_exists('wp_cache_post_change'))
                    wp_cache_post_change($award_id);
                ?>
                <div class="wpgamify-award-updated">
                    <p>You have successfully accepted to add your award to your backpack.</p>
                </div>
                <?php
                break;

            case 'reject':
                update_post_meta($award_id, 'wpgamify-award-status', 'Rejected');

                // If WP Super Cache Plugin installed, delete cache files for award post
                if (function_exists('wp_cache_post_change'))
                    wp_cache_post_change($award_id);
                ?>
                <div class="wpgamify-award-updated">
                    <p>You have successfully declined to add your award to your backpack.</p>
                </div>
                <?php
                break;
        }

        die();
    }

    function content_filter($content) {
        if (get_post_type() != $this->get_post_type_name())
            return $content;

        $post_id = get_the_ID();

        $award_status = get_post_meta($post_id, 'wpgamify-award-status', true);

        if ($award_status == 'Awarded') {
            $badge_title = esc_html(get_the_title(get_post_meta($post_id, 'wpgamify-award-choose-badge', true)));

            $content = <<<EOHTML
                <div id="wpgamify-award-actions-wrap">
                <div id="wpgamify-award-actions" class="wpgamify-award-notice">
                    <p>Congratulations! The "{$badge_title}" badge has been awarded to you.</p>
                    <p>Please choose to <a href='#' class='acceptBadge'>accept</a> or <a href='#' class='rejectBadge'>decline</a> the award.</p>
                </div>
                <div id="wpgamify-award-browser-support" class="wpgamify-award-error">
                    <p>Microsoft Internet Explorer is not supported at this time. Please use Firefox or Chrome to retrieve your award.</p>
                </div>
                <div id="wpgamify-award-actions-errors" class="wpgamify-award-error">
                    <p>An error occured while adding this badge to your backpack.</p>
                </div>
                </div>
                {$content}
EOHTML;
        } elseif ($award_status == 'Rejected') {
            $content = "<div class='wpgamify-award-notice'><p>This award has been declined.</p></div>";
        }

        return $content;
    }

    /**
     * Generates custom rewrite rules
     *
     * @since 1.2
     */
    function generate_rewrite_rules($wp_rewrite) {
        $rules = array(
            // Create rewrite rules for each action
            $this->get_post_type_name() . '/([^/]+)/?$' =>
            'index.php?post_type=' . $this->get_post_type_name() . '&name=' . $wp_rewrite->preg_index(1),
            $this->get_post_type_name() . '/([^/]+)/accept/?$' =>
            'index.php?post_type=' . $this->get_post_type_name() . '&name=' . $wp_rewrite->preg_index(1) . '&accept=1',
            $this->get_post_type_name() . '/([^/]+)/json/?$' =>
            'index.php?post_type=' . $this->get_post_type_name() . '&name=' . $wp_rewrite->preg_index(1) . '&json=1',
            $this->get_post_type_name() . '/([^/]+)/reject/?$' =>
            'index.php?post_type=' . $this->get_post_type_name() . '&name=' . $wp_rewrite->preg_index(1) . '&reject=1',
        );

        // Merge new rewrite rules with existing
        $wp_rewrite->rules = array_merge($rules, $wp_rewrite->rules);

        return $wp_rewrite;
    }

    /**
     * Initialize the custom post type. This registers what we need to
     * support the Award type.
     */
    function init() {
        $this->set_post_type_name();
        $this->set_post_capability_type();
        $this->set_post_type_label();

        $labels = array(
            'name' => _x($this->get_post_type_label() . 's', 'post type general name', 'wpgamify'),
            'singular_name' => _x($this->get_post_type_label(), 'post type singular name', 'wpgamify'),
            'add_new' => _x('Add New', $this->get_post_type_name(), 'wpgamify'),
            'add_new_item' => __('Add New ' . $this->get_post_type_label(), 'wpgamify'),
            'edit_item' => __('Edit ' . $this->get_post_type_label(), 'wpgamify'),
            'new_item' => __('New ' . $this->get_post_type_label(), 'wpgamify'),
            'all_items' => __('All ' . $this->get_post_type_label() . "s", 'wpgamify'),
            'view_item' => __('View ' . $this->get_post_type_label(), 'wpgamify'),
            'search_items' => __('Search ' . $this->get_post_type_label() . "s", 'wpgamify'),
            'not_found' => __('No ' . $this->get_post_type_label() . 's found', 'wpgamify'),
            'not_found_in_trash' => __('No ' . $this->get_post_type_label() . 's found in Trash', 'wpgamify'),
            'parent_item_colon' => '',
            'menu_name' => __($this->get_post_type_label() . 's', 'wpgamify')
        );

        $args = array(
            'labels' => $labels,
            'public' => true,
            'exclude_from_search' => true,
            'query_var' => true,
            'rewrite' => array(
                'slug' => $this->get_post_type_name(),
                'with_front' => false
            ),
            'menu_icon' => plugins_url() . '/gamify/images/checked-shield.png',
            'capability_type' => $this->get_post_capability_type(),
            'has_archive' => false,
            'hierarchical' => false,
            'supports' => array('editor')
        );

        register_post_type($this->get_post_type_name(), $args);

        $this->add_rewrite_tags();
        add_filter('manage_' . $this->get_post_type_name() . '_posts_columns', array($this, 'manage_posts_columns'), 10);
        add_action('manage_' . $this->get_post_type_name() . '_posts_custom_column', array($this, 'manage_posts_custom_column'), 10, 2);
    }

    /**
     * Limit it so that a user can't request a listing of award posts,
     * unless they happen to be an administrator.
     */
    function parse_request(&$arg) {
        $arg_post_type = $arg->query_vars['post_type'];
        $arg_name = $arg->query_vars['name'];
        $idx = false;

        # Only restrict listings of the award post_type
        if (!isset($arg->query_vars['post_type']))
            return;
        if (is_array($arg_post_type)) {
            $idx = array_search($this->get_post_type_name(), $arg_post_type);
            if ($idx === false)
                return;
        }
        else {
            if ($arg_post_type != $this->get_post_type_name())
                return;
        }

        # Don't restrict the listing if a user is logged in and has permission
        # to edit_posts
        $post_type = get_post_type_object($this->get_post_type_name());
        if (current_user_can($post_type->cap->edit_posts))
            return;

        # Allow only if we're querying by a single name
        if (is_array($arg_name)) {
            $first = reset($arg_name);
            if (count($arg_name) == 1 && !empty($first))
                return;
        }
        else {
            if (!empty($arg_name))
                return;
        }

        # If we reach this point then it's an unpriviledged user querying
        # all the awards. Don't allow this
        if (is_array($arg_post_type))
            unset($arg->query_vars['post_type'][$idx]);
        else
            unset($arg->query_vars['post_type']);
    }

    /**
     * Let admins search awards based on the email address.
     */
    function posts_search($search, &$query) {
        # Only add the metadata in a search
        if (!$query->is_search)
            return $search;
        # Only check for posts that are awards, or might return awards
        $post_type = $query->query_vars['post_type'];
        if (is_array($post_type)) {
            if (count(array_intersect(array('any', $this->get_post_type_name()), $post_type)) == 0)
                return $search;
        }
        else {
            if ($post_type != 'any' && $post_type != $this->get_post_type_name())
                return $search;
        }

        if (is_email($query->query_vars['s'])) {
            # If it is an email then only search on the email address. Clear
            # out the other calculated search
            $query->meta_query->queries[] = array(
                'key' => 'wpgamify-award-email-address',
                'value' => $query->query_vars['s']
            );
            return '';
        } else
            return $search;
    }

    /**
     * Use the JSON template for assertions.
     */
    function template_include() {
        global $template;

        if (get_post_type() != $this->get_post_type_name())
            return $template;

        $json = get_query_var('json');

        if ($json)
            return dirname(__FILE__) . '/awards_json.php';

        return $template;
    }

    // Admin Filters and Actions

    function _generate_title($badge_id) {
        return sprintf(__('Badge Issued: %1$s', 'wpgamify'), get_the_title($badge_id));
    }

    // Generate the award slug. Shared by interface to award single badges, as well as bulk
    function _generate_slug() {
        $slug = '';
        if (function_exists('openssl_random_pseudo_bytes')) {
            $data = openssl_random_pseudo_bytes(16);
            if ($data !== false)
                $slug = bin2hex($data);
        }

        if (!$slug)
            $slug = rand(100000000000000, 999999999999999);

        return $slug;
    }

    /**
     * Display admin notices about invalid posts.
     */
    function admin_notices() {
        global $pagenow, $post;

        if ($pagenow != 'post.php')
            return;
        if (get_post_type() != $this->get_post_type_name())
            return;
        if (get_post_status() != 'publish')
            return;

        $valid = $this->check_valid($post->ID, $post);

        if (!$valid['evidence'])
            echo '<div class="error"><p>' . __("You must specify award evidence.", 'wpgamify') . '</p></div>';
        if (!$valid['badge'])
            echo '<div class="error"><p>' . __("You must choose a badge.", 'wpgamify') . '</p></div>';
        if (!$valid['email'])
            echo '<div class="error"><p>' . __("You must enter an email address for the award.", 'wpgamify') . '</p></div>';
    }

    function bulk_award() {
        $badge_id = intval(filter_input(INPUT_POST, 'wpgamify-award-choose-badge'));
        $email_addresses = filter_input(INPUT_POST, 'wpgamify-award-email-addresses');
        $evidence = filter_input(INPUT_POST, 'content');
        $expires = filter_input(INPUT_POST, 'wpgamify-award-expires');

        if (filter_input(INPUT_POST, 'publish')) {
            check_admin_referer('wpgamify_bulk_award_badges');

            $errors = array();

            if (empty($badge_id))
                $errors[] = __('You must choose a badge to award.', 'wpgamify');

            $emails = array();
            foreach (preg_split('/[\n,]/', $email_addresses, -1, PREG_SPLIT_NO_EMPTY) as $email) {
                $email = trim($email);
                if (!empty($email))
                    $emails[] = $email;
            }

            if (count($emails) == 0)
                $errors[] = __('You must specify at least one email address.', 'wpgamify');
            else {
                foreach ($emails as $email) {
                    if (!is_email($email))
                        $errors[] = sprintf(__('The email address "%1$s" is not valid.', 'wpgamify'), $email);
                }
            }

            $tmp = trim(strip_tags($evidence));
            if (empty($tmp))
                $errors[] = __('You must enter some evidence for the awards.', 'wpgamify');

            if (count($errors) > 0) {
                foreach ($errors as $error) {
                    ?>
                    <div class='error'><p><?php esc_html_e($error) ?></p></div>
                    <?php
                }
            } else {

                $user_ID = get_current_user_id();
                foreach ($emails as $email) {

                    // Insert a new post for each award
                    $bi = new GamifyBadgeIssued();
                    $bi->set_value("email", $email);
                    $bi->set_value("evidence", $evidence);
                    $bi->set_value("post_author", $user_ID);
                    $bi->set_value("name", $this->_generate_slug());
                    $bi->set_value("post_status", 'publish');
                    $bi->set_value("template", $badge_id);
                    $bi->set_value("expires", $expires);
                    $bi->update_db();
                }
                ?>
                <div class="updated">
                    <p>Badges were awarded successfully. You can view a list of
                        <a href="<?php esc_attr_e(admin_url('edit.php?post_type=' . $this->get_post_type_name())) ?>">all awards</a>.
                    </p>
                </div>
                <?php
                $badge_id = 0;
                $email_addresses = '';
                $evidence = '';
                $expires = '';
            }
        }
        ?>
        <h2>Award Badges in Bulk</h2>

        <div class="wrap">
            <form method="POST" action="" name="wpgamify_bulk_award_badges">
                <?php wp_nonce_field('wpgamify_bulk_award_badges'); ?>
                <?php wp_nonce_field(basename(__FILE__), 'wpgamify_award_nonce'); ?>

                <table class="form-table">
                    <tr valign="top">
                        <th scope="row"><label for="wpgamify_award_choose_badge">Badge</label></th>
                        <td>
                            <select name="wpgamify-award-choose-badge" id="wpgamify_award_choose_badge">

                                <?php
                                $query = new WP_Query(array(
                                    'post_type' => 'badge',
                                    'post_status' => 'publish',
                                    'nopaging' => true,
                                    'meta_query' => array(
                                        array(
                                            'key' => 'wpgamify-badge-valid',
                                            'value' => true
                                        )
                                    )
                                ));

                                while ($query->next_post()) {
                                    $title_version = esc_html(get_the_title($query->post->ID) . " (" . get_post_meta($query->post->ID, 'wpgamify-badge-version', true) . ")");

                                    $selected = '';
                                    if ($badge_id == $query->post->ID)
                                        $selected = ' selected="selected"';

                                    echo "<option value='{$query->post->ID}'{$selected}>{$title_version}</option>";
                                }
                                ?>

                            </select>
                        </td>
                    </tr>

                    <tr valign="top">
                        <th scope="row"><label for="wpgamify_award_email_addresses">Email Address</label></th>
                        <td>
                            <textarea name="wpgamify-award-email-addresses" id="wpgamify_award_email_addresses" rows="5" cols="45"><?php echo esc_textarea($email_addresses) ?></textarea>
                            <br />
                            Separate multiple email addresses with commas, or put one per line.
                        </td>
                    </tr>

                    <tr valign="top">
                        <th scope="row"><label for="content">Evidence</label></th>
                        <td><?php wp_editor($evidence, 'content') ?></td>
                    </tr>

                    <tr valign="top">
                        <th scope="row"><label for="wpgamify_award_expires">Expiration Date</label></th>
                        <td>
                            <input type="text" name="wpgamify-award-expires" id="wpgamify_award_expires" value="<?php esc_attr_e($expires) ?>" />
                            <br />
                            Optional. Enter as "YY-MM-DD".</td>
                    </tr>
                </table>

                <p class="submit">
                    <input type="submit" class="button-primary" name="publish" value="<?php _e('Publish') ?>" />
                </p>

            </form>
        </div>
        <?php
    }

    /**
     * Checks that an award post is valid. Returns an array with the parts checked, and
     * an overall results. Array keys:
     *
     * - evidence
     * - email
     * - badge
     * - status
     * - all
     *
     * @return array
     */
    function check_valid($post_id, $post = null) {
        if (is_null($post))
            $post = get_post($post_id);

        $rv = array(
            'evidence' => false,
            'email' => false,
            'badge' => false,
            'status' => false
        );

        # Check that the evidence is not empty. We're going to
        # strip the tags and spaces just to make sure that it isn't
        # empty
        $evidence = trim(strip_tags($post->post_content));
        if (!empty($evidence))
            $rv['evidence'] = true;

        $email = get_post_meta($post_id, 'wpgamify-award-email-address', true);
        if (!empty($email) && is_email($email))
            $rv['email'] = true;

        $badge = get_post_meta($post_id, 'wpgamify-award-choose-badge', true);
        if (!empty($badge))
            $rv['badge'] = true;

        if ($post->post_status == 'publish')
            $rv['status'] = true;

        $rv['all'] = $rv['evidence'] && $rv['email'] && $rv['badge'] && $rv['status'];

        return $rv;
    }

    /**
     * If the award is invalid, add it to the list of post states.
     */
    function display_post_states($post_states) {
        if (get_post_type() != $this->get_post_type_name())
            return $post_states;

        if (get_post_status() == 'publish') {
            $valid = get_post_meta(get_the_ID(), 'wpgamify-award-valid', true);
            if (!$valid)
                $post_states['wpgamify-award-state'] = '<span class="wpgamify-award-state-invalid">' . __("Invalid", 'wpgamify') . '</span>';
        }

        return $post_states;
    }

    function manage_posts_columns($defaults) {
        $defaults['award_email'] = __('Issued To Email', 'wpgamify');
        $defaults['award_status'] = __('Award Status', 'wpgamify');

        return $defaults;
    }

    function manage_posts_custom_column($column_name, $post_id) {
        switch ($column_name) {
            case 'award_email':
                esc_html_e(get_post_meta($post_id, 'wpgamify-award-email-address', true));
                break;

            case 'award_status':
                esc_html_e(get_post_meta($post_id, 'wpgamify-award-status', true));
                break;
        }
    }

    // Create metaboxes for post editor
    function meta_boxes_add() {
        global $_wp_post_type_features;
        add_meta_box(
                'wpgamify-award-information', 
                esc_html__('Award Information', 'wpgamify'), 
                array($this, 'meta_box_information'), 
                $this->get_post_type_name(), 
                'normal', 
                'core'
        );
        add_meta_box(
                'wpgamify_award_evidence', // Unique ID
                esc_html__('Evidence', 'wpgamify'), // Title
                array($this, 'meta_box_evidence'), // Callback function
                $this->get_post_type_name(), // Admin page (or post type)
                'normal', // Context
                'core'      // Priority
        );
        if (isset($_wp_post_type_features[$this->get_post_type_name()]['editor']) && $_wp_post_type_features[$this->get_post_type_name()]['editor']) {
		unset($_wp_post_type_features[$this->get_post_type_name()]['editor']);
		add_meta_box(
			'description_sectionid',
			__('Evidence'),
			array($this, 'inner_custom_box'),
			$this->get_post_type_name(), 
                        'normal', 
                        'core'
		);
	}
    }

    function inner_custom_box( $post ) {
	wp_editor($post->post_content,"content");
    }
    
    function meta_box_information($object, $box) {
        global $wpgamify_badge_template_schema;

        wp_nonce_field(basename(__FILE__), 'wpgamify_award_nonce');

        $is_published = ('publish' == $object->post_status || 'private' == $object->post_status);
        $award_badge_id = get_post_meta($object->ID, 'wpgamify-award-choose-badge', true);
        $award_email = get_post_meta($object->ID, 'wpgamify-award-email-address', true);
        $award_status = get_post_meta($object->ID, 'wpgamify-award-status', true);
        $bt = new GamifyBadgeTemplate();
        ?>
        <div id="wpgamify-award-actions">
            <div class="wpgamify-award-section wpgamify-award-badge">
                <label for="wpgamify-award-choose-badge"><?php echo $this->get_post_type_label(); ?>: </label>
        <?php
        if (!$is_published || current_user_can('manage_options')) {
            echo '<select name="wpgamify-award-choose-badge" id="wpgamify-award-choose-badge">';
            $query = new WP_Query(array('post_type' => $bt->get_post_type(), 'nopaging' => true));
            while ($query->next_post()) {
                $badge_id = $query->post->ID;
                $badge_title_version = get_the_title($badge_id) . " (" . get_post_meta($badge_id, 'wpgamify_badge_version', true) . ")";

                // As we iterate through the list of badges, if the chosen badge has the same ID then mark it as selected
                if ($award_badge_id == $badge_id)
                    $selected = ' selected="selected"';
                else
                    $selected = '';

                $valid = $wpgamify_badge_template_schema->check_valid($badge_id, $query->post);
                if ($valid['all'])
                    $disabled = '';
                else
                    $disabled = ' disabled="disabled"';

                echo "<option value='{$badge_id}'{$selected}{$disabled}>{$badge_title_version}</option>";
            }

            echo '</select>';
        }
        else {
            $badge_title_version = get_the_title($award_badge_id) . " (" . get_post_meta($award_badge_id, 'wpgamify-badge-version', true) . ")";
            echo "<b>" . $badge_title_version . "</b>";
        }
        ?>
        </div>
        <div class="wpgamify-award-section wpgamify-award-email-address">
            <label for="wpgamify-award-email-address">Email Address:</label><br />
            <?php
            if (!$is_published || current_user_can('manage_options'))
                echo '<input type="text" name="wpgamify-award-email-address" id="wpgamify-award-email-address" value="' . esc_attr($award_email) . '" />';
            else
                echo '<b>' . esc_html($award_email) . '</b>';
            ?>
        </div>
            <?php
            if ($is_published) {
                ?>
            <div class="wpgamify-award-section wpgamify-award-status">
                Status: <b><?php echo esc_html($award_status) ?></b>
            </div>
            <?php
        }

        echo '</div>';
    }

    function meta_box_evidence() {
        if (get_post_type() != $this->get_post_type_name())
            return;
        ?>
        <p>
            In the content box below, describe the evidence that this individual earned this badge.
        </p>
        <?php
    }

    function meta_boxes_setup() {
        add_action('add_meta_boxes', array($this, 'meta_boxes_add'));

        add_action('save_post', array($this, 'save_post'), 10, 2);
    }

    function save_post($post_id, $post) {
        $fp = filter_input(INPUT_POST, 'wpgamify_award_nonce');
        if (!isset($fp) || !wp_verify_nonce($fp, basename(__FILE__))){
            return $post_id;
        }

        $post_type = get_post_type_object($post->post_type);

        if (!current_user_can($post_type->cap->edit_post, $post_id)){
            return $post_id;
        }

        $bi = new GamifyBadgeIssued();
        $bi->load_by_key($post_id);
        $bi->set_value("template", filter_input(INPUT_POST, 'wpgamify-award-choose-badge'));
        $bi->set_value("email", filter_input(INPUT_POST, 'wpgamify-award-email-address'));
        $bi->set_value("expires", filter_input(INPUT_POST, 'wpgamify-award-expires'));
        $bi->save_meta();
        if($bi->get_value("post_status")=="publish"){
            do_action("wpgamify_issue_badge");
        }
    }

    function save_post_validate($post_id, $post) {
        if ($post->post_type != $this->get_post_type_name())
            return;

        $valid = $this->check_valid($post_id, $post);

        update_post_meta($post_id, 'wpgamify-award-valid', $valid['all']);
    }

    function save_slug($slug) {
        if ($_REQUEST['post_type'] == $this->get_post_type_name())
            return $this->_generate_slug();

        return $slug;
    }

    function save_title($data, $postarr) {
        if ($postarr['post_type'] != $this->get_post_type_name())
            return $data;

        $fp = filter_input(INPUT_POST, 'wpgamify-award-choose-badge');
        $data['post_title'] = $this->_generate_title($fp);
        return $data;
    }

    function send_email($post_id) {
        // Verify that post has been published, and is an award
        if (get_post_type($post_id) != $this->get_post_type_name()){
            return;
        }
        if (!get_post_meta($post_id, 'wpgamify-award-valid', true)){
            return;
        }
        if (get_post_meta($post_id, 'wpgamify-award-status', true) != 'Awarded'){
            return;
        }

        $badge_id = (int) get_post_meta($post_id, 'wpgamify-award-choose-badge', true);
        if (!$badge_id)
            return;

        $email_address = get_post_meta($post_id, 'wpgamify-award-email-address', true);
        if (get_post_meta($post_id, 'wpgamify-award-email-sent', true) == $email_address)
            return;

        $badge_title = get_the_title($badge_id);
        $badge_url = get_permalink($badge_id);
        $badge_image_id = get_post_thumbnail_id($badge_id);
        $badge_image_url = wp_get_attachment_url($badge_image_id);
        $badge_desc = get_post_meta($badge_id, 'wpgamify-badge-description', true);

        $award = get_post($post_id);
        $award_title = get_the_title($post_id);
        $award_url = get_permalink($post_id);
        $award_evidence = $award->post_content;

        $subject = wpgamify_template(
                get_option('wpgamify_awarded_email_subject'), array(
            'BADGE_TITLE' => $badge_title,
            'AWARD_TITLE' => $award_title
                )
        );
        $subject = apply_filters('wpgamify_awarded_email_subject', $subject);

        $message = wpgamify_template(
                get_option('wpgamify_awarded_email_html'), array(
            'BADGE_TITLE' => esc_html($badge_title),
            'BADGE_URL' => $badge_url,
            'BADGE_IMAGE_URL' => $badge_image_url,
            'BADGE_DESCRIPTION' => esc_html($badge_desc),
            'AWARD_TITLE' => esc_html($award_title),
            'AWARD_URL' => $award_url,
            'EVIDENCE' => $award_evidence
                )
        );

        add_filter('wpgamify_awarded_email_html', 'wptexturize');
        add_filter('wpgamify_awarded_email_html', 'convert_chars');
        add_filter('wpgamify_awarded_email_html', 'wpautop');
        $message = apply_filters('wpgamify_awarded_email_html', $message);

        wp_mail($email_address, $subject, $message, array('Content-Type: text/html'));
        update_post_meta($post_id, 'wpgamify-award-email-sent', $email_address);
    }

}

$GLOBALS['wpgamify_badge_issued_schema'] = new WPGamify_Issued_Badge_Schema();
