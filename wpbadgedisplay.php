<?php
//Prevents file from being accessed directly.
if (!defined('ABSPATH'))
    exit;

require_once 'classes/ClassBadgeIssued.php';
require_once 'classes/ClassBadgeTemplate.php';

class WPGamifyDisplayMyBackpackWidget extends WP_Widget {

    public function __construct() {
        parent::__construct(
                'WPGamifyDisplayMyBackpackWidget', 'WPGamify Display My Backpack', array('description' => __("Display Site Owner's Open Backpack", 'text_domain'),)
        );
    }

    function form($instance) {
        $instance = wp_parse_args((array) $instance, array('title' => ''));
        $title = $instance['title'];
        ?>
        <p><label for="<?php echo $this->get_field_id('title'); ?>">Title: <input class="widefat" id="<?php echo $this->get_field_id('title'); ?>" name="<?php echo $this->get_field_name('title'); ?>" type="text" value="<?php echo attribute_escape($title); ?>" /></label></p>

        <p><label for="openbadges_user_id">Email Account: <input class="widefat" id="openbadges_email" name="openbadges_email" type="text" value="<?php echo get_option('openbadges_email'); ?>" /></label></p>
        <?php
    }

    function update($new_instance, $old_instance) {
        $instance = $old_instance;
        $instance['title'] = $new_instance['title'];
        $openbadges_email = filter_input(INPUT_POST, 'openbadges_email');
        update_option('openbadges_email', $openbadges_email);

        $openbadgesuserid = wpgamify_convert_email_to_openbadges_id($openbadges_email);
        update_option('openbadges_user_id', $openbadgesuserid);

        return $instance;
    }

    function widget($args, $instance) {
        extract($args);
        $title = apply_filters('widget_title', $instance['title']);
        if (!empty($title))
            echo $before_title . $title . $after_title;;

        $badgedata = wpgamify_get_public_backpack_contents(get_option('openbadges_user_id'), null);
        echo wpgamify_return_embed_badges($badgedata);
    }

}

add_action('widgets_init', create_function('', 'return register_widget("WPGamifyDisplayMyBackpackWidget");'));

class WPGamifyDisplayUserBackpackWidget extends WP_Widget {

    public function __construct() {
        parent::__construct(
                'WPGamifyDisplayUserBackpackWidget', 
                "WPGamify Display User's Backpack", 
                array('description' => __("Display Users's Backpack", 'text_domain'),)
        );
    }

    function form($instance) {
        $instance = wp_parse_args((array) $instance, array('title' => ''));
        $title = $instance['title'];
        ?>
        <p><label for="<?php echo $this->get_field_id('title'); ?>">Title: <input class="widefat" id="<?php echo $this->get_field_id('title'); ?>" name="<?php echo $this->get_field_name('title'); ?>" type="text" value="<?php echo attribute_escape($title); ?>" /></label></p>

        <?php
    }

    function update($new_instance, $old_instance) {
        $instance = $old_instance;
        $instance['title'] = $new_instance['title'];

        return $instance;
    }

    function widget($args, $instance) {
        extract($args);
        $title = apply_filters('widget_title', $instance['title']);
        if (!empty($title))
            echo $before_title . $title . $after_title;
        if (is_user_logged_in()) {
            $current_user = wp_get_current_user();
            $openbadgesuserid = wpgamify_convert_email_to_openbadges_id($current_user->user_email);
            $badgedata = wpgamify_get_public_backpack_contents($openbadgesuserid, null);
            echo wpgamify_return_embed_badges($badgedata);
        } else {
            echo '<a href="<?php echo wp_login_url(); ?>" title="Login">Login</a> to display your current badges';
        }
    }

}

add_action('widgets_init', create_function('', 'return register_widget("WPGamifyDisplayUserBackpackWidget");'));

class WPGamifyDisplayUserBadgesWidget extends WP_Widget {

    public function __construct() {
        parent::__construct(
                'WPGamifyDisplayUserBadgesWidget', 
                "WPGamify Display User's Badges", 
                array('description' => __("Display Users's Badges", 'text_domain'),)
        );
    }

    function form($instance) {
        $instance = wp_parse_args((array) $instance, array('title' => ''));
        $title = $instance['title'];
        ?>
        <p><label for="<?php echo $this->get_field_id('title'); ?>">Title: <input class="widefat" id="<?php echo $this->get_field_id('title'); ?>" name="<?php echo $this->get_field_name('title'); ?>" type="text" value="<?php echo attribute_escape($title); ?>" /></label></p>

        <?php
    }

    function update($new_instance, $old_instance) {
        $instance = $old_instance;
        $instance['title'] = $new_instance['title'];

        return $instance;
    }

    function widget($args, $instance) {
        global $wpgamify_badge_issued_schema;
        $login = true;
        extract($args);
        if (is_user_logged_in()) {
                $title = apply_filters('widget_title', $instance['title']);
            if (!empty($title)) {
                echo $before_title . $title . $after_title;
            }
            $current_user = wp_get_current_user();
            $post_type = $wpgamify_badge_issued_schema->get_post_type_name();
            $meta_key = 'wpgamify-award-email-address';
            $myquery = new WP_Query( "post_type=$post_type&meta_key=$meta_key&meta_value=$current_user->user_email&order=ASC" );
            $badgesingroup = array();
            if ( $myquery->have_posts() ){
                while($myquery->have_posts()){
                    $myquery->the_post();
                    $b = new GamifyBadgeIssued();
                    $b->load_by_key($post->ID);
                    array_push($badgesingroup, $b);
                }
            }
            $groupdata = array(
                'badges' => $badgesingroup
            );
            $badgedata = array($groupdata);
            echo wpgamify_return_embed_badges($badgedata);
        } elseif($login) {
            echo '<a href="<?php echo wp_login_url(); ?>" title="Login">Login</a> to display your current badges';
        }
    }

}

add_action('widgets_init', create_function('', 'return register_widget("WPGamifyDisplayUserBadgesWidget");'));

// Using OpenBadges User ID, retrieve array of public groups and badges from backpack displayer api
function wpgamify_get_public_backpack_contents($openbadgesuserid) {
    $backpackdata = array();

    $groupsurl = "http://beta.openbadges.org/displayer/" . $openbadgesuserid . "/groups.json";
    $groupsjson = file_get_contents($groupsurl, 0, null, null);
    $groupsdata = json_decode($groupsjson);

    foreach ($groupsdata->groups as $group) {
        $badgesurl = "http://beta.openbadges.org/displayer/" . $openbadgesuserid . "/group/" . $group->groupId . ".json";
        $badgesjson = file_get_contents($badgesurl, 0, null, null);
        $badgesdata = json_decode($badgesjson);

        $badgesingroup = array();

        foreach ($badgesdata->badges as $badge) {
            $bt = new GamifyBadgeTemplate();
            $bt->set_value("name", $badge->assertion->badge->name);
            $bt->set_value("description", $badge->assertion->badge->description);
            $bt->set_value("imageUrl", $badge->imageUrl);
            $bt->set_value("criteria", $badge->assertion->badge->criteria);
            $b = new GamifyBadgeIssued();
            $b->set_badge_template($bt);
            $b->set_value("uid", $badge->assertion->uid);
            $b->set_value("recipient", $badge->assertion->recipient);
            $b->set_value("lastValidated", $badge->lastValidated);
            $b->set_value("issuer_name", $badge->assertion->badge->issuer->name);
            $b->set_value("issuer_url", $badge->assertion->badge->issuer->origin);
            array_push($badgesingroup, $b);
        }

        $groupdata = array(
            'groupname' => $group->name,
            'groupID' => $group->groupId,
            'numberofbadges' => $group->badges,
            'badges' => $badgesingroup
        );
        array_push($backpackdata, $groupdata);
    }

    return $backpackdata;
}

/* Generate HTML returned to display badges. Used by both widgets and shortcodes */

function wpgamify_return_embed_badges($badgedata, $options = null) {

    // @todo: max-height and max-widget should be plugin configurations

    echo "<div id='wpbadgedisplay_widget'>";

    foreach ($badgedata as $group) {
        if($group['groupname']){
            echo "<h1>" . $group['groupname'] . "</h1>";
        }

        foreach ($group['badges'] as $badge) {
            $badge->display(true);
        }

        if (!$group['badges']) {
            echo "No badges have been added to this group.";
        }
    }

    if (!$badgedata) {
        echo "No public groups exist for this user.";
    }
    echo "</div>";
}

function wpgamify_convert_email_to_openbadges_id($email) {
    $emailjson = wp_remote_post('http://beta.openbadges.org/displayer/convert/email', array(
        'body' => array(
            'email' => $email
        ),
            ));

    // @todo The user id should probably be cached locally since it's persistent anyway
    if (is_wp_error($emailjson) || 200 != $emailjson['response']['code']) {
        return '';
    }

    $body = json_decode($emailjson['body']);
    return $body->userId;
}

function wpgamify_display_badge_shortcode($atts) {
    extract(shortcode_atts(array(
        'email' => '',
        'username' => '',
        'badgename' => ''
                    ), $atts));

    // Create params array
    $params = array();

    // If both email and username specified, return an error message
    if ($email && $username) {
        return "An email address and username cannot both be included as attributes of a single shortcode.";
    }

    // If a username for a WordPress install is given, retrieve its email address
    if ($username) {
        $email = get_the_author_meta('user_email', get_user_by('login', $username)->ID);
    }

    // If we still have no email value, fall back on the author of the current post
    if (!$email) {
        $email = get_the_author_meta('user_email');
    }

    /* 	With a user's email address, retrieve their Mozilla Persona ID
      Ideally, email->ID conversion will run only once since a persona ID will not change */
    if ($email) {
        $openbadgesuserid = wpgamify_convert_email_to_openbadges_id($email);
    }

    /*  Adds a hook for other plugins (like WPBadger) to add more shortcodes
      that can optionally be added to the params array */
    do_action('openbadges_shortcode');

    $badgedata = wpgamify_get_public_backpack_contents($openbadgesuserid);
    return wpgamify_return_embed_badges($badgedata);

    // @todo: github ticket #3, if email or username not specified and shortcode is called
    // on an author page, automatically retrieve the author email from the plugin
}

add_shortcode('openbadges', 'wpgamify_display_badge_shortcode');
?>
