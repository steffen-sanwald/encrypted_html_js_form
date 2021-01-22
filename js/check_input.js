function validateEmail(email) {
    /*
    validate email against allowed list
    https://stackoverflow.com/questions/46155/how-to-validate-an-email-address-in-javascript
    */
    const re = /^(([^<>()[\]\\.,;:\s@"]+(\.[^<>()[\]\\.,;:\s@"]+)*)|(".+"))@((\[[0-9]{1,3}\.[0-9]{1,3}\.[0-9]{1,3}\.[0-9]{1,3}\])|(([a-zA-Z\-0-9]+\.)+[a-zA-Z]{2,}))$/;
    return re.test(String(email).toLowerCase());
}

function is_form_input_valid(form){
    /*
    check input from form 
    */
    if(form==null || form.elements["contactname"]==null || form.elements["email"]==null || form.elements["subject"] == null || 
              form.elements["content"] == null){
                  return [false,"Contact form has HTML error"];
    }
    var valid=true;
    var status_msg="OK";
    $(form).children('input').each(function(){
        var cur_input=$(this);
        if(cur_input.length!=1){
            status_msg="Contact form has HTML error";
            result=false;
            return;
        }
        cur_input=cur_input[0];			
        if(cur_input.id=="encryptedmessage"){
            return;				
        }		
        if(cur_input.value.length<2){
            result=false;
            status_msg="Min length too small for "+cur_input.id;
            return;
        }
        if(cur_input.id=="email" && !validateEmail(cur_input.value)){
            result=false;
            status_msg="Email address is not in the correct format";
            return;				
        }		

    });		
    return [valid,status_msg];
    
}
function escapeHtml(unsafe) {
    /*
    escape input from server 
    https://stackoverflow.com/questions/6234773/can-i-escape-html-special-chars-in-javascript/18108463
    */
    return unsafe
        .replace(/&/g, "&amp;")
        .replace(/</g, "&lt;")
        .replace(/>/g, "&gt;")
        .replace(/"/g, "&quot;")
        .replace(/'/g, "&#039;");
}

function trim_pgp_message(encrypted){
    /*
        trimm input that only base64 characters from the actual pgp ciphertext will be transmitted to the backend.
        allow list check doesn't need to take care about difficult pgp comment stuff 
        --> more restrictive = more secure
    */
    var trimmed=encrypted;
    trimmed=trimmed.replace("-----BEGIN PGP MESSAGE-----","");
    trimmed=trimmed.replace("-----END PGP MESSAGE-----","");
    trimmed=trimmed.replace("Version:","");
    trimmed=trimmed.replace("OpenPGP.js","");
    trimmed=trimmed.replace("Comment:","");
    trimmed=trimmed.replace("https://openpgpjs.org","");
    trimmed=trimmed.replace(/(v\d\.[\d|\.]+)/gi,"");
    trimmed=trimmed.trim();
    return trimmed;
}

function reCaptchaOnFocus(evt) {
    /*
    Lazy load
    Function that loads recaptcha on form input focus
    https://antonioufano.com/articles/improve-web-performance-lazy-loading-recaptcha/
    */			
    console.log("Focusing form" +evt.type);		
    lazy_load_hcaptcha();
    remove_lazyload_handlers();
}

function remove_lazyload_handlers(){
    /*
    remove focus to avoid js error:
    */
    console.log("Removing handlers");
    document
    .getElementById('myForm')
    .removeEventListener('focus', reCaptchaOnFocus);
    document
    .getElementById('myForm')
    .removeEventListener('mouseover', reCaptchaOnFocus, false);
    document
    .getElementById('contactname')
    .removeEventListener('focus', reCaptchaOnFocus);
    document
    .getElementById('email')
    .removeEventListener('focus', reCaptchaOnFocus);
    document
    .getElementById('subject')
    .removeEventListener('focus', reCaptchaOnFocus);
    document
    .getElementById('content')
    .removeEventListener('focus', reCaptchaOnFocus);
    document
    .getElementById('submit-msg')
    .removeEventListener('focus', reCaptchaOnFocus);
}
function is_hcaptcha_already_loaded(){
    return document.getElementById("hcpatcha_lazy_load")!=null;
}

async function lazy_load_hcaptcha(){		
    /*
    lazy loads captcha
    */
    if(is_hcaptcha_already_loaded()){
        console.log("Loaded hcaptcha already");
        return;		}

    console.log("Lazy loading hcaptcha");
    var head = document.getElementsByTagName('head')[0];
    var script = document.createElement('script');
    script.type = 'text/javascript';
    script.src = 'https://www.hCaptcha.com/1/api.js';
    script.id="hcpatcha_lazy_load";
    
    head.appendChild(script);
}

function add_lazyload_handlers(){
    // add initial event listener to the form inputs
    document
        .getElementById('myForm')
        .addEventListener('focus', reCaptchaOnFocus, false);	
    document
        .getElementById('myForm')
        .addEventListener('mouseover', reCaptchaOnFocus, false);		
        
    document
        .getElementById('contactname')
        .addEventListener('focus', reCaptchaOnFocus, false);
    document
        .getElementById('email')
        .addEventListener('focus', reCaptchaOnFocus, false);
        document
        .getElementById('subject')
        .addEventListener('focus', reCaptchaOnFocus, false);
    document
        .getElementById('content')
        .addEventListener('focus', reCaptchaOnFocus, false);
        document
        .getElementById('submit-msg')
        .addEventListener('focus', reCaptchaOnFocus, false);
  console.log("added listeners for lazy loading");
}