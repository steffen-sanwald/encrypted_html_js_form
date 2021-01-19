<?php
$smtp_data=[
    "from"=>"no-reply@test.com",
    "reply-to"=>"reply-to@test.com",
    "dest"=>"dest@test.com",
    "msg-id"=>$_SERVER["SERVER_NAME"]
];

/* <!-- official dummy value for captcha secret taken from https://docs.hcaptcha.com.
    Can be used for debugging.
    Replace value with the one from your hcaptcha instance --> */
$captcha_secret ="0x0000000000000000000000000000000000000000"; 

?>