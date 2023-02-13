import LocomotiveScroll from "locomotive-scroll";
const imagesLoaded = require("imagesloaded");





/*~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~
             global variable scroll 
 ~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~*/

var scroll;






function smooth(scrollContainer) {
    let currentScrollContainer = document.querySelector(
        "[data-scroll-container]"
    );
    let options = {
        el: currentScrollContainer,
        smooth: true,
        getSpeed: true,
        getDirection: true,
    };


    scroll = new LocomotiveScroll(options);





    /*~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~
         Update scroll height when all images loaded 
      ~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~*/

    imagesLoaded(currentScrollContainer, { background: true }, function () {
        setTimeout(() => { scroll.update() }, 300)
    });






    /*~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~
                  Scroll Direction Up Down 
      ~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~*/

      scroll.on("scroll", (obj) => {
        document.documentElement.setAttribute("data-direction", obj.direction);
        const hProgress = (obj.scroll.y / obj.limit.y) * 100;
        if (hProgress <= 1) {
          if (hProgress <= 1) document.documentElement.setAttribute("data-direction", "up");
        }
      });



}




export { scroll, smooth };