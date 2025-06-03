  var menuBtn = document.getElementById("menuBtn");
    var closeBtn = document.getElementById("closeBtn");
    var navLinks = document.getElementById("navLinks");

    menuBtn.onclick = function() {
      navLinks.classList.add("show");//to call menu icon
    };
    closeBtn.onclick = function() {
      navLinks.classList.remove("show");//to remove menu icon
    };
