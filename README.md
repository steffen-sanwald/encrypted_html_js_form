# encrypted_html_js_form

## Main Features
- Strong client-side PGP encryption using OpenPGPJS. Make sure only the intended recipient can decrypt the message. All metadata Name,Email,Subject are encrypted, too
- Enhanced server side input validation. Only allow PGP encrypted messages. Bots would need to encrypt messages to spam your Inbox.
- Spam protection by HCaptcha (more privacy friendly than Google's reCaptcha)
- Lazy Loading of HCaptcha: only loaded when website visitor focuses/insert on contact form (until then, no Cookies are needed).
