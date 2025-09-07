const grid = document.getElementById("seraphGrid");
const qInput = document.getElementById("q");
const fRole = document.getElementById("filterRole");
const fRar = document.getElementById("filterRarity");
const styleFilter = document.getElementById("styleFilter");
const elemFilter = document.getElementById("elemFilter");
const rarityFilter = document.getElementById("rarityFilter");

let styleActive = "",
  elemActive = "",
  rarityActive = "",
  BY_ID = Object.create(null);

function fixPath(s) {
  if (!s) return "";
  if (
    /^https?:\/\//i.test(s) ||
    s.startsWith("/") ||
    s.startsWith("uploads/") ||
    s.startsWith("img/")
  )
    return s;
  return "uploads/seraphs/" + s;
}
const styleIconPath = (s) =>
  s === "ฟัน"
    ? "icon/icon1.png"
    : s === "ยิง"
    ? "icon/icon2.png"
    : "icon/icon3.png";
function elemIconPath(e) {
  switch (e) {
    case "ไฟ":
      return "icon/em1.png";
    case "สายฟ้า":
      return "icon/em2.png";
    case "น้ำแข็ง":
      return "icon/em3.png";
    case "มืด":
      return "icon/em4.png";
    default:
      return "icon/em5.png";
  }
}
function rarityIconPath(r) {
  switch (r) {
    case "SSR":
      return "icon/SSR.png";
    case "SS":
      return "icon/SS.png";
    case "S":
      return "icon/S.png";
    case "A":
      return "icon/A.png";
    default:
      return "icon/S.png";
  }
}

const norm = (x) => String(x ?? "").trim(),
  normRole = (x) => norm(x).toUpperCase();
function normRarity(x) {
  const s = norm(x).toUpperCase();
  if (s.includes("SSR")) return "SSR";
  if (s === "SS") return "SS";
  if (s === "S") return "S";
  if (s === "A") return "A";
  return s;
}

let DATA = [];
async function fetchSeraphs() {
  const r = await fetch("seraphs.php", { cache: "no-store" });
  const arr = await r.json();
  return (Array.isArray(arr) ? arr : []).map((it) => ({
    ...it,
    image: fixPath(it.image || ""),
    tags: it.tags || "",
    obtain_type: it.obtain_type || 'normal',
    role_n: normRole(it.role),
    rarity_n: normRarity(it.rarity),
    element_n: norm(it.element),
    style_n: norm(it.style),
    name_th_n: norm(it.name_th),
    name_jp_n: norm(it.name_jp),
  }));
}

function card(it){
  const obClass = it.obtain_type === 'limited' ? 'obtain-limited'
                 : it.obtain_type === 'collab' ? 'obtain-collab' : '';
  return `
  <article class="card ${obClass}" data-id="${it.id}" data-href="seraph_detail.html?id=${it.id}">
    <div class="card-img-wrap">
      ${it.image ? `<img class="card-img" src="${fixPath(it.image)}" alt="${it.name_th}" onerror="this.style.display='none'">` : ''}
      <img class="rarity-badge-img" src="${rarityIconPath(it.rarity)}" alt="${it.rarity}">
    </div>
    <div class="card-body">
      <h3 class="card-title">${it.name_th}</h3>
      <div class="card-meta">
        <span class="icon-wrap"><img class="icon-img" src="${styleIconPath(it.style)}"  alt=""></span>
        <span class="icon-wrap"><img class="icon-img" src="${elemIconPath(it.element)}" alt=""></span>
        <span class="badge badge-role">${it.role}</span>
        <span class="badge badge-ele">${it.element}</span>
      </div>
    </div>
  </article>`;
}

function render(list) {
  BY_ID = Object.fromEntries(list.map((it) => [String(it.id), it]));
  grid.innerHTML = list.map(card).join("");
}
function getSelectedRarity() {
  if (rarityFilter) return rarityActive || "";
  if (fRar) return normRarity(fRar.value || "");
  return "";
}

function applyFilter() {
  const q = norm(qInput?.value).toLowerCase(),
    roleSel = normRole(fRole?.value || ""),
    rarSel = getSelectedRarity(),
    stySel = norm(styleActive || ""),
    eleSel = norm(elemActive || "");
  render(
    DATA.filter((it) => {
      if (roleSel && it.role_n !== roleSel) return false;
      if (rarSel && it.rarity_n !== rarSel) return false;
      if (stySel && it.style_n !== stySel) return false;
      if (eleSel && it.element_n !== eleSel) return false;
      if (obtainActive && it.obtain_type !== obtainActive) return false;
      if (q) {
        const hay = [
          it.name_th_n,
          it.name_jp_n,
          it.role_n,
          it.rarity_n,
          it.element_n,
          it.style_n,
          it.tags || "",
        ]
          .join(" ")
          .toLowerCase();
        if (!hay.includes(q)) return false;
      }
      return true;
    })
  );
}

[qInput, fRole, fRar].forEach(
  (el) => el && el.addEventListener("input", applyFilter)
);
[fRole, fRar].forEach((el) => el && el.addEventListener("change", applyFilter));
styleFilter?.addEventListener("click", (e) => {
  const b = e.target.closest(".style-btn");
  if (!b) return;
  [...styleFilter.querySelectorAll(".style-btn")].forEach((x) =>
    x.classList.remove("is-active")
  );
  b.classList.add("is-active");
  styleActive = b.dataset.style || "";
  applyFilter();
});
elemFilter?.addEventListener("click", (e) => {
  const b = e.target.closest(".style-btn");
  if (!b) return;
  [...elemFilter.querySelectorAll(".style-btn")].forEach((x) =>
    x.classList.remove("is-active")
  );
  b.classList.add("is-active");
  elemActive = b.dataset.element || "";
  applyFilter();
});
rarityFilter?.addEventListener("click", (e) => {
  const b = e.target.closest(".style-btn");
  if (!b) return;
  [...rarityFilter.querySelectorAll(".style-btn")].forEach((x) =>
    x.classList.remove("is-active")
  );
  b.classList.add("is-active");
  rarityActive = b.dataset.rarity || "";
  applyFilter();
});

grid.addEventListener("click", (e) => {
  const c = e.target.closest(".card");
  if (!c || !grid.contains(c)) return;
  const id = c.dataset.id,
    url = c.dataset.href,
    item = BY_ID[id];
  if (item)
    sessionStorage.setItem("seraph_preview_" + id, JSON.stringify(item));
  if (url) location.href = url;
});

(async () => {
  DATA = await fetchSeraphs();
  render(DATA);
})();

const obtainFilter = document.getElementById('obtainFilter');
let obtainActive = '';

obtainFilter?.addEventListener('click', e=>{
  const btn = e.target.closest('.style-btn'); if(!btn) return;
  [...obtainFilter.querySelectorAll('.style-btn')].forEach(b=>b.classList.remove('is-active'));
  btn.classList.add('is-active');
  obtainActive = btn.dataset.obtain || '';
  applyFilter();
});

