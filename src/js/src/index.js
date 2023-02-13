import { isMobile, select } from "./Module/Helper";
import { Fancybox } from "@fancyapps/ui";
import gsap from "gsap";

import { smooth } from "./Module/loco";
smooth();

/*~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~
                 Registration Form Popup
~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~*/

const closeBtn = document.querySelectorAll(".inner.close-btn");
const registerForm = document.querySelector(".inner.registation-form");
const overlay = document.querySelector(".inner.overlay");
const allblocks = document.querySelectorAll(".event-container .block");

allblocks.forEach((block, index) => {
    const btn = block.querySelector(".register-button");

    if (btn) {
        const formIndex = document.querySelector(`.form-${index + 1}`);

        btn.addEventListener("click", function () {
            showform(formIndex);
        });
    }
});

function showform(formNo) {
    registerForm.style.opacity = 1;
    registerForm.style.pointerEvents = "all";
    overlay.style.display = "block";
    formNo.style.display = "block";
    gsap.set(formNo, { display: "block" });
}

function closeRegisterForm() {
    registerForm.style.opacity = 0;
    registerForm.style.pointerEvents = "none";
    overlay.style.display = "none";
    gsap.set(".cf-forms", { display: "none" });
}

if (overlay) {
    overlay.addEventListener("click", closeRegisterForm);
}

if (closeBtn) {
    closeBtn.forEach((element) => {
        element.addEventListener("click", closeRegisterForm);
    });
}




/*~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~
                        Vimeo
~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~*/

import { initVimeo } from "./Module/vimeo";
initVimeo();

/*~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~
                        random Number
~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~*/

// function test() {
//     var min = 10;
//     var max = 20;
//     var num = Math.floor(Math.random() * (max - min + 1)) + min;
//     var field = document.getElementById('sequence-generator');
//     if(field){
//         field.value= num
//     }
//     console.log(num)
//   }
//   window.onload = test;