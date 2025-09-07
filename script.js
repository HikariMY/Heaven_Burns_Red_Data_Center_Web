/* =========================
   Sidebar drawer (mobile)
========================= */
const sidebar = document.getElementById("sidebar");
const scrim = document.getElementById("scrim");
const btnOpen = document.getElementById("btnOpen");

function openSide() {
  sidebar?.classList.add("is-shown");
  scrim?.classList.add("is-on");
  document.body.style.overflow = "hidden";
}
function closeSide() {
  sidebar?.classList.remove("is-shown");
  scrim?.classList.remove("is-on");
  document.body.style.overflow = "";
}
btnOpen?.addEventListener("click", openSide);
scrim?.addEventListener("click", closeSide);
document.addEventListener("keydown", (e) => {
  if (e.key === "Escape") closeSide();
});

/* =========================
   Event Slider (from PHP)
========================= */
const track = document.getElementById("track");
const dotsBox = document.getElementById("dots");

async function loadEvents() {
  try {
    const res = await fetch("events.php", { cache: "no-store" });
    if (!res.ok) throw new Error("HTTP " + res.status);
    const data = await res.json();
    return Array.isArray(data) ? data : [];
  } catch (err) {
    console.error("Load events failed:", err);
    // fallback ให้มีอย่างน้อย 1 สไลด์เวลาทดสอบ
    return [
      {
        id: 0,
        title: "Sample Event",
        desc: "ตัวอย่าง",
        image: "img/event-sample.jpg",
        start: "2025-09-01",
        end: "2025-09-07",
      },
    ];
  }
}

function formatDate(str) {
  const d = new Date(str);
  if (isNaN(d)) return "";
  return d.toLocaleDateString("th-TH", {
    day: "numeric",
    month: "short",
    year: "numeric",
  });
}

// เติมพาธให้รูป (รองรับชื่อไฟล์ล้วน)
function fixPath(src) {
  if (!src) return "";
  if (/^https?:\/\//i.test(src)) return src; // URL เต็ม
  if (src.startsWith("/")) return src; // absolute path
  if (src.startsWith("uploads/") || src.startsWith("img/")) return src;
  return "uploads/events/" + src; // สมมติอัปโหลดไว้ที่นี่
}

// หลังจาก fetch แล้ว map คีย์ให้เข้ากับฝั่งเว็บเดิม
async function loadEvents() {
  try {
    const res = await fetch("events.php", { cache: "no-store" });
    if (!res.ok) throw new Error("HTTP " + res.status);
    const data = await res.json();
    // ปรับชื่อฟิลด์ให้แน่ใจว่าเราอ่านได้ทั้ง description_text/description/desc
    return (Array.isArray(data) ? data : []).map((it) => ({
      ...it,
      desc: it.desc ?? it.description_text ?? it.description ?? "",
      image: fixPath(it.image),
    }));
  } catch (err) {
    console.error("Load events failed:", err);
    return [
      {
        id: 0,
        title: "Sample Event",
        desc: "ตัวอย่าง",
        image: fixPath("img/event-sample.jpg"),
        start: "2025-09-01",
        end: "2025-09-07",
      },
    ];
  }
}

// ใช้ desc ที่เราจัดให้แน่นอนแล้ว + กันรูปแตก
function slideHTML(ev) {
  const hasImg = !!ev.image;
  return `
    <div class="slide">
      <div class="slide-inner">
        ${
          hasImg
            ? `<img class="event-img" src="${ev.image}" alt="${
                ev.title ?? ""
              }" onerror="this.style.display='none'">`
            : ""
        }
        ${ev.title ? `<h3>${ev.title}</h3>` : ""}
        ${ev.desc ? `<p>${ev.desc}</p>` : ""}
        ${
          ev.start || ev.end
            ? `<p class="event-time">
          ${ev.start ? formatDate(ev.start) : ""}${
                ev.start && ev.end ? " – " : ""
              }${ev.end ? formatDate(ev.end) : ""}
        </p>`
            : ""
        }
      </div>
    </div>
  `;
}

async function initSlider() {
  if (!track || !dotsBox) return;

  const events = await loadEvents();

  // render slides
  track.innerHTML = events.map(slideHTML).join("");
  const slides = Array.from(track.children);
  if (slides.length === 0) return;

  // dots
  dotsBox.innerHTML = slides
    .map(
      (_, i) =>
        `<button class="dot${
          i === 0 ? " is-active" : ""
        }" aria-label="ไปสไลด์ ${i + 1}"></button>`
    )
    .join("");
  const dots = Array.from(dotsBox.children);

  // state
  let idx = 0;
  let timer = null;
  const INTERVAL = 3000;

  function render() {
    track.style.transform = `translateX(-${idx * 100}%)`;
    dots.forEach((d, i) => d.classList.toggle("is-active", i === idx));
  }
  function next() {
    idx = (idx + 1) % slides.length;
    render();
  }
  function start() {
    stop();
    if (slides.length > 1) timer = setInterval(next, INTERVAL); // ออโต้เฉพาะมี >1 สไลด์
  }
  function stop() {
    if (timer) clearInterval(timer);
    timer = null;
  }

  // interactions
  dots.forEach((d, i) =>
    d.addEventListener("click", () => {
      idx = i;
      render();
      start();
    })
  );
  track.addEventListener("mouseenter", stop);
  track.addEventListener("mouseleave", start);
  document.addEventListener("visibilitychange", () => {
    document.hidden ? stop() : start();
  });

  render();
  start();
}
initSlider();

/* =========================
   Active nav on scroll
========================= */
const sectionIds = ["home"];
const sections = sectionIds
  .map((id) => document.getElementById(id))
  .filter(Boolean);
const navLinks = Array.from(document.querySelectorAll(".main-nav .nav-link"));
function linkTargetsToId(a) {
  const href = (a.getAttribute("href") || "").trim();
  if (href === "#") return "home";
  if (href.startsWith("#")) return href.slice(1);
  try {
    const url = new URL(href, location.href);
    return url.hash ? url.hash.slice(1) : "";
  } catch {
    return "";
  }
}
function setActive() {
  if (sections.length === 0) return;
  const y = window.scrollY + 120;
  let current = sections[0];
  for (const s of sections) {
    if (s.offsetTop <= y) current = s;
  }
  navLinks.forEach((a) =>
    a.classList.toggle("active", linkTargetsToId(a) === current.id)
  );
}
window.addEventListener("scroll", setActive);
setActive();
