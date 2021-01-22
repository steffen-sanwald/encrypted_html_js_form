
function reCaptchaOnFocus(evt) {
    /*
    Lazy load
    Function that loads recaptcha on form input focus
    https://antonioufano.com/articles/improve-web-performance-lazy-loading-recaptcha/
    */			
    console.log("Focusing form:" +evt.type);		
    lazy_load_hcaptcha();
    remove_lazyload_handlers();
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
        return;		
    }    
    var head = document.getElementsByTagName('head')[0];
    var script = document.createElement('script');
    script.type = 'text/javascript';
    script.src = 'https://www.hCaptcha.com/1/api.js';
    script.id="hcpatcha_lazy_load";    
    head.appendChild(script);
    console.log("Lazy loaded hcaptcha");
}

function add_lazyload_handlers(){
    /* 
    add initial event listener to the form inputs
    */
   
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

function remove_lazyload_handlers(){
    /*
    remove focus to avoid js error:
    */    
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
    console.log("Removed handlers");
}