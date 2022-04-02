<?php

/*
TO DO TASKS : 

    --> make a loop every second to READ AND PROCESS COMMUNICATION with Arduino
    --> found a method to send a "stop info" to distant server 
*/

// PHP Errors Displaying
error_reporting(E_ALL);
ini_set('display_errors',1);
//

require_once 'vendor/autoload.php';

/*
POST REQUEST TO DISTANT WEB-SERVICE :: USING CURL
*/
//Distant Web Service => Server Adress
$distantWebServiceAdress = '192.168.1.6:8888';
//Distant Web Service => Webservice location
$distantWebServiceSlug = '/e-go/distant_web-service.php';
//Distant Web Service => FULL URL
$distantWebServiceURL = 'http://' . $distantWebServiceAdress . $distantWebServiceSlug;

//Distant Web Service => Function Used to make POST Request
function sendPostRequest($url, $outputData){
    $ch = curl_init();

    $postdata = http_build_query($outputData);

    curl_setopt($ch, CURLOPT_URL, $url);
    curl_setopt($ch,CURLOPT_POST, true);
    curl_setopt($ch,CURLOPT_POSTFIELDS, $postdata);
    curl_setopt($ch,CURLOPT_RETURNTRANSFER, true);

    $result = curl_exec($ch);

    echo $result  ;

    curl_close($ch);
}
/*
SET SERIAL COMMINCATION WITH ARDUINO UNO BOARD :: USING SERIAL(PHP Lib)
*/

// Terminal command (Debian) used to define Connection Props for Serial Port used
exec("sudo stty -F /dev/ttyAMA0 9600 -parenb cs8 -cstopb");
// Opening Serial Port for data Reading
$fp = fopen("/dev/ttyACM0", "r+");

//PhpSerial instance and define communications Props.
$serial = new PhpSerial;
$serial->deviceSet("/dev/ttyACM0");
$serial->confBaudRate(9600);
$serial->confParity("none");
$serial->confCharacterLength(8);
$serial->confStopBits(1);
$serial->confFlowControl("none");

/*
READ AND PROCESS COMMINCATION WITH ARDUINO UNO BOARD
*/

// flag used to know if the bike is not in use
$zeroAmpFlag = false;

//If Serial Port is not Open
if(!$fp) { 
    echo "Not Open";

//If Serial Port is Open
}else{
    //Open Serial connection 
    $serial->deviceOpen();
    //catch Serial data flow and stock it on $readedRawValues
    $readedRawValues = $serial->readPort();
    
    //Check All data in $readedRawValues and sort by types :: Catch only numérical values with decimal 
    // Put decimals values into an Array :: $readedFloatValuesArray
    preg_match_all('/[0-9]+(?:.[0-9]+)/', $readedRawValues, $readedFloatValuesArray);
    
    print_r($readedFloatValuesArray);
    
    // if the first value is different of 0 (so if the bike make Ampers)
    if ($readedFloatValuesArray[0][0] != 0){ 

        // lowering the flag because the bike is used
        $zeroAmpFlag = false;

        // Put readed value into a key/value Array
        $data = [
                'bike-amper' => $readedFloatValuesArray[0][0]
        ];
            
        // Sending POST Request to ditant Web Service with Values
        sendPostRequest($distantWebServiceURL, $data);
    
    }else{ 
        //raise the flag because the bike is not in use
        $zeroAmpFlag = true;
    }
    
        $serial->deviceClose();
}




