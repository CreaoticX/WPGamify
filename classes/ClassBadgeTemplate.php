<?php

require_once 'ClassBase.php';

class GamifyBadgeTemplate extends GamifyBase{
    
    private $post_type = 'badgetemplate';
    
    function __construct() {
        parent::__construct();
        $this->post_type = apply_filters('wpgamify_badge_template_post_type_name', 'badgetemplate');
        $this->table = "";
        $this->key_id = "post_id";
        $this->v = array($this->key_id=>NULL,'name'=>NULL,'description'=>NULL,'imageUrl'=>NULL,'criteria'=>NULL, 'badge_url'=>NULL,
            'version'=>NULL,'post_status'=>NULL, 'post_author'=>NULL,'tags'=>array(),'categories'=>array());
        $this->loadable_keys = array();
    }
    
    function load_by_key($id){
        $post = get_post( $id);
        if ($post != NULL && $post->post_type  == $this->get_post_type()){
            $post_id = $post->ID;
            
            $this->set_value($this->key_id, $post_id);
            $this->set_value('post_status', $post->post_status);
            $this->set_value('post_author', $post->post_author);
            $this->set_value('badge_url', get_permalink($post_id));
            $this->set_value("version", get_post_meta($post_id, 'wpgamify_badge_version', true));
            $this->set_value("criteria", get_post_meta($post_id, 'wpgamify_badge_criteria', true));
            $this->set_value("description", get_post_meta($post_id, 'wpgamify_badge_description', true));
            $this->set_value("name", $post->post_title );
            if (has_post_thumbnail( $post_id ) ){
                $this->set_value("imageUrl", wp_get_attachment_url( get_post_thumbnail_id($post_id) ));
            }
            $this->set_value("tags", wp_get_post_terms($post_id, $this->post_type.'tags', array("fields" => "names")));
            $this->set_value("categories", wp_get_post_terms($post_id, $this->post_type.'categories', array("fields" => "names")));
        }
    }
    
    function get_post_type(){
        return $this->post_type;
    }
    
    function exists(){
        $id = $this->get_key_value();
        if ($id != NULL && trim($id) != "") {
            $post = get_post( $id, ARRAY_A);
            if ($post->post_type  == $this->post_type()){
                return TRUE;
            }
        }
        return FALSE;
    }
    
    function update_db() {
        $post = array(
            'post_content'   => "", // The full text of the post.
            'post_title'     => $this->get_value("name"), // The title of your post.
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
        $post_id = $this->get_key_value();
        $meta_key = 'wpgamify_badge_version';
        $new_meta_value = $this->get_value("version");
        if (preg_match('/^\d+$/', $new_meta_value)) {
            $new_meta_value .= '.0';
        } elseif (!preg_match('/^\d+(\.\d+)+$/', $new_meta_value)) {
            $new_meta_value = '1.0';
        }

        $meta_value = get_post_meta($post_id, $meta_key, true);

        if ($new_meta_value && '' == $meta_value){
            add_post_meta($post_id, $meta_key, $new_meta_value, true);
        }elseif ($new_meta_value && $new_meta_value != $meta_value){
            delete_post_meta($post_id, $meta_key);
            update_post_meta($post_id, $meta_key, $new_meta_value);
        }elseif ('' == $new_meta_value && $meta_value){
            delete_post_meta($post_id, $meta_key, $meta_value);
        }

        $meta_key = 'wpgamify_badge_description';
        $description = $this->get_value("description");
        $meta_value = strip_tags($description);

        if (empty($meta_value)){
            delete_post_meta($post_id, $meta_key);
        }else{
            delete_post_meta($post_id, $meta_key);
            update_post_meta($post_id, $meta_key, $meta_value);
        }

        $meta_key = 'wpgamify_badge_criteria';
        $criteria = $this->get_value("criteria");
        $meta_value = strip_tags($criteria);

        if (empty($meta_value)){
            delete_post_meta($post_id, $meta_key);
        }else{
            delete_post_meta($post_id, $meta_key);
            update_post_meta($post_id, $meta_key, $meta_value);
        }

        if(is_array($this->tags)&&!empty($this->tags)){
            $tag_arr = $this->term_ids($this->tags, $this->post_type.'tags');
            wp_set_object_terms( $post_id, $tag_arr, $this->post_type.'tags', FALSE );
        }
        if(is_array($this->categories)&&!empty($this->categories)){
            $cat_arr = $this->term_ids($this->categories, $this->post_type.'categories');
            wp_set_object_terms( $post_id, $cat_arr, $this->post_type.'categories', FALSE );
        }
    }
    
    function term_ids($terms, $tax){
        $return_terms = array();
        foreach ($terms as $term){
            $term = get_term_by('name',$term,$tax);
            if($term){
                $return_terms[]=$term->term_id;
            }  else {
                $term_arr = wp_insert_term($term,$tax);
                $return_terms[] = $term_arr['term_id'];
            }
        }
        return $return_terms;
    }
            
    function delete(){
        
    }
}

?>