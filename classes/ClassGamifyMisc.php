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

    function relativeTime($timestamp) {
        $difference = time() - $timestamp;
        $periods = array(__('sec', 'cp'), __('min', 'cp'), __('hour', 'cp'), __('day', 'cp'), __('week', 'cp'), __('month', 'cp'), __('year', 'cp'), __('decade', 'cp'));
        $lengths = array("60", "60", "24", "7", "4.35", "12", "10");
        if ($difference >= 0) { // this was in the past
            $ending = __('ago', 'cp');
        } else { // this was in the future
            $difference = -$difference;
            $ending = __('to go', 'cp');
        }
        for ($j = 0; $difference >= $lengths[$j]; $j++)
            $difference /= $lengths[$j];
        $difference = round($difference);
        if ($difference != 1)
            $periods[$j].= 's';
        $text = "$difference $periods[$j] $ending";
        return $text;
    }

}
?>