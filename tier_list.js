// --- helpers ---
const $ = (s) => document.querySelector(s);
const $$ = (s) => [...document.querySelectorAll(s)];
const fixPath = (src) =>
  !src
    ? ""
    : /^https?:\/\//i.test(src) ||
      src.startsWith("/") ||
      src.startsWith("uploads/") ||
      src.startsWith("img/")
    ? src
    : "uploads/seraphs/" + src;

const styleIconPath = (st) =>
  st === "ฟัน" ? "icon/icon1.png" : st === "ยิง" ? "icon/icon2.png" : "icon/icon3.png";

function rarityIconPath(r) {
  return (
    {
      SSR: "icon/SSR.png",
      SS: "icon/SS.png",
      S: "icon/S.png",
      A: "icon/A.png",
    }[r] || "icon/S.png"
  );
}

// ===== element icons (รองรับมากสุด 2 ธาตุ) =====
const ELEM_ICON_MAP = {
  "ไร้ธาตุ": "icon/em0.png",
  "ไฟ": "icon/em1.png",
  "สายฟ้า": "icon/em2.png",
  "น้ำแข็ง": "icon/em3.png",
  "มืด": "icon/em4.png",
  "แสง": "icon/em5.png",
};

// แปลงสตริง "ไฟ, สายฟ้า" -> ["ไฟ","สายฟ้า"] (ไม่เกิน 2)
function parseElements(elementStr) {
  return String(elementStr || "")
    .split(",")
    .map((s) => s.trim())
    .filter(Boolean)
    .slice(0, 2);
}

// สร้าง HTML ไอคอนธาตุ 1–2 ชิ้น
function elemIconsHTML(elementStr) {
  const arr = parseElements(elementStr);
  if (!arr.length) return "";
  return arr
    .map((el) => {
      const src = ELEM_ICON_MAP[el] || ELEM_ICON_MAP["แสง"];
      return `<span class="chip"><img src="${src}" alt="${el}"></span>`;
    })
    .join("");
}

// group role → category tab
const CAT = {
  attack: new Set(["ATTACKER", "BLASTER", "BREAKER"]),
  buff: new Set(["BUFFER"]),
  debuff: new Set(["DEBUFFER"]),
  support: new Set(["HEALER", "DEFENDER", "ADMIRAL", "RIDER"]),
};

function detectObtain(tags = "") {
  const t = String(tags).toLowerCase();
  if (t.includes("คอล") || t.includes("collab")) return "collab";
  if (t.includes("จำกัด") || t.includes("limited")) return "limited";
  return "normal";
}

// --- fetch data ---
let DATA = [];
async function loadAll() {
  const res = await fetch("seraphs.php", { cache: "no-store" });
  const arr = await res.json();
  DATA = (Array.isArray(arr) ? arr : []).map((it) => ({
    ...it,
    tier_rank: it.tier_rank !== null ? parseFloat(it.tier_rank) : null,
    image: fixPath(it.image || ""),
    obtain: it.obtain_type || detectObtain(it.tags) || "normal",
  }));
}

// --- mini card (ใหม่) ---
function miniCard(it) {
  const ob =
    it.obtain === "limited"
      ? "obtain-limited"
      : it.obtain === "collab"
      ? "obtain-collab"
      : "";
  return `
    <article class="mini ${ob}" data-id="${it.id}">
      <div class="mini-img">
        <img src="${fixPath(it.image)}" alt="${it.name_th}">
        <img class="rarity-corner" src="${rarityIconPath(it.rarity)}" alt="${it.rarity}">
        <div class="corner-chips">
          <span class="chip"><img src="${styleIconPath(it.style)}" alt="${it.style}"></span>
          ${elemIconsHTML(it.element)}
        </div>
      </div>
      <div class="mini-body">
        <span class="badge badge-role">${it.role}</span>
      </div>
    </article>`;
}

// --- Tier sections ---
const fmtTier = (t) => String(t).replace(/\.0$/, ""); // 2.0 -> 2
function renderTierSections(list) {
  const host = $("#tierHost");
  const tiers = [
    ...new Set(list.filter((x) => x.tier_rank !== null).map((x) => x.tier_rank)),
  ].sort((a, b) => a - b);
  const unknown = list.filter((x) => x.tier_rank === null);

  let html = "";
  for (const t of tiers) {
    const items = list.filter((x) => x.tier_rank === t);
    if (!items.length) continue;
    html += `<section class="section">
      <h3>Tier${fmtTier(t)}</h3>
      <div class="tier-grid">${items.map(miniCard).join("")}</div>
    </section>`;
  }
  if (unknown.length) {
    html += `<section class="section">
      <h3>ยังไม่จัดอันดับ</h3>
      <div class="tier-grid">${unknown.map(miniCard).join("")}</div>
    </section>`;
  }

  host.innerHTML = html;

  // คลิกการ์ด → ไปหน้า detail
  host.querySelectorAll(".mini").forEach((el) => {
    el.addEventListener("click", () => {
      const id = el.dataset.id;
      const item = list.find((x) => String(x.id) === String(id));
      if (item) sessionStorage.setItem("seraph_preview_" + id, JSON.stringify(item));
      location.href = "seraph_detail.html?id=" + id;
    });
  });
}

// --- filter state ---
let activeCat = "";
let activeStyle = "";
let activeElem = "";
let activeObtain = "";

// เช็ค element กับหลายธาตุ (การ์ดมีธาตุที่เลือกอย่างน้อย 1 ตัว)
function matchElementFilter(itemElement, wantElement) {
  if (!wantElement) return true;
  const arr = parseElements(itemElement);
  return arr.includes(wantElement);
}

function applyFilter() {
  const list = DATA.filter((it) => {
    if (activeCat && !CAT[activeCat]?.has(it.role)) return false;
    if (activeStyle && it.style !== activeStyle) return false;
    if (!matchElementFilter(it.element, activeElem)) return false;
    if (activeObtain && it.obtain !== activeObtain) return false;
    return true;
  }).sort((a, b) => {
    const ax = a.tier_rank,
      bx = b.tier_rank;
    if (ax === null && bx === null) return 0;
    if (ax === null) return 1;
    if (bx === null) return -1;
    return ax - bx;
  });

  renderTierSections(list);
}

// --- wire UI ---
$("#catTabs")?.addEventListener("click", (e) => {
  const btn = e.target.closest(".tab");
  if (!btn) return;
  $$("#catTabs .tab").forEach((x) => x.classList.remove("active"));
  btn.classList.add("active");
  activeCat = btn.dataset.cat || "";
  applyFilter();
});

function bindChipRow(rowSel, key) {
  const row = $(rowSel);
  row?.addEventListener("click", (e) => {
    const chip = e.target.closest(".chip");
    if (!chip) return;
    row.querySelectorAll(".chip").forEach((c) => c.classList.remove("is-active"));
    chip.classList.add("is-active");
    const val = chip.dataset[key] || "";
    if (key === "style") activeStyle = val;
    if (key === "element") activeElem = val; // << ใช้กับหลายธาตุได้แล้ว
    if (key === "obtain") activeObtain = val;
    applyFilter();
  });
}
bindChipRow("#styleChips", "style");
bindChipRow("#elemChips", "element");
bindChipRow("#obtainChips", "obtain");

// --- boot ---
(async () => {
  await loadAll();
  applyFilter();
})();
