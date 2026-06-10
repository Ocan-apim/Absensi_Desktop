(function () {
    function $(id) {
        return document.getElementById(id);
    }

    function parseApiJson(res) {
        return res.text().then(function (text) {
            var data = text ? JSON.parse(text) : {};
            if (!res.ok || data.error) throw new Error(data.error || "Request gagal.");
            return data;
        });
    }

    function initials(value, fallback) {
        return String(value || fallback || "US").slice(0, 2).toUpperCase();
    }

    function applyProfileToSession(profile) {
        return AbsensiAuth.update({
            id: profile.id,
            username: profile.username || profile.identifier,
            name: profile.name || profile.nama_lengkap,
            fullName: profile.fullName || profile.nama_lengkap,
            displayName: profile.name || profile.nama_lengkap,
            email: profile.email,
            password: profile.password,
            role: profile.role,
            tableRole: profile.tableRole,
            kelas: profile.kelas,
            jurusan: profile.jurusan,
            rombel: profile.rombel
        });
    }

    function loadAvatar(session, fallback) {
        var url = AbsensiAuth.getAvatarDataUrl();
        var img = $("profil-avatar-img");
        var ph = $("profil-avatar-ph");
        if (!img || !ph) return;
        if (url) {
            img.src = url;
            img.classList.remove("hidden");
            ph.classList.add("hidden");
        } else {
            ph.textContent = initials(session.fullName || session.name || session.username, fallback);
        }
    }

    function setForm(profile) {
        $("profil-nama-lengkap").value = profile.nama_lengkap || profile.fullName || "";
        $("profil-identifier").value = profile.identifier || profile.username || "";
        $("profil-email").value = profile.email || "";
        $("profil-password").value = profile.password || "";
        var label = $("profil-identifier-label");
        if (label) label.textContent = profile.identifierLabel || "NIS/NPSN";
    }

    window.AbsensiProfileForm = {
        init: function (config) {
            var session = AbsensiAuth.requireRoles(config.roles);
            if (!session) return;

            $("btn-logout").addEventListener("click", function () {
                AbsensiAuth.logout();
            });

            DashboardAvatar.bind(config.profileHref);
            loadAvatar(session, config.fallbackInitials);

            var params = new URLSearchParams({
                role: config.role,
                username: session.username || session.email || ""
            });

            fetch("profile_api.php?" + params.toString())
                .then(parseApiJson)
                .then(function (data) {
                    if (!data.profile) return;
                    session = applyProfileToSession(data.profile);
                    setForm(data.profile);
                    loadAvatar(session, config.fallbackInitials);
                    if (window.DashboardAvatar && DashboardAvatar.refresh) DashboardAvatar.refresh();
                })
                .catch(function (err) {
                    $("profil-msg").textContent = err.message || "Gagal memuat profil.";
                    $("profil-msg").classList.remove("hidden");
                });

            $("profil-foto-input").addEventListener("change", function () {
                var f = this.files && this.files[0];
                if (!f) return;
                var reader = new FileReader();
                reader.onload = function () {
                    var img = $("profil-avatar-img");
                    img.src = reader.result;
                    img.classList.remove("hidden");
                    $("profil-avatar-ph").classList.add("hidden");
                };
                reader.readAsDataURL(f);
            });

            $("profil-form").addEventListener("submit", function (event) {
                event.preventDefault();
                var img = $("profil-avatar-img");
                var src = img ? img.getAttribute("src") : "";
                if (src && src.indexOf("data:") === 0) {
                    AbsensiAuth.setAvatarDataUrl(src);
                }

                var formData = new FormData();
                formData.append("role", config.role);
                formData.append("username", session.username || session.email || "");
                formData.append("nama_lengkap", $("profil-nama-lengkap").value.trim());
                formData.append("identifier", $("profil-identifier").value.trim());
                formData.append("email", $("profil-email").value.trim());
                formData.append("password", $("profil-password").value);

                fetch("profile_api.php", { method: "POST", body: formData })
                    .then(parseApiJson)
                    .then(function (data) {
                        if (data.profile) {
                            session = applyProfileToSession(data.profile);
                            setForm(data.profile);
                            if (src && src.indexOf("data:") === 0) {
                                AbsensiAuth.setAvatarDataUrl(src);
                            }
                        }
                        var msg = $("profil-msg");
                        msg.textContent = "Tersimpan.";
                        msg.classList.remove("hidden");
                        setTimeout(function () { msg.classList.add("hidden"); }, 2200);
                        if (window.DashboardAvatar && DashboardAvatar.refresh) DashboardAvatar.refresh();
                    })
                    .catch(function (err) {
                        alert(err.message || "Gagal menyimpan profil.");
                    });
            });
        }
    };
})();
