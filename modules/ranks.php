<?php
/** Ranks Module */
global $wpgamify_points_core;
$wpgamify_points_core->wpg_module_register(__('Ranks', 'cp'), 'ranks', '1.0', 'CubePoints', 'http://cubepoints.com', 'http://cubepoints.com', __('Create and display user ranks based on the number of points they have.', 'cp'), 1);

function cp_module_ranks_data_install() {
    add_option('cp_module_ranks_data', array(0 => __('Newbie', 'cp')));
}

add_action('cp_module_ranks_activate', 'cp_module_ranks_data_install');

if ($wpgamify_points_core->wpg_module_activated('ranks')) {

    function cp_module_ranks_data_add_admin_page() {
        add_submenu_page('cp_admin_manage', 'CubePoints - ' . __('Ranks', 'cp'), __('Ranks', 'cp'), 'manage_options', 'cp_modules_ranks_admin', 'cp_modules_ranks_admin');
    }

    add_action('cp_admin_pages', 'cp_module_ranks_data_add_admin_page');

    function cp_modules_ranks_admin() {

        // handles form submissions
        $cp_module_ranks_data_form_submit = filter_input(INPUT_POST, 'cp_module_ranks_data_form_submit');
        if ($cp_module_ranks_data_form_submit == 'Y') {
            $cp_module_ranks_data_rank = trim(filter_input(INPUT_POST, 'cp_module_ranks_data_rank'));
            $cp_module_ranks_data_points = (int) trim(filter_input(INPUT_POST, 'cp_module_ranks_data_points'));
            $ranks = get_option('cp_module_ranks_data');
            if ($cp_module_ranks_data_rank == '' || $cp_module_ranks_data_points == '') {
                echo '<div class="error"><p><strong>' . __('Rank name or points cannot be empty!', 'cp') . '</strong></p></div>';
            } else if (!is_numeric($cp_module_ranks_data_points) || $cp_module_ranks_data_points < 0 || $cp_module_ranks_data_points != (float) trim(filter_input(INPUT_POST, 'cp_module_ranks_data_points'))) {
                echo '<div class="error"><p><strong>' . __('Please enter only positive integers for the points!', 'cp') . '</strong></p></div>';
            } else {
                if($cp_module_ranks_data_points == 0){
                    $ranks[$cp_module_ranks_data_points] = $cp_module_ranks_data_rank;
                }else{
                    $ranks[$cp_module_ranks_data_points] = $cp_module_ranks_data_rank;
                }
                if ($ranks[$cp_module_ranks_data_points] != '') {
                    echo '<div class="updated"><p><strong>' . __('Rank Updated', 'cp') . '</strong></p></div>';
                } else {
                    echo '<div class="updated"><p><strong>' . __('Rank Added', 'cp') . '</strong></p></div>';
                }
                update_option('cp_module_ranks_data', $ranks);
            }
        }
        
        $cp_rank_remove = trim(filter_input(INPUT_POST, 'cp_rank_remove'));
        if ($cp_rank_remove != '') {
            if ( $cp_rank_remove == 0) {
                echo '<div class="error"><p><strong>' . __('A rank name is needed for users with 0 points!<br /><br />To change the name of this rank, add another rank to replace this.', 'cp') . '</strong></p></div>';
            } else {
                $ranks = get_option('cp_module_ranks_data');
                unset($ranks[$cp_rank_remove]);
                update_option('cp_module_ranks_data', $ranks);
                echo '<div class="updated"><p><strong>' . __('Rank removed', 'cp') . '</strong></p></div>';
            }
        }
        ?>

        <div class="wrap">
            <h2>WPGamify - <?php _e('Ranks', 'cp'); ?></h2>
        <?php _e('Setup ranks for your users.', 'cp'); ?> <?php _e('To rename ranks, overwrite it with a new rank.', 'cp'); ?><br /><br />

            <table id="cp_modules_table" class="widefat datatables">
                <thead><tr><th scope="col"><?php _e('Rank', 'cp'); ?></th><th scope="col" width="150" style="text-align:center;"><?php _e('Points', 'cp'); ?></th><th scope="col" width="150"><?php _e('Action', 'cp'); ?></th></tr></thead>
                <tfoot><tr><th scope="col"><?php _e('Rank', 'cp'); ?></th><th scope="col" style="text-align:center;"><?php _e('Points', 'cp'); ?></th><th scope="col"><?php _e('Action', 'cp'); ?></th></tr></tfoot>
            <?php
            $ranks = (array) get_option('cp_module_ranks_data');
            if ($ranks[0] == '') {
                $ranks[0] = __('Newbie', 'cp');
                update_option('cp_module_ranks_data', $ranks);
            }
            ksort($ranks);
            foreach ($ranks as $points => $rank) {
                ?>
                    <tr>
                        <td><?php echo $rank; ?></td>
                        <td style="text-align:center;"><?php echo $points; ?></td>
                        <td>
                            <form method="post" name="cp_ranks_action_remove_<?php echo $points; ?>" style="display:inline;">
                                <input type="hidden" name="cp_rank_remove" value="<?php echo $points; ?>" />
                                <a href="javascript:void(0);" onclick="document.cp_ranks_action_remove_<?php echo $points; ?>.submit();"><?php _e('Remove'); ?></a>
                            </form>
                        </td>
                    </tr>
            <?php
        }
        ?>
            </table>

            <form name="cp_module_ranks_data_form" method="post">
                <input type="hidden" name="cp_module_ranks_data_form_submit" value="Y" />

                <h3><?php _e('Add Rank', 'cp'); ?></h3>
                <table class="form-table">
                    <tr valign="top">
                        <th scope="row"><label for="cp_module_ranks_data_rank"><?php _e('Rank Name', 'cp'); ?>:</label></th>
                        <td valign="middle"><input type="text" id="cp_module_ranks_data_rank" name="cp_module_ranks_data_rank" value="" size="40" /></td>
                    </tr>
                    <tr valign="top">
                        <th scope="row"><label for="cp_module_ranks_data_points"><?php _e('Points to reach this rank', 'cp'); ?>:</label></th>
                        <td valign="middle"><input type="text" id="cp_module_ranks_data_points" name="cp_module_ranks_data_points" value="" size="40" /></td>
                    </tr>
                </table>

                <p class="submit">
                    <input type="submit" name="Submit" value="<?php _e('Add Rank', 'cp'); ?>" />
                </p>
            </form>
        </div>
        <?php
    }

    function cp_module_ranks_getRank($uid) {
        global $wpgamify_points_core;
        return cp_module_ranks_pointsToRank($wpgamify_points_core->wpg_getPoints($uid));
    }

    function cp_module_ranks_pointsToRank($points) {
        $ranks_o = get_option('cp_module_ranks_data');
        ksort($ranks_o);
        $ranks = array_reverse($ranks_o, 1);
        foreach($ranks as $p=>$r){
                if($points>=$p){
                        return $r;
                }
        }
    }

    function cp_module_ranks_widget() {
        if (is_user_logged_in()) {
        global $wpgamify_points_core;
            ?>
            <li><?php _e('Rank', 'cp'); ?>: <?php echo cp_module_ranks_getRank($wpgamify_points_core->wpg_currentUser()); ?></li>
            <?php
        }
    }

    add_action('cp_pointsWidget', 'cp_module_ranks_widget');
}
?>