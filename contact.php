<?php
include_once("config.php");

function check_captcha($captcha_response){
    global $captcha_secret;
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
    }catch(Exception $e){
        return array("Invalid","Exception during hcaptcha curl in backend","");
    }
    
    $responseData = json_decode($verifyResponse);
    //echo("ResponseData".$verifyResponse);
    return $responseData->success;
}

function check_userinput(){    
    /**
     * check userinput restrictively
     * only allowed characters are accepted
     */
    
    if(!isset($_POST['encryptedmessage']) || empty($_POST['encryptedmessage'])){
        return array("Invalid","No/empty message was submitted","");
    }
    if(!isset($_POST['h-captcha-response']) || empty($_POST['h-captcha-response'])){
        return array("Invalid","No/empty captcha was submitted","");
    }
    //check  if captcha response only comprises allowed characters
    if(!preg_match("#^[a-zA-Z0-9-._]+$#",$_POST['h-captcha-response'])){
        return array("Invalid", "Non allowed characters in h-captcha-response","");
    }
    if(!check_captcha($_POST['h-captcha-response'])){
        return array("Invalid","Invalid captcha","");
    }

    //check if pgp encrpyted message only comprises allowed characters --> needs more testing before discarding its content. 
    //Maybe write it into log and send admin an default e-mail instead
    if(!preg_match("/[a-zA-Z0-9\-\+\/\r\n\=\:\.\ ]+/m",$_POST['encryptedmessage'])){    
        $msg=$_POST['encryptedmessage'];
        $msg=str_replace("-----BEGIN PGP MESSAGE-----","",$msg);
        $msg=str_replace("-----END PGP MESSAGE-----","",$msg);
        $msg=str_replace("Version:","",$msg);
        $msg=str_replace("OpenPGP.js","",$msg);
        $msg=str_replace("Comment:","",$msg);
        $msg=str_replace("https://openpgpjs.org","",$msg);
        $msg=preg_replace("(v[0-9\.]+)"," ",$msg);
        $msg=trim($msg);
        //Regex for allowed chars in pgp based on
        //https://crypto.stackexchange.com/questions/18517/what-characters-are-valid-in-pgp-encrypted-and-signed-messages
        if(preg_match("[a-zA-Z0-9\+\/\=\ ]",$msg)){ 
            $msg="-----BEGIN PGP MESSAGE-----\r\n".$msg."\r\n-----END PGP MESSAGE-----";
            return array("Valid", "Input was not catched by original filter, but seems valid",$msg);
        }else{
            return array("PostAnalysisNeeded", "Version and Comment field probably not correct. Encoding input as base64 for post-analysis",base64_encode($_POST['encryptedmessage']));    
        }        
    }    
    return array("Valid","",$_POST['encryptedmessage']);
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
    echo("Mime:<br>".$mime);
    echo("Headers:<br>".$headers);
    try{
        mail($dest, $subject, $mime, $headers);
        return TRUE;
    }
    catch(Exception $e){
        return FALSE;
    }
}
function write_to_log($resp,$mail_success){
    $log_dir="."; 
    array_push($resp,$resp ? 'MailSuccess' : 'MailFailed');
    
    $log="SecureContactForm:".implode(":::",$resp);    
    echo("Log".$log."<br>");
    if($resp[0]=="Valid"){ //only write validated input to logging file.
        file_put_contents($log_dir.'/log_'.$resp[0]."_".date("j.n.Y").'.log', $log."\r\n", FILE_APPEND);        
    }
    else{
        if($resp[0]=="PostAnalysisNeeded"){
            $log=$log.":::PostAnalysisNeeded";
        }
        error_log($log."\r\n");
    }
    
}
$response=check_userinput();
echo("Response:".$response[0]."<br>");
echo("Info:".$response[1]."<br>");
echo("PGP:".$response[2]."<br>");
if($response[0]=="Valid"){
    $res_mail=write_mail($response[2]);
}
write_to_log($response,$res_mail);

?>