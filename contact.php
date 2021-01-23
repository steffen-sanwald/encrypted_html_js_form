<?php

// Import PHPMailer classes into the global namespace
// These must be at the top of your script, not inside a function

include_once("PHPMailer/src/PHPMailer.php"); 
include_once("PHPMailer/src/SMTP.php"); 
include_once("PHPMailer/src/Exception.php"); 
include_once("config.php");

use PHPMailer\PHPMailer\PHPMailer;
use PHPMailer\PHPMailer\SMTP;
use PHPMailer\PHPMailer\Exception;

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
    $msg="-----BEGIN PGP MESSAGE-----\r\n\r\n".$msg."\r\n-----END PGP MESSAGE-----";   
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
    $mail = new PHPMailer();
    try {       
        //Server settings
        
        $mail->SMTPDebug = SMTP::DEBUG_OFF;                      // 
        //$mail->SMTPDebug = SMTP::DEBUG_SERVER;                      // Uncomment to enable verbose debug output
        $mail->isSMTP();                                            // Send using SMTP
        $mail->Host       = $smtp_data["host"];                    // Set the SMTP server to send through
        $mail->SMTPAuth   = true;                                   // Enable SMTP authentication
        $mail->Username   = $smtp_data["username"];                     // SMTP username
        $mail->Password   = $smtp_data["password"];                               // SMTP password
        $mail->SMTPSecure = PHPMailer::ENCRYPTION_STARTTLS;         // Enable TLS encryption; `PHPMailer::ENCRYPTION_SMTPS` encouraged
        $mail->Port       = $smtp_data["port"];                                    // TCP port to connect to, use 465 for `PHPMailer::ENCRYPTION_SMTPS` above
    
        //Recipients
        $mail->setFrom($smtp_data["from"], $smtp_data["from-name"]);
        $mail->addAddress($smtp_data["dest"], $smtp_data["dest-name"]);     // Add a recipient
    
        $mail->isHTML(true);                                  // Set email format to HTML
        $mail->Subject = $smtp_data["mail-subject"];
        //$mail->Body    = 'This is the HTML message body <b>in bold!</b>';
        $mail->Body = $content; //set the pgp encrypted message directly. Thunderbird will decode it automatically.
        $mail->AltBody = 'This is the body in plain text for non-HTML mail clients';
        
        $mail->send();
        add_log_entry("Sending mail successfully");
    } catch (Exception $e) {
        if(DEBUG){//don't know if error message leaks info
            add_log_entry("SecureContactForm:Message could not be sent. Mailer Error: {$mail->ErrorInfo}");
        }
        else{
            add_log_entry("SecureContactForm:Could send mail");
        }
        
        
        
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