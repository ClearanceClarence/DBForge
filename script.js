// ─── Existing scroll reveal ─────────────────────────────────
const reveals = document.querySelectorAll(".reveal");
const observer = new IntersectionObserver(
  (e) => {
    e.forEach((e, i) => {
      if (e.isIntersecting) {
        setTimeout(() => e.target.classList.add("visible"), i * 80);
        observer.unobserve(e.target);
      }
    });
  },
  { threshold: 0.1, rootMargin: "0px 0px -40px 0px" },
);
reveals.forEach((el) => observer.observe(el));

// ─── Kinetic title observer (adds .in-view) ─────────────────
const kinetics = document.querySelectorAll(
  ".kinetic-title:not(.in-view), .kinetic-label, .reveal-stagger",
);
const kineticObs = new IntersectionObserver(
  (entries) => {
    entries.forEach((en) => {
      if (en.isIntersecting) {
        en.target.classList.add("in-view");
        kineticObs.unobserve(en.target);
      }
    });
  },
  { threshold: 0.2, rootMargin: "0px 0px -60px 0px" },
);
kinetics.forEach((el) => kineticObs.observe(el));

// ─── Smooth anchor scroll ───────────────────────────────────
document.querySelectorAll('a[href^="#"]').forEach((a) => {
  a.addEventListener("click", (e) => {
    const href = a.getAttribute("href");
    if (!href || href === "#") return;
    const t = document.querySelector(href);
    if (t) {
      e.preventDefault();
      t.scrollIntoView({ behavior: "smooth", block: "start" });
    }
  });
});

// ─── Theme toggle ───────────────────────────────────────────
(function initTheme() {
  const root = document.documentElement;
  const btn = document.getElementById("theme-toggle");
  const saved = localStorage.getItem("dbforge-theme");
  const prefers =
    window.matchMedia &&
    window.matchMedia("(prefers-color-scheme: light)").matches
      ? "light"
      : "dark";
  const initial = saved || prefers;
  root.setAttribute("data-theme", initial);
  if (btn) {
    btn.addEventListener("click", () => {
      const now =
        root.getAttribute("data-theme") === "light" ? "dark" : "light";
      root.setAttribute("data-theme", now);
      localStorage.setItem("dbforge-theme", now);
    });
  }
})();

// ─── Active section nav highlighting ────────────────────────
(function initActiveNav() {
  const sections = document.querySelectorAll("section[id]");
  const navLinks = Array.from(document.querySelectorAll(".nav-links a")).filter(
    (a) => a.getAttribute("href") && a.getAttribute("href").startsWith("#"),
  );
  if (!sections.length || !navLinks.length) return;

  const linkMap = {};
  navLinks.forEach((a) => {
    const id = a.getAttribute("href").slice(1);
    linkMap[id] = a;
  });

  const navObs = new IntersectionObserver(
    (entries) => {
      entries.forEach((en) => {
        const id = en.target.id;
        if (!linkMap[id]) return;
        if (en.isIntersecting && en.intersectionRatio > 0.3) {
          navLinks.forEach((a) => a.classList.remove("nav-active"));
          linkMap[id].classList.add("nav-active");
        }
      });
    },
    { threshold: [0.3, 0.6] },
  );
  sections.forEach((s) => {
    if (linkMap[s.id]) navObs.observe(s);
  });
})();
// ─── Interactive comparison (search + filter + expand) ─────
(function initCompare() {
  const list = document.getElementById("cmp-list");
  const input = document.getElementById("cmp-input");
  const clearBtn = document.getElementById("cmp-clear");
  const searchWrap = document.getElementById("cmp-search");
  const countEl = document.getElementById("cmp-count");
  const emptyEl = document.getElementById("cmp-empty");
  const chips = document.querySelectorAll(".cmp-chip");
  if (!list || !input) return;

  const rows = Array.from(list.querySelectorAll(".cmp-row"));
  const total = rows.length;
  let activeCat = "all";
  let query = "";

  function apply() {
    let visible = 0;
    const q = query.trim().toLowerCase();
    rows.forEach((row) => {
      const cat = row.dataset.cat || "";
      const kw = (row.dataset.keywords || "") + " " + row.textContent;
      const catOk = activeCat === "all" || cat === activeCat;
      const qOk = !q || kw.toLowerCase().includes(q);
      const show = catOk && qOk;
      row.style.display = show ? "" : "none";
      if (show) visible++;
    });
    countEl.textContent = visible + " / " + total;
    emptyEl.classList.toggle("show", visible === 0);
  }

  input.addEventListener("input", (e) => {
    query = e.target.value;
    searchWrap.classList.toggle("has-value", !!query);
    apply();
  });
  input.addEventListener("keydown", (e) => {
    if (e.key === "Escape") {
      input.value = "";
      query = "";
      searchWrap.classList.remove("has-value");
      apply();
      input.blur();
    }
  });
  clearBtn.addEventListener("click", () => {
    input.value = "";
    query = "";
    searchWrap.classList.remove("has-value");
    apply();
    input.focus();
  });
  chips.forEach((chip) => {
    chip.addEventListener("click", () => {
      chips.forEach((c) => c.classList.remove("active"));
      chip.classList.add("active");
      activeCat = chip.dataset.cat;
      apply();
    });
  });

  // Row expand toggles
  rows.forEach((row) => {
    const main = row.querySelector(".cmp-row-main");
    if (!main) return;
    const toggle = () => row.classList.toggle("open");
    main.addEventListener("click", toggle);
    main.addEventListener("keydown", (e) => {
      if (e.key === "Enter" || e.key === " ") {
        e.preventDefault();
        toggle();
      }
    });
  });
})();

// ─── Feature picker (tab switcher) ─────────────────────────
(function initFeaturePicker() {
  const picker = document.getElementById("feature-picker");
  if (!picker) return;
  const tabs = picker.querySelectorAll(".fp-tab");
  const panels = picker.querySelectorAll(".fp-panel");
  tabs.forEach((tab) => {
    tab.addEventListener("click", () => {
      const target = tab.dataset.panel;
      tabs.forEach((t) => {
        t.classList.toggle("active", t === tab);
        t.setAttribute("aria-selected", t === tab ? "true" : "false");
      });
      panels.forEach((p) => {
        p.classList.toggle("active", p.id === target);
      });
    });
    tab.addEventListener("keydown", (e) => {
      const idx = Array.from(tabs).indexOf(tab);
      let next = null;
      if (e.key === "ArrowDown" || e.key === "ArrowRight") {
        next = tabs[(idx + 1) % tabs.length];
      } else if (e.key === "ArrowUp" || e.key === "ArrowLeft") {
        next = tabs[(idx - 1 + tabs.length) % tabs.length];
      }
      if (next) {
        e.preventDefault();
        next.click();
        next.focus();
      }
    });
  });
})();
