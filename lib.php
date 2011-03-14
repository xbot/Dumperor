<?php
function array_printf($tmpl, $arr) 
{ 
    return call_user_func_array('sprintf', array_merge((array)$tmpl, $arr)); 
}
?>
