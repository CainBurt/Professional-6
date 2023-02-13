const registerBtn = document.querySelector(".register-button");
const cancel_form_btn = document.querySelector("#cancel_form_btn");
const closeButton = document.querySelector(".close-btn");
const registerForms = document.querySelector(".registation-form");
const overlays = document.querySelector(".overlay");
const confirmbutton = document.querySelector(".confirm_button");
const cancelbutton = document.querySelector(".cancel_button");


jQuery(document).ready(function(){
  jQuery(".cancel-page .submit-btn").hide();
});


if (registerForms) {
  // registerBtn.addEventListener("click", function () {
    registerForms.style.opacity = 1;
    registerForms.style.pointerEvents = "all";
    overlays.style.display = "block";
  // });
}

if(cancel_form_btn){ 
  cancel_form_btn.addEventListener("click", function () { 
    registerForms.style.opacity = 1;
    registerForms.style.pointerEvents = "all";
    overlays.style.display = "block";
  });
}

if (closeButton) {
  closeButton.addEventListener("click", function () {
    // registerForms.style.opacity = 0;
    // registerForms.style.pointerEvents = "none";
    // overlays.style.display = "none";
    document.location.href="/";
  });
}

if(confirmbutton)
{ 
  confirmbutton.addEventListener("click", function () { 
    jQuery(".submit-btn").trigger('click');
    jQuery('.close-btn').trigger('click');
  });
}
if(cancelbutton)
{ 
  cancelbutton.addEventListener("click", function () { 
     jQuery('.close-btn').trigger('click');
  });
}
const select1 = document.querySelector("select option");
if (select1) {
  select1.setAttribute("disabled", "");
  select1.setAttribute("selected", "");
}

document.addEventListener(
  "wpcf7mailsent",
  function (event) { 

    // hide form of cancel page
    jQuery('.cancel-page .contact-form').hide();
    jQuery('.cancel-page .register-button').hide();
    
  },
  false
);
