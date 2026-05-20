(function (global) {
    var AUTH_KEY = "absensi_auth";

    global.AbsensiAuth = {
        get: function () {
            try {
                var raw = sessionStorage.getItem(AUTH_KEY);
                if (raw) {
                    try {
                        localStorage.setItem(AUTH_KEY, raw);
                    } catch (e) {}
                } else {
                    raw = localStorage.getItem(AUTH_KEY);
                }
                return raw ? JSON.parse(raw) : null;
            } catch (e) {
                return null;
            }
        },
        set: function (data) {
            sessionStorage.setItem(AUTH_KEY, JSON.stringify(data));
            try {
                localStorage.setItem(AUTH_KEY, JSON.stringify(data));
            } catch (e) {}
        },
        update: function (patch) {
            var current = this.get() || {};
            var next = Object.assign({}, current, patch || {});
            this.set(next);
            return next;
        },
        clear: function () {
            sessionStorage.removeItem(AUTH_KEY);
            try {
                localStorage.removeItem(AUTH_KEY);
            } catch (e) {}
        },
        logout: function () {
            this.clear();
            location.href = "Login.html";
        },
        requireRoles: function (roles) {
            var s = this.get();
            if (!s || roles.indexOf(s.role) === -1) {
                location.href = "Login.html";
                return null;
            }
            return s;
        },
        avatarKey: function (username) {
            return "absensi_avatar_" + username;
        },
        getAvatarDataUrl: function () {
            var s = this.get();
            if (!s) return null;
            try {
                return localStorage.getItem(this.avatarKey(s.username)) || null;
            } catch (e) {
                return null;
            }
        },
        setAvatarDataUrl: function (dataUrl) {
            var s = this.get();
            if (!s) return;
            try {
                localStorage.setItem(this.avatarKey(s.username), dataUrl);
            } catch (e) {}
        },
        classTitle: function (session) {
            var s = session || this.get() || {};
            var kelas = String(s.kelas || "").trim();
            var jurusan = String(s.jurusan || "").trim().toUpperCase();
            var rombel = String(s.rombel || "").trim();
            return [kelas, jurusan, rombel].filter(Boolean).join(" ");
        }
    };
})(window);
