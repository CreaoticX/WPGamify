<?php

require_once 'ClassGamifyMisc.php';
require_once __DIR__ . '/../config.inc.php';

class GamifyBase{
    protected $table = "";
    protected $key_id = "";
    protected $v = array();
    //protected $fields = array();
    protected $loadable_keys = array();
    
    function __construct() {
        $this->table = "";
        $this->key_id = "";
        $this->v = array($this->key_id=>NULL);
        $this->loadable_keys = array();
    }
    
    function toArray(){ return $this->v;}

    function load_by_value($id,$value){
        
    }

    function load_by_key($id){
        
    }
    
    function get_key(){return $this->key_id;}
    
    function get_table(){return $this->table;}
    
    function get_key_value(){return $this->v[$this->key_id];}

    function set_value($k, $v){
            if(array_key_exists($k,$this->v)){
                    $this->v[$k] = $v;
                    return true;
            }else{
                    return false;
            }
    }

    function get_value($k){
            if(array_key_exists($k,$this->v)){
                    return $this->v[$k];
            }else{
                    return false;
            }
    }

    function exists(){
        
    }

    function update_db() {
        
    }

    function delete(){
       
    }
}