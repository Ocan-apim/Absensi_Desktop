(function (global) {
    function initialsFromUsername(name) {
        var n = (name || "?").trim();
        if (!n) return "?";
        return n.slice(0, 2).toUpperCase();
    }

    global.DashboardAvatar = {
        bind: function (profileHref) {
            var link = document.getElementById("header-avatar-link");
            var img = document.getElementById("header-avatar-img");
            var fb = document.getElementById("header-avatar-fallback");
            if (!link || !global.AbsensiAuth) return;

            if (profileHref) link.setAttribute("href", profileHref);

            function refresh() {
                var s = AbsensiAuth.get();
                if (!s) return;
                var url = AbsensiAuth.getAvatarDataUrl && AbsensiAuth.getAvatarDataUrl();
                if (url && img) {
                    img.src = url;
                    img.classList.remove("hidden");
                    if (fb) fb.classList.add("hidden");
                } else {
                    if (img) {
                        img.removeAttribute("src");
                        img.classList.add("hidden");
                    }
                    if (fb) {
                        fb.textContent = initialsFromUsername(s.displayName || s.name || s.username);
                        fb.classList.remove("hidden");
                    }
                }
            }

            refresh();
            global.addEventListener("storage", function (e) {
                if (e.key && e.key.indexOf("absensi_avatar_") === 0) refresh();
            });
            global.DashboardAvatar.refresh = refresh;
        }
    };
})(window);
