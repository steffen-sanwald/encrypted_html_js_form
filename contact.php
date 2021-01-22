<?php
define('VERIFY_CAPTURE',true); //default=true
define('DEBUG',false);//default=false
define('ADD_LOG_TO_JSON_OBJ',false);//default=false
include_once("config.php");
$global_logs=array();

function add_log_entry($log_msg){
    global $global_logs;
    $global_logs[]=$log_msg;    
}

function check_captcha($captcha_response){
    /**
     * checks the user given captcha against the server
     */
    global $captcha_secret;
    add_log_entry("Checking Captcha");
    // get verify response        
    $verify = curl_init();    
    $data = array(
        'secret' => $captcha_secret,
        'response' => $captcha_response
    );    
    curl_setopt($verify, CURLOPT_URL,   "https://hcaptcha.com/siteverify");
    curl_setopt($verify, CURLOPT_POST, true);
    curl_setopt($verify, CURLOPT_POSTFIELDS, http_build_query($data));
    curl_setopt($verify, CURLOPT_RETURNTRANSFER, true);
    //catch all exception to avoid leaking your secret to the user
    try{
        $verifyResponse = curl_exec($verify);
        add_log_entry("Captcha server queried done");
    }catch(Exception $e){
        return array(
            "result"=>"Invalid",
            "info" => "Exception during hcaptcha curl in backend",
            "msg" => "");
    }
    
    $responseData = json_decode($verifyResponse);
    add_log_entry("Captcha Evaluated Response:".$verifyResponse);
    return $responseData->success;
}

function check_userinput(){    
    /**
     * check userinput restrictively
     * only allowed characters are accepted
     */
    add_log_entry("Checking userinput");
    if(!isset($_POST['encryptedmessage']) || empty($_POST['encryptedmessage'])){        
        return array(
            "result"=>"Invalid",
            "info" => "No/empty message was submitted",
            "msg" => "");
    }
    if(VERIFY_CAPTURE){
        if(!isset($_POST['h-captcha-response']) || empty($_POST['h-captcha-response'])){            
            return array(
                "result"=>"Invalid",
                "info" => "No/empty captcha was submitted",
                "msg" => "");
        }
        $capt_resp=$_POST['h-captcha-response'];
        //check  if captcha response only comprises allowed characters
        preg_match("#^[a-zA-Z0-9-._]+$#",$capt_resp,$matches);
        if(sizeof($matches)!=1 || $matches[0]!=$capt_resp){            
            return array(
                "result"=>"Invalid",
                "info" => "Non allowed characters in h-captcha-response",
                "msg" => "");
        }
        if(!check_captcha($_POST['h-captcha-response'])){            
            return array(
                "result"=>"Invalid",
                "info" => "Invalid captcha",
                "msg" => "");
        }
    }   
    
    //check if trimmed pgp encrpyted message (without comments,preamble,etc.. ) only comprises base64 characters
    //Regex for allowed base64 chars in pgp based on
    //https://crypto.stackexchange.com/questions/18517/what-characters-are-valid-in-pgp-encrypted-and-signed-messages            
    $msg=$_POST['encryptedmessage'];
    add_log_entry("Raw base64 msg:\"".$msg."\"");
    $msg=str_replace(" ","+",$msg);//recover base64 character + 
    add_log_entry("Recovered base64 msg:\"".$msg."\"");
    preg_match("#[a-zA-Z0-9+\/=\r\n]+#",$msg,$matches);        
    if(sizeof($matches)!=1 || $matches[0]!=$msg){
        $msg="-----BEGIN-BASE64-ERR-MESSAGE-----\r\n".base64encode($msg)."\r\n-----END-BASE64-ERR-MESSAGE-----";        
        return array(
            "result"=>"Invalid",
            "info" => "None base64 characters detected in PGP content",
            "msg" => $msg);  
    }    
    $msg="-----BEGIN PGP MESSAGE-----\r\n".$msg."\r\n-----END PGP MESSAGE-----";   
    add_log_entry("Passed Base64 pgp message filter");
    return array(
        "result"=>"Valid",
        "info" => "Input was transmitted properly",
        "msg" => $msg);
    
}

function write_mail($content){
    /*
        based on https://thomas.gouverneur.name/2012/04/20120430sending-pgp-html-encrypted-e-mail-with-php/
    */
    global $smtp_data;
    
    $pgpmime = "";
    $mime = "";
    $headers = "";
    $dest = $smtp_data["dest"];
    $subject = "My HTML crypted report";
    $clearContent = "<html><p>This is the report in cleartext!</p></html>";
    $clearText = "This is the text version of the report";
    /* Prepare the crypted Part of the message */
    $bound = "————".substr(strtoupper(md5(uniqid(rand()))), 0, 25);
    $pgpmime .= "Content-Type: multipart/alternative;\r\n boundary=\"$bound\"\r\n\r\n";
    $pgpmime .= "This is a multi-part message in MIME format.\r\n";
    $pgpmime .= "–$bound\r\n";
    $pgpmime .= "Content-Type: text/plain; charset=utf-8\r\n";
    $pgpmime .= "Content-Transfer-Encoding: quoted-printable\r\n\r\n";
    $pgpmime .= $clearText."\r\n\r\n";
    $pgpmime .= "–$bound\r\n";
    $pgpmime .= "Content-Type: text/html; charset=\"utf-8\"\r\n";
    $pgpmime .= "Content-Transfer-Encoding: quoted-printable\r\n\r\n";
    $pgpmime .= $clearContent."\r\n";
    $pgpmime .= "–".$bound."–\r\n";
    //$content = GPG::cryptTxt($pgpkey, $pgpmime);
    /* Make the email"s headers */
    $headers = "";
    $headers = "From: ".$smtp_data["from"]."\r\n";
    $headers .= "Reply-to: ".$smtp_data["reply-to"]."\r\n";
    $headers .= "X-Sender: WeSunSolve v2.0\r\n";
    $headers .= "Message-ID: <".time()."@".$smtp_data["msg-id"].">\r\n";
    $headers .= "Date: " . date("r") . "\r\n";
    $bound = "————enig".substr(strtoupper(md5(uniqid(rand()))), 0, 25);
    $headers .= "MIME-Version: 1.0\r\n";
    $headers .= "Content-Type: multipart/encrypted;\r\n";
    $headers .= " protocol=\"application/pgp-encrypted\";\r\n";
    $headers .= " boundary=\"".$bound."\"\r\n\r\n";
    /* And the cleartext body which encapsulate PGP message */
    $mime = "";
    $mime .= "This is an OpenPGP/MIME encrypted message (RFC 2440 and 3156)\r\n";
    $mime .= "–".$bound."\r\n";
    $mime .= "Content-Type: application/pgp-encrypted\r\n";
    $mime .= "Content-Description: PGP/MIME version identification\r\n\r\n";
    $mime .= "Version: 1\r\n\r\n";
    $mime .= "–".$bound."\r\n";
    $mime .= "Content-Type: application/octet-stream; name=\"encrypted.asc\"\r\n";
    $mime .= "Content-Description: OpenPGP encrypted message\r\n";
    $mime .= "Content-Disposition: inline; filename=\"encrypted.asc\"\r\n\r\n";
    $mime .= $content."\r\n";
    $mime .= "–".$bound."–";
    
    add_log_entry("Sending mail:");
    add_log_entry("Mime:".$mime);
    add_log_entry("Headers:".$headers);
        
    try{
        mail($dest, $subject, $mime, $headers);
        add_log_entry("Sending mail successfully");
        return TRUE;
    }
    catch(Exception $e){
        add_log_entry("SecureContactForm:Could send mail:::".$mime.":::".$headers);
        return FALSE;
    }
}
function write_to_logfile($resp,$mail_success){
    /*
    write result to log file
    only validated content is allowed for the custom log
    all others shall be handled by the webserver log in a secure fashion
    */
    $log_dir="."; 
    $log_file=$log_dir.'/log_'.$resp["result"]."_".date("j.n.Y").'.log';
    if($resp["result"]=="Valid"){ //only write validated input to logging file.
        $log="SecureContactFormValid:".implode(":::",$resp);    
        try{
            file_put_contents($log_file, $log."\r\n", FILE_APPEND);     
            return true;   
        }
        catch(Exception $e){
            add_log_entry("Could write valid input to logfile:".$log_file.":::".$log);
        }
        
    }    
    $log="SecureContactFormFail:".implode(":::",$resp);    
    error_log("CentralLoggingStatement:".$log."\r\n");
    return false;
    
    
}
$response=check_userinput();
$res_mail="";
if($response["result"]=="Valid"){
    $res_mail=write_mail($response["msg"]);
}

write_to_logfile($response,$res_mail);
if(DEBUG){
    if(ADD_LOG_TO_JSON_OBJ){
        $response["log"]=$global_logs;
    }    
    echo(json_encode($response));
}
else{
    echo($response["result"]); //hide the error messages
}

?>