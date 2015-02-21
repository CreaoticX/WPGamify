<?php

/**
 * CubePoints admin page: manage
 */
function cp_admin_manage() {
    ?>

    <div class="wrap">
        <h2>WPGamify - <?php _e('Users', 'cp'); ?></h2>
    <?php _e('Manage the points of your users.', 'cp'); ?><br /><br />
        <div class="updated" id="cp_manage_updated" style="display: none;"></div>
        <?php
        global $wpdb;
        $results = $wpdb->get_results("SELECT * FROM `" . $wpdb->users . "` ORDER BY user_login ASC");
        ?>

        <table id="cp_manage_table" class="widefat datatables">
            <thead><tr><th scope="col" width="35"></th><th scope="col"><?php _e('User', 'cp'); ?></th><th scope="col" width="120"><?php _e('Points', 'cp'); ?></th><th scope="col" width="180"><?php _e('Add Points', 'cp'); ?></th></tr></thead>
            <tfoot><tr><th scope="col"></th><th scope="col"><?php _e('User', 'cp'); ?></th><th scope="col"><?php _e('Points', 'cp'); ?></th><th scope="col"><?php _e('Add Points', 'cp'); ?></th></tr></tfoot>

    <?php
    foreach ($results as $result) {
        $user = get_userdata($result->ID);
        $username = $user->user_login;
        $user_nicename = $user->display_name;
        $user_id = $user->ID;
        $gravatar = get_avatar($result->ID, $size = '32');
        ?>
                <tr>
                    <td>
        <?php echo $gravatar; ?>
                    </td>
                    <td title="<?php echo $user_nicename ?>">
                        <strong>
                            <a href="<?php echo admin_url() . 'admin.php?page=cp_admin_logs&wpguser=' . $user_id; ?>">
        <?php echo $username; ?></a>
                        </strong><br /><i><?php echo $user->user_email; ?></i>
                    </td>
                    <td class="cp_manage_form_points">
                        <span id="cp_manage_form_points_<?php echo $result->ID; ?>"><?php cp_displayPoints($result->ID); ?></span>
                    </td>
                    <td class="cp_manage_form_update">
                        <a href="<?php echo admin_url() . 'admin.php?page=cp_admin_add_points#' . $user_id; ?>">
                            <span class="dashicons dashicons-awards" title="Add Points"></span>
                        </a>
                    </td>
                </tr>
        <?php
    }
    ?>
        </table>

    </div>

    <script type="text/javascript">
        jQuery(document).ready(function() {

//            jQuery(".cp_manage_form_update form").submit(function() {
//                user_id = jQuery(this).children('input[name=cp_manage_form_id]').val();
//                points = jQuery(this).children('input[name=cp_manage_form_points]').val();
//                submit = jQuery(this).children('input[type=submit]');
//                loadImg = jQuery(this).children('img');
//
//                jQuery(".cp_manage_form_update form").children('input').attr('disabled', true);
//                submit.hide();
//                loadImg.css('display', 'inline-block');
//                jQuery(this).children('input[name=cp_manage_form_points]').attr('readonly', true);
//                jQuery('#cp_manage_form_points_' + user_id).hide(100);
//
//                jQuery.post(
//                        ajaxurl,
//                        {
//                            action: 'cp_manage_form_submit',
//                            user_id: user_id,
//                            points: points
//                        },
//                function(data, status) {
//                    if (status != 'success') {
//                        message = '<?php _e('Connection problem. Please check that you are connected to the internet.', 'cp'); ?>';
//                    } else if (data.error != 'ok') {
//                        message = data.error;
//                    } else {
//                        jQuery("#cp_manage_form_points_" + user_id).html(data.points_formatted);
//                        jQuery("#cp_manage_form_points_" + user_id).show(100);
//                        jQuery('#cp_manage_form_' + data.user_id).children('input[name=cp_manage_form_points]').val(data.points);
//                        jQuery('#cp_manage_form_' + data.user_id).children('input[name=cp_manage_form_points]').removeAttr('readonly');
//                        message = '<?php _e("Points updated for", 'cp'); ?>' + ' "' + data.username + '"';
//                    }
//                    jQuery("#cp_manage_updated").html('<p><strong>' + message + '</strong></p>');
//                    jQuery("#cp_manage_updated").show(100);
//                    loadImg.hide();
//                    submit.show();
//                    jQuery(".cp_manage_form_update form").children('input').removeAttr('disabled');
//                },
//                        "json"
//                        );
//                return false;
//            });

            jQuery('#cp_manage_table').dataTable({
                "bStateSave": true,
                "bSort": false,
                "aoColumns": [{"bSearchable": false}, {}, {}, {"bSearchable": false}]
            });

        });

    </script>

    <?php do_action('cp_admin_manage'); ?>

    <?php
}
?>