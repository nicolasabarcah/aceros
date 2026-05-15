(function() {
    if (window.location.protocol !== "file:") {
        return;
    }

    var path = window.location.pathname.replace(/\\/g, "/");
    var fileName = path.split("/").pop() || "index.html";
    var query = window.location.search || "";
    var hash = window.location.hash || "";
    var targetUrl = "http://localhost/aceros/app/" + fileName + query + hash;

    window.location.replace(targetUrl);
})();