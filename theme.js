(function (global) {
    var KEY = "absensi_theme";
    var THEME_STYLE_ID = "absensi-theme-ui";

    function safeGetTheme() {
        try {
            return localStorage.getItem(KEY) || "";
        } catch (e) {
            return "";
        }
    }

    function safeSetTheme(theme) {
        try {
            localStorage.setItem(KEY, theme);
        } catch (e) {}
    }

    function preferredTheme() {
        var stored = safeGetTheme();
        if (stored === "light" || stored === "dark") return stored;
        try {
            if (global.matchMedia && global.matchMedia("(prefers-color-scheme: light)").matches) return "light";
        } catch (e) {}
        return "dark";
    }

    function iconSvg(kind) {
        if (kind === "light") {
            return '<svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true"><circle cx="12" cy="12" r="4"/><path d="M12 2v2"/><path d="M12 20v2"/><path d="m4.93 4.93 1.41 1.41"/><path d="m17.66 17.66 1.41 1.41"/><path d="M2 12h2"/><path d="M20 12h2"/><path d="m6.34 17.66-1.41 1.41"/><path d="m19.07 4.93-1.41 1.41"/></svg>';
        }
        return '<svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true"><path d="M21 12.8A9 9 0 1 1 11.2 3 7 7 0 0 0 21 12.8z"/></svg>';
    }

    function ensureStyle() {
        if (document.getElementById(THEME_STYLE_ID)) return;
        var style = document.createElement("style");
        style.id = THEME_STYLE_ID;
        style.textContent = [
            ".theme-toggle{position:fixed;top:1rem;right:1rem;z-index:60;display:inline-flex;align-items:center;gap:.5rem;padding:.7rem .9rem;border-radius:999px;border:1px solid var(--border, rgba(148,163,184,.16));background:rgba(7,11,20,.82);color:var(--text, #f1f5f9);font:600 .85rem/1 inherit;cursor:pointer;box-shadow:0 10px 26px rgba(0,0,0,.22);backdrop-filter:blur(14px)}",
            ".theme-toggle.theme-toggle--inline{position:static;top:auto;right:auto;z-index:auto;margin-right:.75rem}",
            ".theme-toggle:hover{border-color:rgba(34,211,238,.45)}",
            ".theme-toggle svg{width:18px;height:18px;flex:0 0 auto}",
            ".theme-toggle .theme-toggle-sun,.theme-toggle .theme-toggle-moon{display:none}",
            "html[data-theme='dark'] .theme-toggle .theme-toggle-moon{display:block}",
            "html[data-theme='light'] .theme-toggle .theme-toggle-sun{display:block}",
            ".theme-toggle .theme-toggle-label{white-space:nowrap}",
            "html[data-theme='light'] .theme-toggle{background:rgba(255,255,255,.82);color:#102133}",
            ".cn-site-header{position:relative;z-index:5;width:100%;height:48px;display:flex;align-items:center;justify-content:space-between;padding:0 1.25rem;border-bottom:1px solid rgba(148,163,184,.18);background:#0d1424;box-shadow:inset 0 1px 0 rgba(255,255,255,.04)}",
            "html[data-theme='light'] .cn-site-header{background:rgba(255,255,255,.92)}",
            ".cn-header-brand{font:800 1.2rem/1 'Plus Jakarta Sans',system-ui,sans-serif;letter-spacing:-.04em;color:var(--text,#f1f5f9);white-space:nowrap}",
            ".cn-header-brand span{font-family:Georgia,serif;font-size:.8em;letter-spacing:-.08em;margin-left:1px}",
            ".cn-header-actions{display:flex;align-items:center;gap:.65rem;margin-left:auto}",
            ".cn-site-header .theme-toggle{position:static;top:auto;right:auto;z-index:auto;margin:0;width:38px;height:38px;padding:0;border-radius:999px;justify-content:center}",
            ".cn-site-header .theme-toggle .theme-toggle-label{display:none}",
            ".cn-site-header .avatar-btn{width:38px;height:38px}",
            ".dashboard-topbar{padding:0;border:0;background:transparent;box-shadow:none}",
            ".cn-footer{position:relative;z-index:2;background:#0d1424;color:#f8fafc;border-top:4px solid rgba(100,116,139,.32);font-family:'Plus Jakarta Sans',system-ui,sans-serif}",
            "html[data-theme='light'] .cn-footer{background:#111827;color:#f8fafc}",
            ".cn-footer-inner{max-width:1180px;margin:0 auto;padding:1.05rem 1.4rem;display:grid;gap:2.6rem}",
            ".cn-footer-full .cn-footer-inner{padding-top:1.5rem;padding-bottom:6.5rem;grid-template-columns:1fr 1fr 1fr 1.55fr;align-items:start}",
            ".cn-footer-compact .cn-footer-inner{grid-template-columns:1fr auto;align-items:center;gap:1.5rem}",
            ".cn-footer-logo{font-size:2rem;font-weight:800;letter-spacing:-.08em;margin-bottom:.85rem}",
            ".cn-footer-social{display:flex;gap:.7rem;align-items:center;flex-wrap:wrap;color:#fff}",
            ".cn-footer-social a{display:inline-flex;align-items:center;gap:.35rem;color:#fff;text-decoration:none;font-size:.86rem;font-weight:700}",
            ".cn-footer-col strong{display:block;font-size:.82rem;margin-bottom:1.25rem}",
            ".cn-footer-col a{display:block;color:#f8fafc;text-decoration:none;font-size:.86rem;margin:.72rem 0;opacity:.9}",
            ".cn-footer-col a:hover{opacity:1;text-decoration:underline}",
            ".cn-footer-bottom{display:flex;align-items:center;justify-content:space-between;gap:1.2rem;flex-wrap:wrap}",
            ".cn-footer-copy{display:flex;align-items:center;gap:.75rem;min-width:260px}",
            ".cn-footer-badge{width:68px;height:68px;border-radius:999px;background:#34396c;display:grid;place-items:center;flex:0 0 auto}",
            ".cn-footer-badge svg{width:38px;height:38px}",
            ".cn-footer-copy-text{font-size:.78rem;line-height:1.45}",
            ".cn-footer-links{display:flex;gap:.8rem;align-items:center;flex-wrap:wrap;font-size:.78rem}",
            ".cn-footer-links a{display:inline-flex;align-items:center;gap:.45rem;color:#f8fafc;text-decoration:none;white-space:nowrap}",
            ".cn-footer-links a:hover{text-decoration:underline}",
            ".cn-footer-links i{font-style:normal;font-size:1.35rem;line-height:1}",
            ".cn-footer-sep{width:1px;height:22px;background:rgba(248,250,252,.45)}",
            ".cn-footer-full .cn-footer-bottom{grid-column:1/-1;margin-top:2.4rem}",
            ".cn-footer-page{display:block!important;align-items:initial!important;justify-content:initial!important;padding:0!important}",
            ".cn-footer-page .login-wrap{margin:4.5rem auto 2rem;padding:0 1.5rem}",
            "@media(max-width:900px){.cn-footer-full .cn-footer-inner{grid-template-columns:1fr 1fr;padding-bottom:3rem}.cn-footer-compact .cn-footer-inner{grid-template-columns:1fr}.cn-footer-bottom{align-items:flex-start}.cn-footer-links{gap:.75rem}.cn-footer-sep{display:none}}",
            "@media(max-width:560px){.cn-site-header{height:46px;padding:0 .85rem}.cn-header-brand{font-size:1.05rem}.cn-site-header .theme-toggle,.cn-site-header .avatar-btn{width:34px;height:34px}.cn-footer-full .cn-footer-inner{grid-template-columns:1fr}.cn-footer-copy{min-width:0}.cn-footer-badge{width:48px;height:48px}.cn-footer-links{font-size:.76rem}}"
        ].join("");
        document.head.appendChild(style);
    }

    function shieldSvg() {
        return '<svg viewBox="0 0 48 48" fill="none" stroke="currentColor" stroke-width="3" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true"><path d="M24 5 39 11v11c0 10-6.4 17.5-15 21-8.6-3.5-15-11-15-21V11L24 5Z"/><path d="m17 24 5 5 10-11"/></svg>';
    }

    function footerMarkup(full) {
        var cols = full ? [
            '<div class="cn-footer-col cn-footer-brand"><div class="cn-footer-logo">CNwork</div><div class="cn-footer-social"><a href="https://www.instagram.com/smkcitranegaradepok/">◎ Instagram</a><a href="https://www.youtube.com/@citranegaratv9070">▶ YouTube</a></div></div>',
            '<div class="cn-footer-col"><strong>Sistem Absensi</strong><a href="user-dashboard.html#hadir">Absensi hadir</a><a href="user-dashboard.html#pulang">Absensi pulang</a><a href="user-dashboard.html#riwayat">Riwayat siswa</a><a href="teacher-dashboard.html#rekap">Rekap wali kelas</a><a href="bk-dashboard.html#laporan">Laporan BK</a></div>',
            '<div class="cn-footer-col"><strong>Portal Pengguna</strong><a href="user-dashboard.html">Dashboard siswa</a><a href="teacher-dashboard.html">Dashboard guru/walas</a><a href="bk-dashboard.html">Dashboard BK</a><a href="admin-dashboard.html">Dashboard admin</a><a href="Login.html">Login akun</a></div>',
            '<div class="cn-footer-col"><strong>SMK Citra Negara</strong><a href="companyprofile.html">Company profile</a><a href="https://www.instagram.com/smkcitranegaradepok/">Instagram sekolah</a><a href="https://www.youtube.com/@citranegaratv9070">Citra Negara TV</a><a href="#" data-placeholder-link="true">Kebijakan data</a><a href="#" data-placeholder-link="true">Bantuan pengguna</a></div>'
        ].join("") : "";
        var bottom = '<div class="cn-footer-bottom"><div class="cn-footer-copy"><div class="cn-footer-badge">' + shieldSvg() + '</div><div class="cn-footer-copy-text">© 2026 CNWORK — All rights reserved.<br>Sistem Absensi</div></div><div class="cn-footer-links"><a href="#" data-placeholder-link="true"><i>▣</i>Kebijakan Privasi</a><span class="cn-footer-sep"></span><a href="#" data-placeholder-link="true"><i>▤</i>Syarat & Ketentuan</a><span class="cn-footer-sep"></span><a href="#" data-placeholder-link="true"><i>?</i>Bantuan</a></div></div>';
        return '<div class="cn-footer-inner">' + cols + bottom + '</div>';
    }

    function ensureHeaderBrand() {
        var topbar = document.querySelector(".dashboard-topbar");
        if (!topbar || document.querySelector(".cn-site-header")) return;
        var appShell = document.querySelector(".app-shell");
        var header = document.createElement("header");
        header.className = "cn-site-header";
        var actions = document.createElement("div");
        actions.className = "cn-header-actions";
        var brand = document.createElement("div");
        brand.className = "cn-header-brand";
        brand.innerHTML = "CN<span>work</span>";
        header.appendChild(brand);
        header.appendChild(actions);
        if (appShell && appShell.parentNode) appShell.parentNode.insertBefore(header, appShell);
        else document.body.insertBefore(header, document.body.firstChild);
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
        var isLandingOrLogin = path.indexOf("companyprofile.html") !== -1 || path.indexOf("login.html") !== -1 || /\/$/.test(path);
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

    function buildToggleButton() {
        var btn = document.createElement("button");
        btn.type = "button";
        btn.className = "theme-toggle";
        btn.setAttribute("data-theme-toggle", "true");
        btn.innerHTML = '<span class="theme-toggle-sun">' + iconSvg("light") + '</span><span class="theme-toggle-moon">' + iconSvg("dark") + '</span><span class="theme-toggle-label">Tema</span>';
        return btn;
    }

    function ensureButtonMarkup(btn) {
        if (!btn.querySelector(".theme-toggle-label")) {
            btn.innerHTML = '<span class="theme-toggle-sun">' + iconSvg("light") + '</span><span class="theme-toggle-moon">' + iconSvg("dark") + '</span><span class="theme-toggle-label">Tema</span>';
        }
    }

    function syncButtons(theme) {
        document.querySelectorAll("[data-theme-toggle]").forEach(function (btn) {
            btn.setAttribute("aria-label", theme === "dark" ? "Beralih ke tema terang" : "Beralih ke tema gelap");
            btn.title = theme === "dark" ? "Beralih ke tema terang" : "Beralih ke tema gelap";
        });
    }

    function apply(theme) {
        var next = theme === "light" ? "light" : "dark";
        document.documentElement.setAttribute("data-theme", next);
        if (document.body) document.body.setAttribute("data-theme", next);
        safeSetTheme(next);
        syncButtons(next);
        return next;
    }

    function ensureToggleButton() {
        var existing = document.querySelector("[data-theme-toggle]");
        var headerActions = document.querySelector(".cn-header-actions");
        if (existing) {
            if (headerActions && !headerActions.contains(existing)) {
                headerActions.insertBefore(existing, headerActions.firstChild);
                existing.classList.remove("theme-toggle--inline");
            }
            return existing;
        }
        var btn = buildToggleButton();
        if (headerActions) {
            headerActions.insertBefore(btn, headerActions.firstChild);
        } else {
            var topbar = document.querySelector(".dashboard-topbar");
            if (topbar) {
                btn.classList.add("theme-toggle--inline");
                var avatar = topbar.querySelector(".avatar-btn");
                if (avatar && avatar.parentNode === topbar) topbar.insertBefore(btn, avatar);
                else topbar.appendChild(btn);
            } else {
                document.body.appendChild(btn);
            }
        }
        var existingInline = document.querySelector(".dashboard-topbar [data-theme-toggle]");
        var headerActionsAfter = document.querySelector(".cn-header-actions");
        if (existingInline && headerActionsAfter) {
            headerActionsAfter.insertBefore(existingInline, headerActionsAfter.firstChild);
            return existingInline;
        } else {
            return btn;
        }
    }

    function bindToggle(btn) {
        if (!btn || btn.__themeBound) return;
        ensureButtonMarkup(btn);
        btn.__themeBound = true;
        btn.addEventListener("click", function () {
            toggle();
        });
    }

    function init() {
        ensureStyle();
        ensureHeaderBrand();
        var theme = apply(preferredTheme());
        ensureFooter();
        syncFooter();
        var injected = ensureToggleButton();
        bindToggle(injected);
        document.querySelectorAll("[data-theme-toggle]").forEach(bindToggle);
        syncButtons(theme);
        global.addEventListener("hashchange", syncFooter);
        document.addEventListener("click", function (event) {
            if (event.target.closest("[data-placeholder-link]")) {
                event.preventDefault();
            }
        });
    }

    function toggle() {
        var current = document.documentElement.getAttribute("data-theme") || preferredTheme();
        return apply(current === "dark" ? "light" : "dark");
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
