const qs = (s) => document.querySelector(s),
  qsa = (s) => [...document.querySelectorAll(s)];
const getID = () => new URLSearchParams(location.search).get("id");
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
  st === "ฟัน"
    ? "icon/icon1.png"
    : st === "ยิง"
    ? "icon/icon2.png"
    : "icon/icon3.png";
function elemIconPath(el) {
  switch (el) {
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
  return (
    {
      SSR: "icon/SSR.png",
      SS: "icon/SS.png",
      S: "icon/S.png",
      A: "icon/A.png",
    }[r] || "icon/S.png"
  );
}
async function fetchOne(id) {
  const r = await fetch("seraphs.php?id=" + encodeURIComponent(id), {
    cache: "no-store",
  });
  if (!r.ok) throw 0;
  return r.json();
}
async function fetchSkills(id) {
  const r = await fetch(
    "seraph_skills.php?seraph_id=" + encodeURIComponent(id),
    { cache: "no-store" }
  );
  return r.ok ? r.json() : [];
}
function fillHeader(it) {
  qs("#dImg").src = fixPath(it.image || "");
  qs("#dRarity").src = rarityIconPath(it.rarity || "");
  qs("#dName").textContent = it.name_th || "(ไม่พบชื่อ)";
  qs("#dRole").textContent = it.role || "";
  qs("#dElem").textContent = it.element || "";
  qs("#dStyleIco").src = styleIconPath(it.style || "");
  qs("#dElemIco").src = elemIconPath(it.element || "");
}
function renderStats(it) {
  const host = qs("#dStats");
  const pairs = [
    ["Dp", it.dp],
    ["Hp", it.hp],
    ["Str", it.str_val],
    ["Dex", it.dex],
    ["Pdef", it.pdef],
    ["Mdef", it.mdef],
    ["Int", it.int_stat],
    ["Luck", it.luck],
  ].filter(([, v]) => v !== null && v !== undefined && v !== "");
  host.innerHTML = pairs.length
    ? pairs.map(([k, v]) => `<div class="stat">${k} ${v}</div>`).join("")
    : '<div style="opacity:.7">ยังไม่ระบุค่าสเตตัส</div>';
}
function renderSkills(list) {
  const b = { skill: [], passive: [], resonance: [] };
  list.forEach((s) => (b[s.tab] || (b[s.tab] = [])).push(s));
  const fill = (id, items) => {
    const h = qs("#tab-" + id);
    h.innerHTML = items.length
      ? items
          .map(
            (s) =>
              `<article class="skill"><div class="title">${
                s.name || "-"
              }</div><div class="desc">${(s.desc || "").replace(
                /\n/g,
                "<br>"
              )}</div></article>`
          )
          .join("")
      : `<div style="opacity:.7">ยังไม่มีข้อมูล</div>`;
  };
  fill("skill", b.skill);
  fill("passive", b.passive);
  fill("resonance", b.resonance);
}
qsa(".tab-btn").forEach((btn) =>
  btn.addEventListener("click", () => {
    qsa(".tab-btn").forEach((b) => b.classList.remove("active"));
    btn.classList.add("active");
    const t = btn.dataset.tab;
    qsa(".tab").forEach((p) => p.classList.remove("active"));
    qs("#tab-" + t).classList.add("active");
  })
);
(async () => {
  const id = getID();
  if (!id) return;
  const cache = sessionStorage.getItem("seraph_preview_" + id);
  if (cache) {
    try {
      fillHeader(JSON.parse(cache));
    } catch {}
  }
  try {
    const it = await fetchOne(id);
    fillHeader(it);
    renderStats(it);
  } catch {}
  const skills = await fetchSkills(id);
  renderSkills(skills);
})();
