<?php

/**
 * Create log
 * @param string|array $arMsg
 */
function _log($arMsg,$file_name = 'Sofiaman_') {

    $stEntry = "";

    $arLogData['event_datetime'] = '[' . date('D Y-m-d h:i:s A') . '] [client ' . ']';

    if (is_array($arMsg)) {

        foreach ($arMsg as $key => $msg)
            $stEntry .= $arLogData['event_datetime'] . " " . " $key => " . "" . print_r($msg, 1) . "\r\n";
    } else {
        $stEntry .= $arLogData['event_datetime'] . " " . $arMsg . "\r\n";
    }

    $stCurLogFileName = $file_name . date('Ymd') . '.txt';  
    $storagePath = storage_path() .DIRECTORY_SEPARATOR . 'logs'.DIRECTORY_SEPARATOR ;
   
    
    $fHandler = fopen($storagePath . $stCurLogFileName, 'a+');


    fwrite($fHandler, $stEntry);

    fclose($fHandler);
}

/**
 * show formatted message.
 * @param mixed $value
 */
function pre_print( $value ){
    echo '<pre>'.print_r($value,1).'</pre>';
}
