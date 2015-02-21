<?php

class GamifyMisc{
    function error($code = 0, $message = "", $a = true){
        if($a){
            return array( "result" => array(), "error" => array("id" => $code, "message" => $message));
        }else{
            return "Error Code " . $code . ": " . $message;
        }
    }


    function guid($h = true){
            if (function_exists('com_create_guid') === true){
                    $guid = trim(com_create_guid(), '{}');
                    if(!$h){
                            $guid = str_replace("-", "", $guid);
                    }
            return $guid;
        }else{
                    mt_srand((double)microtime()*10000);//optional for php 4.2.0 and up.
            $charid = strtoupper(md5(uniqid(rand(), true)));
                    if($h){
                            $hyphen = chr(45);// "-"
                    }else{
                            $hyphen = "";
                    }
            $uuid = substr($charid, 0, 8).$hyphen
                    .substr($charid, 8, 4).$hyphen
                    .substr($charid,12, 4).$hyphen
                    .substr($charid,16, 4).$hyphen
                    .substr($charid,20,12);// "}"
            return $uuid;
        }
    }

    function same($instance1, $instance2){
            if($instance1 == $instance2){
                    return true;
            }else{
                    $a =  array_diff_assoc($instance1->toArray(),$instance2->toArray());
                    if(count($a) > 0){
                            return false;
                    }else{
                            return true;
                    }
            }
    }

}
?>