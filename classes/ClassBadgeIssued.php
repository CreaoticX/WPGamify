<?php

require_once 'ClassBase.php';

class GamifyBadgeIssued extends GamifyBase{
    
    private $badge_template;
    private $post_type = 'badgeissued';
    
    function __construct() {
        parent::__construct();
        $this->badge_template = new GamifyBadgeTemplate();
        $this->table = "";
        $this->key_id = "";
        $this->v = array($this->key_id=>NULL,'name'=>NULL,"slug"=>NULL,'uid'=>NULL,'recipient'=>NULL,'email'=>NULL,'lastValidated'=>NULL,
            'issuer_name'=>NULL,'issuer_url'=>NULL,'expires'=>NULL,"template"=>NULL,'award_status'=>NULL,'salt'=>NULL,
            'post_status'=>NULL, 'post_author'=>NULL, "evidence"=>NULL);
        $this->loadable_keys = array();
    }
        
    function display($echo = true){
        $display_contents = '<div class="open-badge-thumb">';
        $display_contents .= '<span class="ob-info">';
        $display_contents .= '<p>'.$this->badge_template->get_value("description").'</p>';
        $display_contents .= '<div class="ob-badge-logo-wrapper">';
        $display_contents .= '<div class="ob-badge-logo"></div>';
        $display_contents .= '</div>';
        $display_contents .= '</span>';
        $display_contents .= "<img class='ob-badge-img' src='" . $this->badge_template->get_value('imageUrl') . "' border='0'>";
        $display_contents .= '</div>';
        $display_contents = apply_filters("gamify_display_badge",$display_contents,  $this->toArray());
        
        if($echo){
            echo $display_contents;
        }
        return $display_contents;
    }
    
    function get_post_type(){
        return $this->post_type;
    }
    
    function set_badge_template($temp){
        if (is_a($temp, 'GamifyBadgeTemplate')) {
            $this->badge_template = $temp;
        }else{
            $this->badge_template = new GamifyBadgeTemplate();
            $this->badge_template->load_by_key($temp);
        }
        if($this->badge_template->get_key_value() != NULL){
            $this->set_value("template", $this->badge_template->get_key_value());
        }
    }
    
    function get_template_value($key){
        return $this->badge_template->get_value($key);
    }
            
    function load_by_value($id,$value){
        
    }
    
    function load_by_key($id){
        $post = get_post( $id);
        if ($post != NULL && $post->post_type  == $this->get_post_type()){
            $post_id = $post->ID;
            $template = get_post_meta($post_id, 'wpgamify-award-choose-badge', true);
            $this->set_value($this->key_id, $post_id);
            $this->set_value('post_status', $post->post_status);
            $this->set_value('post_author', $post->post_author);
            $this->set_value('evidence', $post->content);
            $this->set_value('slug', $post->content);
            $this->set_value("template", $template);
            $this->set_value("email", get_post_meta($post_id, 'wpgamify-award-email-address', true));
            $this->set_value("expires", get_post_meta($post_id, 'wpgamify-award-expires', true));
            $this->set_value("award_status", get_post_meta($post_id, 'wpgamify-award-status', true));
            $this->set_value("salt", get_post_meta($post_id, 'wpgamify-award-salt', true));
            $this->set_value("name", $post->post_title );
            $this->set_value("issuer_url", get_permalink($post_id));
            $this->badge_template = new GamifyBadgeTemplate();
            $this->badge_template->load_by_key($template);
        }
    }
        
    function exists(){
        
    }
    
    function update_db() {
        $post = array(
            'post_content'   => $this->get_value("evidence"), // The full text of the post.
            'post_title'     => $this->get_value("name"), // The title of your post.
            'post_slug'    => $this->get_value("slug"), // Default 'draft'.
            'post_status'    => $this->get_value("post_status"), // Default 'draft'.
            'post_type'      => $this->post_type, // Default 'post'.
            'post_author'    => $this->get_value("post_author"), // The user ID number of the author. Default is the current user ID.
            'ping_status'    => 'closed',
            'comment_status' => 'closed'
        );
        $id = $this->get_key_value();
        if ($id != NULL && trim($id) != "") {
            $post["ID"] = $id;
        }
        $post_id = wp_insert_post($post);
        if($post_id != 0){
            $this->set_value($this->get_key(), $post_id);
            $this->save_meta();
        }
    }
    
    function save_meta(){
        $meta_posts = array(
            "template"=>'wpgamify-award-choose-badge',
            "email"=>'wpgamify-award-email-address',
            "expires"=>'wpgamify-award-expires',
            "award_status"=>"wpgamify-award-status"
            );
        foreach($meta_posts as $k => $v){
            $this->update_meta($v, $this->get_value($k));
        }
        if($this->get_value("award_status") == NULL){
            $post_status = get_post_status($post_id);
            if (get_post_meta($post_id, 'wpgamify-award-status', true) == false &&
                    $post_status == "publish"){
                add_post_meta($post_id, 'wpgamify-award-status', 'Awarded');
            }
        }

        // Add the salt only the first time, and do not update if already exists
        if (get_post_meta($post_id, 'wpgamify-award-salt', true) == false) {
            $salt = substr(str_shuffle(str_repeat("0123456789abcdefghijklmnopqrstuvwxyz", 8)), 0, 8);
            add_post_meta($post_id, 'wpgamify-award-salt', $salt);
        }
    }
    
    function update_meta($meta_key,$new_value){
        $old_value = get_post_meta($this->get_key_value(),$meta_key,true);
        if ($new_value && empty($old_value))
            add_post_meta($this->get_key_value(), $meta_key, $new_value, true);
        elseif (current_user_can('manage_options')) {
            if (empty($new_value)){
                delete_post_meta($this->get_key_value(), $meta_key, $old_value);
            }elseif ($new_value && $new_value != $old_value){
                delete_post_meta($this->get_key_value(), $meta_key, $old_value);
                update_post_meta($this->get_key_value(), $meta_key, $new_value);
            }
        }
    }
    
    function delete(){
        
    }
}

?>