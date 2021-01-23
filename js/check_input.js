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
                  return [false,null,"ElemIsNull"];
    }
    var input_list=form.getElementsByTagName("input");
    for(let cur_input_elem of input_list){
        var cur_input=cur_input_elem.value;
        	
        if(cur_input_elem.id=="encryptedmessage"){
            continue;				
        }		
        if(cur_input.length<2){            
            return [false,cur_input_elem, "TooSmall"];
        }
        if(cur_input_elem.id=="email" && !validateEmail(cur_input)){            
            return [false, cur_input_elem,"InvalidMail"];				
        }		

    };		
    return [true,"OK"];
    
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