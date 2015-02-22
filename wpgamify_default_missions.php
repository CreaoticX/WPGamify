<?php

class WPGamify_Default_Missions{
    
    public $default_missions = array();
    
    function __construct() {
        add_action('init', array($this, 'init'));
        $this->default_missions['points_to_badges']='Points to Badges';
        $this->default_missions['badges_to_points']='Badges to Points';
    }

    function init() {
        global $wpgamify_mission_schema;
        foreach($this->default_missions as $value => $label){
            $wpgamify_mission_schema->add_mission($value, $label);
            add_filter("wpgamify_mission_".$value."_options",array( $this, 'settings_output' ),10,2 );
        }
        add_action('wpgamify_save_mission', array($this, 'save_post'), 10, 1);
    }
    
    function settings_output($start,$post_id){
        $filter = current_filter();
        $filter = str_replace("wpgamify_mission_", "", $filter);
        $filter = str_replace("_options", "", $filter);
        $start .= '<input type="hidden" name="mission_form" value="'.$filter.'">';
        switch ($filter){
            case 'points_to_badges':
                return $this->points_to_badges_ouput($start,$post_id);
                break;
            case 'badges_to_points':
                return $this->badges_to_points_ouput($start,$post_id);
                break;
        }
    }
    
    function save_post($post_id){
        $mission = filter_input(INPUT_POST, "mission_form");
        switch ($mission){
            case 'points_to_badges':
                $this->points_to_badges_save($post_id);
                break;
            case 'badges_to_points':
                $this->badges_to_points_save($post_id);
                break;
        }
    }
    
    private function points_to_badges_ouput($start,$post_id){
        $start .= '<p>'.__("Earning ", "wpgamify").get_option('cp_prefix').
                '<input type="text" name="wpgamify_ptb_points" id="wpgamify_ptb_points"'.
                ' value="'.get_post_meta($post_id,'wpgamify_ptb_points',true).'"></input>'.
                get_option('cp_suffix').__(" earns the ","wpgamify").$this->badge_output(get_post_meta($post_id,'wpgamify_ptb_badge',true),'ptb').
                __(" Badge ", "wpgamify").'</p>';
        $start .= '<h3>Evidence</h3>';
        $start .= '<p>Describe the evidence that this badge was earned.</p>';
        $start .= '<p><textarea type="text" class="widefat" rows="3" name="wpgamify_ptb_evidence" id="wpgamify_ptb_evidence" />';
        $start .= esc_attr(get_post_meta($post_id, 'wpgamify_ptb_evidence', true)).'</textarea></p>';
        return $start;
    }
    
    private function points_to_badges_save($post_id){
        $this->update_meta($post_id, 'wpgamify_ptb_points', filter_input(INPUT_POST, 'wpgamify_ptb_points'));
        $this->update_meta($post_id, 'wpgamify_ptb_badge', filter_input(INPUT_POST, 'wpgamify_ptb_badge'));
        $this->update_meta($post_id, 'wpgamify_ptb_evidence', filter_input(INPUT_POST, 'wpgamify_ptb_evidence'));
    }
    
    private function badges_to_points_ouput($start,$post_id){
        $start .= '<p>'.__("Earning the", "wpgamify").$this->badge_output(get_post_meta($post_id,'wpgamify_btp_badge',true),'btp').
                __(" Badge ", "wpgamify").__(" earns ","wpgamify").get_option('cp_prefix').
                '<input type="text" name="wpgamify_btp_points" id="wpgamify_btp_points"'.
                ' value="'.get_post_meta($post_id,'wpgamify_btp_points',true).'"></input>'.
                get_option('cp_suffix').'</p>';
        return $start;
    }
    
    private function badges_to_points_save($post_id){
        $this->update_meta($post_id, 'wpgamify_btp_points', filter_input(INPUT_POST, 'wpgamify_btp_points'));
        $this->update_meta($post_id, 'wpgamify_btp_badge', filter_input(INPUT_POST, 'wpgamify_btp_badge'));
    }
    
    private function badge_output($award_badge_id,$split){
        global $wpgamify_badge_template_schema;
        $badge_out = '';
        if (current_user_can('manage_options')) {
            $badge_out .= '<select name="wpgamify_'.$split.'_badge" id="wpgamify_'.$split.'_badge">';
            $bt = new GamifyBadgeTemplate();
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

                $badge_out .= "<option value='{$badge_id}'{$selected}{$disabled}>{$badge_title_version}</option>";
            }

            $badge_out .= '</select>';
        }
        return $badge_out;
    }

    function update_meta($post_id,$meta_key,$new_value){
        $old_value = get_post_meta($post_id,$meta_key,true);
        if ($new_value && empty($old_value))
            add_post_meta($post_id, $meta_key, $new_value, true);
        elseif (current_user_can('manage_options')) {
            if ($new_value && $new_value != $old_value){
                delete_post_meta($post_id, $meta_key, $old_value);
                update_post_meta($post_id, $meta_key, $new_value);
            }elseif (empty($new_value)){
                delete_post_meta($post_id, $meta_key, $old_value);
            }
        }
    }
}
$GLOBALS['wpgamify_default_missions'] = new WPGamify_Default_Missions();