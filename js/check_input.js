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
            valid=false;
            return;
        }
        cur_input=cur_input[0];			
        if(cur_input.id=="encryptedmessage"){
            return;				
        }		
        if(cur_input.value.length<2){
            valid=false;
            status_msg="Min length too small for "+cur_input.id;
            return;
        }
        if(cur_input.id=="email" && !validateEmail(cur_input.value)){
            valid=false;
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