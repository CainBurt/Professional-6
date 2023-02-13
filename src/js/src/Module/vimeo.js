import Player from "@vimeo/player";

var vid1;


function playVimeo(el) {
  vid1 = new Player(el, {
    id: el.getAttribute("data-player-id"),
    autoplay: true,
    controls: false,
    loop: true,
  });

  vid1.setVolume(0);

  const muteUnmute = document.getElementById("mute-unmute");

  muteUnmute.addEventListener("click", function () {
    if (muteUnmute.classList.contains("mute")) {
      muteUnmute.className = "unmute";
      vid1.setVolume(1);
    } else {
      muteUnmute.className = "mute";
      vid1.setVolume(0);
    }
  });
}

function initVimeo() {
  const vimeoId = document.getElementById("vid1");
  if (vimeoId) {
    playVimeo(vimeoId);
    makeFullScreen();
  }
}
export { initVimeo };
