<?php

define('VERIFY_CAPTURE',true); //default=true
define('DEBUG',false);//default=false
define('ADD_LOG_TO_JSON_OBJ',false);//default=false

$smtp_data=[
    "host"=> "stmp.test.com",
    "username" => "no-reply@test.com",
    "password" => "secret",
    "port" => 587,
    "from"=>"no-reply@test.com",
    "from-name" => "Contact request",    
    "dest"=>"contact@test.com",
    "dest-name" => "Contact Mailbox",
    "mail-subject" => "New customer request from website"
];


/* <!-- official dummy value for captcha secret taken from https://docs.hcaptcha.com.
    Can be used for debugging.
    Replace value with the one from your hcaptcha instance --> */
$captcha_secret ="0x0000000000000000000000000000000000000000"; 

?>