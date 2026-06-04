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
            ".cn-footer{position:relative;z-index:2;background:#0d1424;color:#f8fafc;border-top:4px solid rgba(100,116,139,.32);font-family:'Plus Jakarta Sans',system-ui,sans-serif}",
            ".cn-footer-inner{max-width:1180px;margin:0 auto;padding:1.05rem 1.4rem;display:grid;gap:2.6rem}",
            ".cn-footer-full .cn-footer-inner{padding-top:1.5rem;padding-bottom:4rem;grid-template-columns:1fr 1fr 1fr;align-items:start}",
            ".cn-footer-compact .cn-footer-inner{grid-template-columns:1fr auto;align-items:center;gap:1.5rem}",
            ".cn-footer-logo{font-size:2rem;font-weight:800;letter-spacing:-.08em;margin-bottom:.85rem}",
            ".cn-footer-social{display:flex;gap:.7rem;align-items:center;flex-wrap:wrap;color:#fff}",
            ".cn-footer-social a{display:inline-flex;align-items:center;gap:.35rem;color:#f8fafc;text-decoration:none;font-size:.86rem;font-weight:700;opacity:.9}",
            ".cn-footer-social a:hover{opacity:1;text-decoration:underline}",
            ".cn-footer-col strong{display:block;font-size:.82rem;margin-bottom:1.25rem}",
            ".cn-footer-col a{display:block;color:#f8fafc;text-decoration:none;font-size:.86rem;margin:.72rem 0;opacity:.9}",
            ".cn-footer-col a:hover{opacity:1;text-decoration:underline}",
            ".cn-footer-school{text-align:center;max-width:320px;justify-self:center}",
            ".cn-footer-school strong{font-size:1rem;margin-bottom:.65rem}",
            ".cn-footer-school p{margin:0;color:rgba(248,250,252,.72);font-size:.88rem;line-height:1.6}",
            ".cn-footer-location{justify-self:end;width:min(100%,340px)}",
            ".cn-location-card{display:block;padding:1rem 1.1rem;border:1px solid rgba(148,163,184,.24);border-radius:8px;background:rgba(255,255,255,.06);color:#f8fafc;text-decoration:none;box-shadow:0 16px 28px rgba(2,6,23,.16)}",
            ".cn-location-card:hover{border-color:rgba(34,211,238,.45);background:rgba(34,211,238,.08);text-decoration:none}",
            ".cn-location-card span{display:block;font-size:.76rem;font-weight:800;text-transform:uppercase;letter-spacing:.06em;color:#67e8f9;margin-bottom:.45rem}",
            ".cn-location-card strong{display:block;margin:0 0 .45rem;font-size:.95rem}",
            ".cn-location-card p{margin:0;color:rgba(248,250,252,.76);font-size:.84rem;line-height:1.55}",
            ".cn-footer-bottom{display:flex;align-items:center;justify-content:space-between;gap:1.2rem;flex-wrap:wrap}",
            ".cn-footer-copy{display:flex;align-items:center;gap:.75rem;min-width:260px}",
            ".cn-footer-badge{width:68px;height:68px;border-radius:999px;background:#34396c;display:grid;place-items:center;flex:0 0 auto}",
            ".cn-footer-badge svg{width:38px;height:38px}",
            ".cn-footer-copy-text{font-size:.78rem;line-height:1.45}",
            ".cn-footer-full .cn-footer-bottom{grid-column:1/-1;margin-top:1.5rem}",
            ".cn-footer-page{display:block!important;align-items:initial!important;justify-content:initial!important;padding:0!important}",
            ".cn-footer-page .login-wrap{margin:4.5rem auto 2rem;padding:0 1.5rem}",
            ".theme-toggle,[data-theme-toggle]{display:none!important}",
            "@media(max-width:900px){.cn-footer-full .cn-footer-inner{grid-template-columns:1fr 1fr;padding-bottom:3rem}.cn-footer-compact .cn-footer-inner{grid-template-columns:1fr}.cn-footer-school{text-align:left;justify-self:start}.cn-footer-location{justify-self:start}.cn-footer-bottom{align-items:flex-start}}",
            "@media(max-width:560px){.cn-site-header{height:46px;padding:0 .85rem}.cn-header-brand{font-size:1.05rem}.cn-site-header .avatar-btn{width:34px;height:34px}.cn-footer-full .cn-footer-inner{grid-template-columns:1fr}.cn-footer-copy{min-width:0}.cn-footer-badge{width:48px;height:48px}}"
        ].join("");
        document.head.appendChild(style);
    }

    function shieldSvg() {
        return '<svg viewBox="0 0 48 48" fill="none" stroke="currentColor" stroke-width="3" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true"><path d="M24 5 39 11v11c0 10-6.4 17.5-15 21-8.6-3.5-15-11-15-21V11L24 5Z"/><path d="m17 24 5 5 10-11"/></svg>';
    }

    function footerMarkup(full) {
        var mapsUrl = "https://www.google.com/maps/search/?api=1&query=SMK%20Citra%20Negara%20Jl.%20Raya%20Tanah%20Baru%20No.99%20Kemiri%20Jaya%20Beji%20Depok";
        var cols = full ? [
            '<div class="cn-footer-col cn-footer-brand"><div class="cn-footer-logo">CNAJA</div><div class="cn-footer-social"><a href="https://www.instagram.com/smkcitranegaradepok/">Instagram</a><a href="https://www.youtube.com/@citranegaratv9070">YouTube</a></div></div>',
            '<div class="cn-footer-col cn-footer-school"><strong>SMK Citra Negara</strong><p>Sistem absensi siswa dan portal kehadiran sekolah.</p></div>',
            '<div class="cn-footer-col cn-footer-location"><a class="cn-location-card" href="' + mapsUrl + '" target="_blank" rel="noopener"><span>Lokasi</span><strong>SMK Citra Negara</strong><p>Jl. Raya Tanah Baru No.99, Kemiri Jaya, Beji, Depok 16421</p></a></div>'
        ].join("") : "";
        var bottom = '<div class="cn-footer-bottom"><div class="cn-footer-copy"><div class="cn-footer-badge">' + shieldSvg() + '</div><div class="cn-footer-copy-text">&copy; 2026 CNAJA &mdash; All rights reserved.<br>Sistem Absensi</div></div></div>';
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
