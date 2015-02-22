<?php
//Need 
//Prevents file from being accessed directly.
if (!defined('ABSPATH'))
    exit;

class WPGamify_Points_Core {

    /** Core function loaded */
    function gp_ready() {
        return true;
    }

    /** Get current logged in user */
    function wpg_currentUser() {
        require_once(ABSPATH . WPINC . '/pluggable.php');
        global $current_user;
        get_currentuserinfo();
        return $current_user->ID;
    }

    /** Get number of points */
    function wpg_getPoints($uid,$custom='default') {
        if($custom == 'default'){
            $points = (int)get_user_meta($uid, 'wpg_points', 1);
        }else{
            $points = (int)get_user_meta($uid, 'wpg_points_'.$custom, 1);
        }
        if ($points == '') {
            return 0;
        } else {
            return $points;
        }
    }

    /** Update points */
    function wpg_updatePoints($uid, $points,$custom='default') {
        // no negative points 
        if ($points < 0) {
            $points = 0;
        }
        if($custom == 'default'){
            update_user_meta($uid, 'wpg_points', $points);
        }else{
            update_user_meta($uid, 'wpg_points_'.$custom, $points);
        }
    }

    /** Alter points */
    function wpg_alterPoints($uid, $points,$custom='default') {
        $this->wpg_updatePoints($uid, $this->wpg_getPoints($uid) + $points,$custom);
    }

    /** Formats points with prefix and suffix */
    function wpg_formatPoints($points,$custom='default') {
        if ($points == 0) {
            $points = '0';
        }
        $points_output = $points;
        if($custom == 'default'){
            $points_output = get_option('cp_prefix') . $points . get_option('cp_suffix');
        }else{
            $points_output = apply_filters('wpg_formatPoints',$points,$custom);
        }
        return $points_output;
    }

    /** Display points */
    function wpg_displayPoints($uid = 0, $return = 0, $format = 1,$custom='default') {
        if ($uid == 0) {
            if (!is_user_logged_in()) {
                return false;
            }
            $uid = $this->wpg_currentUser();
        }

        if ($format == 1) {
            $fpoints = $this->wpg_formatPoints($this->wpg_getPoints($uid,$custom),$custom);
        } else {
            $fpoints = $this->wpg_getPoints($uid);
        }

        if (!$return) {
            echo $fpoints;
        } else {
            return $fpoints;
        }
    }

    /** Get points of all users into an array */
    function wpg_getAllPoints($amt = 0, $filter_users = array(), $start = 0,$custom='default') {
        global $wpdb;
        if ($amt > 0) {
            $limit = ' LIMIT ' . $start . ',' . $amt;
        }
        $extraquery = '';
        if (count($filter_users) > 0) {
            $extraquery = ' WHERE ' . $wpdb->base_prefix . 'users.user_login != \'';
            $extraquery .= implode("' AND " . $wpdb->base_prefix . "users.user_login != '", $filter_users);
            $extraquery .= '\' ';
        }
        if($custom == 'default'){
            $point_type = 'wpg_points';
        }else{
            $point_type = 'wpg_points_'.$custom;
        }
        $array = $wpdb->get_results('SELECT ' . $wpdb->base_prefix . 'users.id, ' . $wpdb->base_prefix . 'users.user_login, ' . $wpdb->base_prefix . 'users.display_name, ' . $wpdb->base_prefix . 'usermeta.meta_value 
                    FROM `' . $wpdb->base_prefix . 'users` 
                    LEFT JOIN `' . $wpdb->base_prefix . 'usermeta` ON ' . $wpdb->base_prefix . 'users.id = ' . $wpdb->base_prefix . 'usermeta.user_id 
                    AND ' . $wpdb->base_prefix . 'usermeta.meta_key=\'' . $point_type . '\'' . $extraquery . ' 
                    ORDER BY ' . $wpdb->base_prefix . 'usermeta.meta_value+0 DESC'
                . $limit . ';'
                , ARRAY_A);
        foreach ($array as $x => $y) {
            $a[$x] = array("id" => $y['id'], "user" => $y['user_login'], "display_name" => $y['display_name'], "points" => ($y['meta_value'] == 0) ? 0 : $y['meta_value'], "points_formatted" => $this->wpg_formatPoints($y['meta_value']));
        }
        return $a;
    }

    /** Adds transaction to logs database */
    function wpg_points_log($type, $uid, $points, $data,$custom='default') {
        $userinfo = get_userdata($uid);
        if ($userinfo->user_login == '') {
            return false;
        }
        if ($points == 0 && $type != 'reset') {
            return false;
        }
        global $wpdb;
        $wpdb->query("INSERT INTO `" . CP_DB . "` (`id`, `uid`, `type`, 'custom', `data`, `points`, `timestamp`) 
                                      VALUES (NULL, '" . $uid . "', '" . $type . "', '" . $custom . "', '" . $data . "', '" . $points . "', " . time() . ");");
        do_action('wpg_points_log', $type, $uid, $points, $data, $custom);
        return true;
    }

    /** Alter points and add to logs */
    function wpg_add_points($type, $uid, $points, $data,$custom='default') {
        $points = apply_filters('wpg_add_points', $points, $type, $uid, $data,$custom);
        $this->wpg_alterPoints($uid, $points,$custom);
        $this->wpg_points_log($type, $uid, $points, $data,$custom);
    }

    /** Set points and add to logs */
    function wpg_points_set($type, $uid, $points, $data,$custom='default') {
        $points = apply_filters('wpg_points_set', $points, $type, $uid, $data,$custom);
        $difference = $points - $this->wpg_getPoints($uid);
        $this->wpg_updatePoints($uid, $points,$custom);
        $this->wpg_log($type, $uid, $difference, $data,$custom);
    }

    /** Get total number of posts */
    function wpg_getPostCount($id) {
        global $wpdb;
        return (int) $wpdb->get_var('SELECT count(id) FROM `' . $wpdb->base_prefix . 'posts` where `post_type`=\'post\' and `post_status`=\'publish\' and `post_author`=' . $id);
    }

    /** Get total number of comments */
    function wpg_getCommentCount($id) {
        global $wpdb;
        return (int) $wpdb->get_var('SELECT count(comment_ID) FROM `' . $wpdb->base_prefix . 'comments` where `user_id`=' . $id);
    }

    /** Function to truncate a long string */
    function wpg_truncate($string, $length, $stopanywhere = false) {
        $string = str_replace('"', '&quot;', strip_tags($string));

        //truncates a string to a certain char length, stopping on a word if not specified otherwise.
        if (strlen($string) > $length) {
            //limit hit!
            $string = substr($string, 0, ($length - 3));
            if ($stopanywhere) {
                //stop anywhere
                $string .= '...';
            } else {
                //stop on a word.
                $string = substr($string, 0, strrpos($string, ' ')) . '...';
            }
        }
        return $string;
    }

    /** Function to register modules */
    function wpg_module_register($module, $id, $version, $author, $author_url, $plugin_url, $description, $can_deactivate) {
        if ($module == '' || $id == '' || $version == '' || $description == '') {
            return false;
        }
        global $cp_module;
        $cp_module[] = array("module" => $module, "id" => $id, "version" => $version, "author" => $author, "author_url" => $author_url, "plugin_url" => $plugin_url, "description" => $description, "can_deactivate" => $can_deactivate);
    }

    /** Function to check module activation status */
    function wpg_module_activated($id) {
        if (get_option('cp_module_activation_' . $id) != false) {
            return true;
        } else {
            return false;
        }
    }

    /** Function to activate or deactivate module */
    function wpg_module_activation_set($id, $status) {
        update_option('cp_module_activation_' . $id, $status);
    }

    /** Function to include all modules in the modules folder */
    function wpg_modules_include() {
        foreach (glob(ABSPATH . PLUGINDIR . '/' . dirname(plugin_basename(__FILE__)) . "/modules/*.php") as $filename) {
            require_once($filename);
        }
        foreach (glob(ABSPATH . PLUGINDIR . '/' . dirname(plugin_basename(__FILE__)) . "/modules/*/*.php") as $filename) {
            require_once($filename);
        }
    }

    /** Function to cache module versions and run activation hook on module update */
    function wpg_modules_updateCheck() {
        global $cp_module;
        $module_ver_cache = unserialize(get_option('cp_moduleVersions'));
        $module_ver = array();
        foreach ($cp_module as $mod) {
            $module_ver[$mod['id']] = $mod['version'];
            // check for change in version and run module activation hook
            if ($this->wpg_module_activated($mod['id'])) {
                if ($module_ver_cache[$mod['id']] != $mod['version']) {
                    if (!did_action('cp_module_' . $mod['id'] . '_activate')) {
                        do_action('cp_module_' . $mod['id'] . '_activate');
                    }
                }
            }
        }
        update_option('cp_moduleVersions', serialize($module_ver));
    }
    
}

$GLOBALS['wpgamify_points_core'] = new WPGamify_Points_Core();
