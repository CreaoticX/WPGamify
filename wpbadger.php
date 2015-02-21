<?php

//Prevents file from being accessed directly.
if ( ! defined( 'ABSPATH' ) ) exit;

add_action('admin_init', 'wpgamify_admin_init');
add_action('admin_head', 'wpgamify_admin_head');
add_action('admin_menu', 'wpgamify_admin_menu',20);
add_action('admin_notices', 'wpgamify_admin_notices');
add_action('openbadges_shortcode', 'wpgamify_shortcode');
register_activation_hook(__FILE__,'wpgamify_activate');
register_deactivation_hook(__FILE__,'wpgamify_deactivate');

require_once( dirname(__FILE__) . '/includes/badges.php' );
require_once( dirname(__FILE__) . '/includes/badges_stats.php' );
require_once( dirname(__FILE__) . '/includes/awards.php' );

global $wpgamify_db_version;
$wpgamify_db_version = "0.7.0";

function wpgamify_activate()
{
	// If the current theme does not support post thumbnails, exit install and flash warning
	if(!current_theme_supports('post-thumbnails')) {
		echo "Unable to install plugin, because current theme does not support post-thumbnails. You can fix this by adding the following line to your current theme's functions.php file: add_theme_support( 'post-thumbnails' );";
		exit;
	}

	global $wpgamify_db_version;

	add_option("wpgamify_db_version", $wpgamify_db_version);

	// Flush rewrite rules
	global $wp_rewrite;
	$wp_rewrite->flush_rules();
}

function wpgamify_deactivate()
{
	global $wp_rewrite;
	$wp_rewrite->flush_rules();
}

function wpgamify_admin_init()
{
    wp_register_style( 'wpgamify-admin-styles', plugins_url('css/admin-styles.css', __FILE__) );
    wp_register_script( 'wpgamify-admin-post', plugins_url('js/admin-post.js', __FILE__), array( 'post' ) );
}

function wpgamify_admin_head()
{
    global $pagenow, $wpgamify_badge_template_schema, $wpgamify_badge_issued_schema;

    if (get_post_type() != $wpgamify_badge_template_schema->get_post_type_name() &&
        get_post_type() != $wpgamify_badge_issued_schema->get_post_type_name())
        return;

    wp_enqueue_style( 'wpgamify-admin-styles' );

    if ($pagenow == 'post.php' || $pagenow == 'post-new.php')
        wp_enqueue_script( 'wpgamify-admin-post' );
}


function wpgamify_admin_menu()
{
    global $wpgamify_badge_issued_schema;

    $award_type = get_post_type_object($wpgamify_badge_issued_schema->get_post_type_name());

    add_submenu_page('cp_admin_manage','Configure Badge Issuer','Badge Issuer','manage_options','wpgamify_configure_plugin','wpgamify_configure_plugin');
    add_submenu_page(
        'edit.php?post_type='.$wpgamify_badge_issued_schema->get_post_type_name(),
        'WPGamify | Bulk Issue Badges',
        'Bulk Issue Badges',
        (get_option('wpgamify_bulk_awards_allow_all') ? $award_type->cap->edit_posts : 'manage_options'),
        'wpgamify_bulk_award_badges',
        array( $wpgamify_badge_issued_schema, 'bulk_award' )
    );
}

function wpgamify_admin_notices()
{
    global $wpgamify_db_version;
    
    $db_version = filter_input(INPUT_POST, 'wpgamify_db_version');
    if ((get_option( 'wpgamify_db_version' ) != $wpgamify_db_version) && ($db_version != $wpgamify_db_version)){
        ?>
        <div class="updated">
            <p>WPGamify has been updated! Please go to the <a href="<?php echo admin_url( 'options-general.php?page=wpgamify_configure_plugin' ) ?>">configuration page</a> and update the database.</p>
        </div>
        <?php
    }
}

// Checks two mandatory fields of configured. If options are empty or don't exist, return FALSE
function wpgamify_configured()
{
	if (get_option('wpgamify_config_origin') && get_option('wpgamify_config_name')) {
		return TRUE;
	} else {
		return FALSE;
	}
}

function wpgamify_shortcode()
{
	// Query for badges with specific title
	// @todo: sort the badge version to make it first
	if ($badgename) {
		echo "bah";
		exit;
		$badge_query = new WP_Query(array('post_type' => 'badge', 'post_title' => $badge_name));

		while ( $badge_query->have_posts() ) : $badge_query->the_post();

			// Query for a user meta, check if email is user meta email
			$award_query = new WP_Query( array(
				'post_status' => 'publish',
				'post_type' => 'award',
				'meta_query' => array(
					array(
						'key' => 'wpgamify-award-email-address',
						'value' => $email,
						'compare' => '=',
						'type' => 'CHAR'
						),
					array(
						'key' => 'wpgamify-award-choose-badge',
						'value' => get_the_ID(),
						'compare' => '=',
						'type' => 'CHAR'
						)
					)
				)
			);

			// If award has been issued to specific email address, add to params
			if ($award_query) {
				array_push($options, $email);
			}
		endwhile;
	}
}

function wpgamify_configure_plugin()
{ 
    global $wpgamify_db_version;
    $save = filter_input(INPUT_POST, 'save');
    $update_db = filter_input(INPUT_POST, 'update_db');
    if ($save){
        check_admin_referer( 'wpgamify_config' );

        if (!get_option( 'wpgamify_issuer_lock' ) || is_super_admin())
        {
            $val = trim( stripslashes( filter_input(INPUT_POST, 'wpgamify_issuer_name')) );
            if (!empty( $val ))
                update_option( 'wpgamify_issuer_name', $val );

            $val = trim( stripslashes( filter_input(INPUT_POST, 'wpgamify_issuer_org') ) );
            if (!empty( $val ))
                update_option( 'wpgamify_issuer_org', $val );

            if (is_super_admin())
                update_option( 'wpgamify_issuer_lock', (bool)filter_input(INPUT_POST, 'wpgamify_issuer_lock') );
        }

        $val = trim( stripslashes( filter_input(INPUT_POST, 'wpgamify_issuer_contact')));
        if (!empty( $val ))
            update_option( 'wpgamify_issuer_contact', $val );

        update_option( 'wpgamify_bulk_awards_allow_all', (bool)filter_input(INPUT_POST, 'wpgamify_bulk_awards_allow_all'));

        $val = trim( stripslashes(filter_input(INPUT_POST, 'wpgamify_awarded_email_subject')));
        if (!empty( $val ))
            update_option( 'wpgamify_awarded_email_subject', $val );

        $val = trim( stripslashes( filter_input(INPUT_POST, 'wpgamifyawardedemailhtml')));
        if (!empty( $val ))
            update_option( 'wpgamify_awarded_email_html', $val );

        echo "<div id='message' class='updated'><p>Options successfully updated</p></div>";
    }
    elseif ($update_db)
    {
        global $wpgamify_badge_issued_schema, $wpgamify_badge_template_schema;

        $query = new WP_Query( array( 'post_type' => $wpgamify_badge_template_schema->get_post_type_name(), 'nopaging' => true ) );
        while ($query->next_post())
        {
            # Migrate the post_content to the description metadata
            $desc = $wpgamify_badge_template_schema->get_post_description( $query->post->ID, $query->post );
            update_post_meta( $query->post->ID, 'wpgamify-badge-description', $desc );

            # Validate the post
            $wpgamify_badge_template_schema->save_post_validate( $query->post->ID, $query->post );
        }

        $query = new WP_Query( array( 'post_type' => $wpgamify_badge_issued_schema->get_post_type_name(), 'nopaging' => true ) );
        while ($query->next_post())
        {
            $wpgamify_badge_issued_schema->save_post_validate( $query->post->ID, $query->post );
            
            # We just have to assume here that if the award is published then
            # an email was sent
            $tmp = get_post_meta( $query->post->ID, 'wpgamify-award-email-sent' );
            if (empty( $tmp ) && $query->post->post_status == 'publish') 
                update_post_meta( $query->post->ID, 'wpgamify-award-email-sent', get_post_meta( $query->post->ID, 'wpgamify-award-email-address', true ) );
        }

        $tmp = get_option( 'wpgamify_awarded_email_subject' );
        if (empty( $tmp ))
            update_option(
                'wpgamify_awarded_email_subject',
                __( 'You have been awarded the "{BADGE_TITLE}" badge', 'wpgamify' )
            );

        $tmp = get_option( 'wpgamify_awarded_email_html' );
        if (empty( $tmp ))
        {
            $tmp = get_option( 'wpgamify_config_award_email_text' );
            if (empty( $tmp ))
                $tmp = __( <<<EOHTML
Congratulations! {ISSUER_NAME} at {ISSUER_ORG} has awarded you the "<a href="{BADGE_URL}">{BADGE_TITLE}</a>" badge. You can choose to accept or reject the badge into your <a href="http://openbadges.org/">OpenBadges Backpack</a> by following this link:

<a href="{AWARD_URL}">{AWARD_URL}</a>

If you have any issues with this award, please contact <a href="mailto:{ISSUER_CONTACT}">{ISSUER_CONTACT}</a>.
EOHTML
            , 'wpgamify' );

            update_option( 'wpgamify_awarded_email_html', $tmp );
        }

        update_option( 'wpgamify_db_version', $wpgamify_db_version );

        echo "<div class='updated'><p>Database successfully updated</p></div>";
    }

    $issuer_disabled = (get_option('wpgamify_issuer_lock') && !is_super_admin()) ? 'disabled="disabled"' : '';

?>
<div class="wrap">
<h2>WPGamify Configuration</h2>

<form method="POST" action="" name="wpgamify_config">
    <?php wp_nonce_field( 'wpgamify_config' ); ?>

    <table class="form-table">

        <tr valign="top">
            <th scope="row"><label for="wpgamify_issuer_name">Issuing Agent Name</label></th>
            <td>
                <input type="text"
                    id="wpgamify_issuer_name"
                    name="wpgamify_issuer_name"
                    class="regular-text"
                    value="<?php esc_attr_e( get_option('wpgamify_issuer_name') ); ?>"
                    <?php echo $issuer_disabled ?> />
            </td>
        </tr>

        <tr valign="top">
            <th scope="row"><label for="wpgamify_issuer_org">Issuing Organization</label></th>
            <td>
                <input type="text"
                    id="wpgamify_issuer_org"
                    name="wpgamify_issuer_org"
                    class="regular-text"
                    value="<?php esc_attr_e( get_option('wpgamify_issuer_org') ); ?>"
                    <?php echo $issuer_disabled ?> />
            </td>
        </tr>

        <?php
        if (is_super_admin())
        {
            ?>

            <tr valign="top">
                <th scope="row"></th>
                <td><label>
                    <input type="checkbox"
                        id="wpgamify_issuer_lock"
                        name="wpgamify_issuer_lock"
                        value="1" <?php echo get_option('wpgamify_issuer_lock') ? 'checked="checked"' : '' ?> />
                    Disable editting of issuer information for non-admins.
                </label></td>
            </tr>
            
            <?php
        }
        ?>

        <tr valign="top">
            <th scope="row"><label for="wpgamify_issuer_contact">Contact Email Address</label></th>
            <td>
                <input type="text"
                    id="wpgamify_issuer_contact"
                    name="wpgamify_issuer_contact"
                    class="regular-text"
                    value="<?php esc_attr_e( get_option('wpgamify_issuer_contact') ); ?>" />
            </td>
        </tr>

        <tr valign="top">
            <th scope="row"></th>
            <td><label>
                <input type="checkbox"
                    name="wpgamify_bulk_awards_allow_all"
                    id="wpgamify_bulk_awards_allow_all"
                    value="1"
                    <?php echo get_option('wpgamify_bulk_awards_allow_all') ? 'checked="checked"' : '' ?> />
                Allow all users to bulk award badges.
            </label></td>
        </tr>

    </table>

    <h3 class="title">Awarded Email Template</h3>
    
    <p>This is the email send when a badge is awarded to a user.  Valid template tags are:
    <b>{ISSUER_NAME}</b>; <b>{ISSUER_ORG}</b>; <b>{ISSUER_CONTACT}</b>; <b>{BADGE_TITLE}</b>;
    {BADGE_URL}; {BADGE_IMAGE_URL}; {BADGE_DESCRIPTION}; <b>{AWARD_TITLE}</b>; <b>{FIRST_NAME}</b>; 
    <b>{LAST_NAME}</b>; {AWARD_URL}, and {EVIDENCE}.
    Only <b>bold</b> tags are avilable for the subject.</p>

    <label for="wpgamify-awarded-email-subject"><em>Subject</em></label>
    <input type="text"
        name="wpgamify_awarded_email_subject"
        id="wpgamify-awarded-email-subject"
        class="widefat"
        value="<?php esc_attr_e( get_option( 'wpgamify_awarded_email_subject' ) ) ?>" />

    <br /><br />
    <label for="wpgamifyawardedemailhtml"><em>HTML Body</em></label>
    <?php wp_editor( get_option( 'wpgamify_awarded_email_html' ), 'wpgamifyawardedemailhtml' ) ?>

    <p class="submit">
        <input type="submit" class="button-primary" name="save" value="<?php _e('Save Changes') ?>" />
    </p>

</form>

<form method="POST" action="" name="wpgamify_db_update">
    <input type="hidden" name="wpgamify_db_version" value="<?php esc_attr_e( $wpgamify_db_version ) ?>" />
    <input type="submit" name="update_db" value="<?php _e('Update Database') ?>" />
</form>
</div>

<?php
}

function wpgamify_disable_quickedit( $actions, $post ) {
    if( $post->post_type == 'badgeissued' || 'badgetemplate' ) {
        unset( $actions['inline hide-if-no-js'] );
    }
    return $actions;
}
add_filter( 'post_row_actions', 'wpgamify_disable_quickedit', 10, 2 );

function wpgamify_template( $template, $values )
{
    $defaults = array(
        '{'                 => '{',
        'ISSUER_NAME'       => get_option( 'wpgamify_issuer_name' ),
        'ISSUER_ORG'        => get_option( 'wpgamify_issuer_org' ),
        'ISSUER_CONTACT'    => get_option( 'wpgamify_issuer_contact' ),
    );

    if (empty( $defaults[ 'ISSUER_CONTACT' ] ))
        $defaults[ 'ISSUER_CONTACT' ] = get_bloginfo( 'admin_email' );

    $values = array_merge( $defaults, $values );

    /*
     * Possible states:
     *
     * - text
     * - tag-open
     * - tag-close
     * - tag-sub
     * - end
     */
    $state = 'text';
    $tag = null;
    $pos = 0;
    $result = '';

    while ($state != 'end')
    {
        #printf( "DEBUG: state = %s; tag = %s; pos = %d\n", $state, $tag, $pos );

        switch ($state)
        {
        case 'text':
            if ($pos >= strlen( $template ))
            {
                $state = 'end';
                break;
            }

            $found = strpos( $template, '{', $pos );
            if ($found === false)
            {
                # No opening tags found. Just append the substring to the
                # result and exit
                $result .= substr( $template, $pos );
                $state = 'end';
            }
            else
            {
                # Found a tag! Append the substring before the tag to
                # the result, advance the $pos, and go to tag-open, unless
                $result .= substr( $template, $pos, ($found - $pos) );
                $pos = $found + 1;

                $state = 'tag-open';
            }
            break;

        case 'tag-open':
            $found = strpos( $template, '}', $pos );

            if ($found === false)
            {
                # We didn't find a valid close tag after our start tag.
                # Just output the start tag and continue on as text.
                $result .= '{';
                $state = 'text';
            }
            else
            {
                # Grab the tag and go to tag-close
                $tag = substr( $template, $pos, ($found - $pos) );
                $state = 'tag-close';
            }
            break;

        case 'tag-close':
            if (!preg_match( '/^(([a-z_]+)|{)$/i', $tag ))
            {
                # Not a valid tag. Output the start tag and continue on.
                $result .= '{';
                $state = 'text';
            }
            else
            {
                # Advance our position and do the tag sub
                $pos += strlen( $tag ) + 1;
                $state = 'tag-sub';
            }
            break;

        case 'tag-sub':
            if (isset( $values[ $tag ] ))
            {
                $result .= $values[ $tag ];
                $state = 'text';
            }
            else
            {
                # If we don't have a substitution to make then go ahead
                # and output the raw tag
                $result .= '{' . $tag . '}';
                $state = 'text';
            }
            break;
        }
    }

    return $result;
}
