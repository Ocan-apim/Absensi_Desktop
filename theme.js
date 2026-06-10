(function (global) {
    var KEY = "absensi_theme";
    var THEME_STYLE_ID = "absensi-theme-ui";

    function safeSetTheme(theme) {
        try {
            localStorage.setItem(KEY, theme);
        } catch (e) {}
    }

    function preferredTheme() {
        return "dark";
    }

    function ensureStyle() {
        if (document.getElementById(THEME_STYLE_ID)) return;
        var style = document.createElement("style");
        style.id = THEME_STYLE_ID;
        style.textContent = [
            ".cn-site-header{position:relative;z-index:5;width:100%;height:48px;display:flex;align-items:center;justify-content:space-between;padding:0 1.25rem;border-bottom:1px solid rgba(148,163,184,.18);background:#0d1424;box-shadow:inset 0 1px 0 rgba(255,255,255,.04)}",
            ".cn-header-brand{font:800 1.2rem/1 'Plus Jakarta Sans',system-ui,sans-serif;letter-spacing:-.04em;color:var(--text,#f1f5f9);white-space:nowrap}",
            ".cn-header-brand span{font-family:Georgia,serif;font-size:.8em;letter-spacing:-.08em;margin-left:1px}",
            ".cn-header-actions{display:flex;align-items:center;gap:.65rem;margin-left:auto}",
            ".cn-site-header .avatar-btn{width:38px;height:38px}",
            ".dashboard-topbar{padding:0;border:0;background:transparent;box-shadow:none}",
            ".cn-footer{position:relative;z-index:2;background:radial-gradient(circle at 15% 20%,rgba(99,102,241,.18),transparent 34%),linear-gradient(180deg,#071126 0%,#0d1424 100%);color:#f8fafc;border-top:1px solid rgba(148,163,184,.22);font-family:'Plus Jakarta Sans',system-ui,sans-serif}",
            ".cn-footer-inner{max-width:1180px;margin:0 auto;padding:1.05rem 1.4rem;display:grid;gap:2.2rem}",
            ".cn-footer-full .cn-footer-inner{padding-top:4rem;padding-bottom:3.6rem;grid-template-columns:1.25fr .82fr .9fr 1.4fr;align-items:start}",
            ".cn-footer-compact .cn-footer-inner{grid-template-columns:1fr auto;align-items:center;gap:1.5rem}",
            ".cn-footer-logo{font-size:2rem;font-weight:800;letter-spacing:-.06em;margin-bottom:1rem}",
            ".cn-footer-brand p{max-width:320px;margin:0 0 1rem;color:rgba(248,250,252,.76);font-size:.92rem;line-height:1.75}",
            ".cn-footer-social{display:flex;gap:.7rem;align-items:center;flex-wrap:wrap;color:#fff;margin-top:1.4rem}",
            ".cn-footer-social a{width:42px;height:42px;border-radius:8px;display:inline-grid;place-items:center;color:#f8fafc;text-decoration:none;font-size:.8rem;font-weight:800;background:rgba(99,102,241,.28);border:1px solid rgba(148,163,184,.18);opacity:.95}",
            ".cn-footer-col .cn-footer-social a{display:inline-grid;place-items:center;margin:0;padding:0}",
            ".cn-footer-social svg{width:22px;height:22px;display:block;fill:currentColor}",
            ".cn-footer-social a:hover{opacity:1;background:rgba(99,102,241,.42);text-decoration:none}",
            ".cn-footer-col{min-width:0}",
            ".cn-footer-col strong{display:block;font-size:.9rem;margin-bottom:1.15rem;text-transform:uppercase}",
            ".cn-footer-col a{display:block;color:rgba(248,250,252,.76);text-decoration:none;font-size:.9rem;margin:.86rem 0;opacity:.95}",
            ".cn-footer-col a:hover{opacity:1;color:#f8fafc;text-decoration:none}",
            ".cn-footer-about,.cn-footer-contact{padding-left:2rem;border-left:1px solid rgba(148,163,184,.18);min-height:230px}",
            ".cn-footer-contact{padding-right:1.5rem}",
            ".cn-contact-list{display:grid;gap:1rem;color:rgba(248,250,252,.78);font-size:.9rem;line-height:1.6}",
            ".cn-contact-item{display:grid;grid-template-columns:24px 1fr;gap:.75rem;align-items:start}",
            ".cn-contact-item span:last-child{overflow-wrap:anywhere}",
            ".cn-contact-icon{width:24px;height:24px;border-radius:999px;display:grid;place-items:center;color:#818cf8;font-weight:800}",
            ".cn-contact-icon svg{width:24px;height:24px;display:block;fill:currentColor}",
            ".cn-footer-location{justify-self:end;width:min(100%,340px)}",
            ".cn-location-card{position:relative;display:block;padding:1rem 1.1rem;border:1px solid rgba(148,163,184,.24);border-radius:8px;background:rgba(255,255,255,.06);color:#f8fafc;text-decoration:none;box-shadow:0 16px 28px rgba(2,6,23,.16);overflow:hidden}",
            ".cn-location-card:hover{border-color:rgba(34,211,238,.45);background:rgba(34,211,238,.08);text-decoration:none}",
            ".cn-location-map{display:block;width:100%;aspect-ratio:16/9;border:0;border-radius:6px;background:#0f172a;margin-bottom:.85rem;pointer-events:none}",
            ".cn-location-open{position:absolute;inset:0;z-index:2;border-radius:8px}",
            ".cn-location-open:focus-visible{outline:2px solid #67e8f9;outline-offset:-4px}",
            ".cn-location-card span{display:block;font-size:.76rem;font-weight:800;text-transform:uppercase;letter-spacing:.06em;color:#67e8f9;margin-bottom:.45rem}",
            ".cn-location-card strong{display:block;margin:0 0 .45rem;font-size:.95rem}",
            ".cn-location-card p{margin:0;color:rgba(248,250,252,.76);font-size:.84rem;line-height:1.55}",
            ".cn-footer-bottom{display:flex;align-items:center;justify-content:space-between;gap:1rem;flex-wrap:wrap;border-top:1px solid rgba(148,163,184,.18);padding-top:1rem}",
            ".cn-footer-copy{display:flex;align-items:center;gap:.65rem;min-width:240px}",
            ".cn-footer-badge{width:46px;height:46px;border-radius:999px;background:#34396c;display:grid;place-items:center;flex:0 0 auto}",
            ".cn-footer-badge svg{width:26px;height:26px}",
            ".cn-footer-copy-text{font-size:.75rem;line-height:1.35}",
            ".cn-footer-safe{display:flex;align-items:center;gap:.65rem;color:rgba(248,250,252,.78);font-size:.82rem}",
            ".cn-footer-safe svg{width:18px;height:18px;display:block;fill:none;stroke:#cbd5e1;stroke-width:2}",
            ".cn-footer-full .cn-footer-bottom{grid-column:1/-1;margin-top:.8rem}",
            ".cn-footer-page{display:block!important;align-items:initial!important;justify-content:initial!important;padding:0!important}",
            ".cn-footer-page .login-wrap{margin:4.5rem auto 2rem;padding:0 1.5rem}",
            ".cn-footer-page .glass-card{width:min(100% - 2rem,860px);margin:4.5rem auto 2rem!important}",
            ".theme-toggle,[data-theme-toggle]{display:none!important}",
            ".demo-hint,.demo-accounts,[data-demo-accounts],#demo-hint,#demo-accounts{display:none!important}",
            "@media(max-width:900px){.cn-footer-full .cn-footer-inner{grid-template-columns:1fr 1fr;padding-bottom:3rem}.cn-footer-compact .cn-footer-inner{grid-template-columns:1fr}.cn-footer-about,.cn-footer-contact{padding-left:0;border-left:0;min-height:0}.cn-footer-location{justify-self:start}.cn-footer-bottom{align-items:flex-start}}",
            "@media(max-width:560px){.cn-site-header{height:46px;padding:0 .85rem}.cn-header-brand{font-size:1.05rem}.cn-site-header .avatar-btn{width:34px;height:34px}.cn-footer-full .cn-footer-inner{grid-template-columns:1fr}.cn-footer-copy{min-width:0}.cn-footer-badge{width:48px;height:48px}}"
        ].join("");
        document.head.appendChild(style);
    }

    function shieldSvg() {
        return '<svg viewBox="0 0 48 48" fill="none" stroke="currentColor" stroke-width="3" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true"><path d="M24 5 39 11v11c0 10-6.4 17.5-15 21-8.6-3.5-15-11-15-21V11L24 5Z"/><path d="m17 24 5 5 10-11"/></svg>';
    }

    function footerMarkup(full) {
        var mapsUrl = "https://www.google.com/maps/search/?api=1&query=SMK%20Citra%20Negara%20Jl.%20Raya%20Tanah%20Baru%20No.99%20Kemiri%20Jaya%20Beji%20Depok";
        var embedUrl = "https://www.google.com/maps?q=SMK%20Citra%20Negara%20Jl.%20Raya%20Tanah%20Baru%20No.99%20Kemiri%20Jaya%20Beji%20Depok&output=embed";
        var iconInstagram = '<svg viewBox="0 0 24 24" aria-hidden="true"><path d="M7 2h10a5 5 0 0 1 5 5v10a5 5 0 0 1-5 5H7a5 5 0 0 1-5-5V7a5 5 0 0 1 5-5Zm0 2a3 3 0 0 0-3 3v10a3 3 0 0 0 3 3h10a3 3 0 0 0 3-3V7a3 3 0 0 0-3-3H7Zm5 4a4 4 0 1 1 0 8 4 4 0 0 1 0-8Zm0 2a2 2 0 1 0 0 4 2 2 0 0 0 0-4Zm5.25-3.25a1 1 0 1 1 0 2 1 1 0 0 1 0-2Z"/></svg>';
        var iconYoutube = '<svg viewBox="0 0 24 24" aria-hidden="true"><path d="M21.6 7.2s-.2-1.5-.8-2.1c-.8-.8-1.7-.8-2.1-.9C15.8 4 12 4 12 4s-3.8 0-6.7.2c-.4.1-1.3.1-2.1.9-.6.6-.8 2.1-.8 2.1S2 9 2 10.9v1.8c0 1.9.4 3.7.4 3.7s.2 1.5.8 2.1c.8.8 1.9.8 2.4.9 1.7.2 6.4.2 6.4.2s3.8 0 6.7-.3c.4 0 1.3-.1 2.1-.9.6-.6.8-2.1.8-2.1s.4-1.8.4-3.7v-1.8c0-1.8-.4-3.6-.4-3.6ZM10 14.8V8.6l5.8 3.1L10 14.8Z"/></svg>';
        var iconLocation = '<svg viewBox="0 0 24 24" aria-hidden="true"><path d="M12 2a7 7 0 0 0-7 7c0 5.25 7 13 7 13s7-7.75 7-13a7 7 0 0 0-7-7Zm0 9.5A2.5 2.5 0 1 1 12 6a2.5 2.5 0 0 1 0 5.5Z"/></svg>';
        var iconPhone = '<svg viewBox="0 0 24 24" aria-hidden="true"><path d="M6.62 10.79a15.1 15.1 0 0 0 6.59 6.59l2.2-2.2a1 1 0 0 1 1.01-.24 11.4 11.4 0 0 0 3.58.57 1 1 0 0 1 1 1V20a1 1 0 0 1-1 1A17 17 0 0 1 3 4a1 1 0 0 1 1-1h3.5a1 1 0 0 1 1 1 11.4 11.4 0 0 0 .57 3.58 1 1 0 0 1-.25 1.01l-2.2 2.2Z"/></svg>';
        var iconEmail = '<svg viewBox="0 0 24 24" aria-hidden="true"><path d="M20 4H4a2 2 0 0 0-2 2v12a2 2 0 0 0 2 2h16a2 2 0 0 0 2-2V6a2 2 0 0 0-2-2Zm0 4-8 5-8-5V6l8 5 8-5v2Z"/></svg>';
        var iconClock = '<svg viewBox="0 0 24 24" aria-hidden="true"><path d="M12 2a10 10 0 1 0 .01 0H12Zm1 10.25 4 2.38-1 1.64-5-3V7h2v5.25Z"/></svg>';
        var iconLock = '<svg viewBox="0 0 24 24" aria-hidden="true"><rect x="5" y="10" width="14" height="11" rx="2"/><path d="M8 10V7a4 4 0 0 1 8 0v3"/><path d="M12 15v2"/></svg>';
        var cols = full ? [
            '<div class="cn-footer-col cn-footer-brand"><div class="cn-footer-logo">CNAJA</div><p>Sistem absensi siswa dan portal kehadiran sekolah SMK Citra Negara.</p><p>Terintegrasi, akurat, dan mudah digunakan untuk mendukung kedisiplinan dan transparansi.</p><div class="cn-footer-social"><a href="https://www.instagram.com/smkcitranegaradepok/" aria-label="Instagram">' + iconInstagram + '</a><a href="https://www.youtube.com/@citranegaratv9070" aria-label="YouTube">' + iconYoutube + '</a></div></div>',
            '<div class="cn-footer-col cn-footer-about"><strong>Tentang Kami</strong><a href="companyprofile.html#tentang">Profil Sekolah</a><a href="companyprofile.html#visi-misi">Visi & Misi</a><a href="companyprofile.html#berita-kegiatan">Berita & Kegiatan</a><a href="companyprofile.html#kontak">Hubungi Kami</a></div>',
            '<div class="cn-footer-col cn-footer-contact"><strong>Kontak Sekolah</strong><div class="cn-contact-list"><div class="cn-contact-item"><span class="cn-contact-icon">' + iconLocation + '</span><span>Jl. Raya Tanah Baru No.99,<br>Kemiri Jaya, Beji,<br>Depok 16421</span></div><div class="cn-contact-item"><span class="cn-contact-icon">' + iconPhone + '</span><span>(021) 77201052</span></div><div class="cn-contact-item"><span class="cn-contact-icon">' + iconEmail + '</span><span>info@citranegara.sch.id</span></div><div class="cn-contact-item"><span class="cn-contact-icon">' + iconClock + '</span><span>Senin - Jumat<br>07.00 - 15.00 WIB</span></div></div></div>',
            '<div class="cn-footer-col cn-footer-location"><div class="cn-location-card"><iframe class="cn-location-map" src="' + embedUrl + '" loading="lazy" referrerpolicy="no-referrer-when-downgrade" tabindex="-1" aria-label="Preview lokasi SMK Citra Negara"></iframe><span>Lokasi</span><strong>SMK Citra Negara</strong><p>Jl. Raya Tanah Baru No.99, Kemiri Jaya, Beji, Depok 16421</p><a class="cn-location-open" href="' + mapsUrl + '" target="_blank" rel="noopener" aria-label="Buka lokasi SMK Citra Negara di Google Maps"></a></div></div>'
        ].join("") : "";
        var bottom = '<div class="cn-footer-bottom"><div class="cn-footer-copy"><div class="cn-footer-badge">' + shieldSvg() + '</div><div class="cn-footer-copy-text">&copy; 2026 CNAJA &mdash; All rights reserved.<br>Sistem Absensi SMK Citra Negara</div></div><div class="cn-footer-safe">' + iconLock + '<span>Data kehadiran Anda aman bersama kami.</span></div></div>';
        return '<div class="cn-footer-inner">' + cols + bottom + '</div>';
    }

    function ensureHeaderBrand() {
        var topbar = document.querySelector(".dashboard-topbar");
        if (!topbar || document.querySelector(".cn-site-header")) return;

        var appShell = document.querySelector(".app-shell");
        var header = document.createElement("header");
        var actions = document.createElement("div");
        var brand = document.createElement("div");

        header.className = "cn-site-header";
        actions.className = "cn-header-actions";
        brand.className = "cn-header-brand";
        brand.innerHTML = "CN<span>AJA</span>";
        header.appendChild(brand);
        header.appendChild(actions);

        if (appShell && appShell.parentNode) {
            appShell.parentNode.insertBefore(header, appShell);
        } else {
            document.body.insertBefore(header, document.body.firstChild);
        }

        var avatar = topbar.querySelector(".avatar-btn");
        if (avatar) actions.appendChild(avatar);
    }

    function currentDashboardView() {
        var raw = (global.location.hash || "").replace(/^#/, "").toLowerCase().trim();
        if (!raw) {
            if (document.getElementById("view-beranda")) return "beranda";
            if (document.getElementById("view-hadir")) return "hadir";
        }
        return raw;
    }

    function isFullFooterView(view) {
        if (document.body && !document.body.classList.contains("dashboard-body")) return true;
        return view === "beranda" || view === "hadir" || view === "pulang";
    }

    function ensureFooter() {
        if (document.querySelector(".cn-footer")) return;

        var path = (global.location && global.location.pathname ? global.location.pathname : "").toLowerCase();
        var isLandingOrLogin = path.indexOf("companyprofile.html") !== -1 || path.indexOf("login.html") !== -1 || path.indexOf("register.html") !== -1 || /\/$/.test(path);
        var isDashboard = document.body && document.body.classList.contains("dashboard-body");
        if (!isDashboard && !isLandingOrLogin) return;

        if (document.body && !isDashboard) {
            document.body.classList.add("cn-footer-page");
        }

        var footer = document.createElement("footer");
        footer.className = "cn-footer";
        footer.setAttribute("data-cn-footer", "true");
        document.body.appendChild(footer);
    }

    function syncFooter() {
        var footer = document.querySelector(".cn-footer");
        if (!footer) return;
        var full = isFullFooterView(currentDashboardView());
        footer.classList.toggle("cn-footer-full", full);
        footer.classList.toggle("cn-footer-compact", !full);
        footer.innerHTML = footerMarkup(full);
    }

    function removeToggleButtons() {
        document.querySelectorAll("[data-theme-toggle]").forEach(function (btn) {
            btn.remove();
        });
    }

    function bindSidebarDropdowns() {
        var path = (global.location && global.location.pathname ? global.location.pathname : "").toLowerCase();
        if (path.indexOf("-profil.html") === -1 && path.indexOf("profile.html") === -1) return;
        document.querySelectorAll(".nav-group-toggle").forEach(function (btn) {
            if (btn.__cnNavBound) return;
            btn.__cnNavBound = true;
            btn.addEventListener("click", function () {
                var group = btn.closest(".nav-group");
                if (!group) return;
                var open = !group.classList.contains("is-open");
                group.classList.toggle("is-open", open);
                btn.setAttribute("aria-expanded", open ? "true" : "false");
                var defaultView = btn.getAttribute("data-nav-default");
                if (defaultView) {
                    var target = group.querySelector('.nav-submenu a[href*="#' + defaultView + '"]') || group.querySelector(".nav-submenu a[href]");
                    if (target && target.getAttribute("href")) {
                        global.location.href = target.getAttribute("href");
                    }
                }
            });
        });
    }

    function resetTeacherProfileSidebar() {
        var path = (global.location && global.location.pathname ? global.location.pathname : "").toLowerCase();
        if (path.indexOf("-profil.html") === -1 && path.indexOf("profile.html") === -1) return;
        if (document.body) document.body.setAttribute("data-profile-sidebar", "closed");
        document.querySelectorAll(".nav-group").forEach(function (group) {
            group.classList.remove("is-open");
        });
        document.querySelectorAll(".nav-group-toggle").forEach(function (btn) {
            btn.setAttribute("aria-expanded", "false");
        });
    }

    function bindLandingLogout() {
        document.querySelectorAll("#btn-logout, .btn-logout").forEach(function (btn) {
            if (btn.__cnLandingLogoutBound) return;
            btn.__cnLandingLogoutBound = true;
            btn.addEventListener("click", function (event) {
                event.preventDefault();
                event.stopImmediatePropagation();
                try {
                    if (global.AbsensiAuth && typeof global.AbsensiAuth.clear === "function") {
                        global.AbsensiAuth.clear();
                    } else {
                        localStorage.removeItem("absensi_session");
                    }
                    localStorage.removeItem("absensi_session");
                    localStorage.removeItem("absensi_auth");
                    localStorage.removeItem("absensi_user");
                } catch (e) {}
                global.location.href = "companyprofile.html";
            }, true);
        });
    }

    function apply(theme) {
        var next = "dark";
        document.documentElement.setAttribute("data-theme", next);
        if (document.body) document.body.setAttribute("data-theme", next);
        safeSetTheme(next);
        return next;
    }

    function init() {
        ensureStyle();
        ensureHeaderBrand();
        apply(preferredTheme());
        ensureFooter();
        syncFooter();
        removeToggleButtons();
        bindSidebarDropdowns();
        resetTeacherProfileSidebar();
        bindLandingLogout();
        global.addEventListener("hashchange", syncFooter);
        document.addEventListener("click", function (event) {
            if (event.target.closest("[data-placeholder-link]")) {
                event.preventDefault();
            }
        });
    }

    function toggle() {
        return apply("dark");
    }

    global.AbsensiTheme = {
        get: function () {
            return document.documentElement.getAttribute("data-theme") || preferredTheme();
        },
        set: apply,
        toggle: toggle,
        syncFooter: syncFooter
    };

    if (document.readyState === "loading") {
        document.addEventListener("DOMContentLoaded", init);
    } else {
        init();
    }
})(window);
