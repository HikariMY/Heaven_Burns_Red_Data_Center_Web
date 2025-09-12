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

function elemIconPathOne(el) {
  return (
    {
      ไร้ธาตุ: "icon/em0.png",
      ไฟ: "icon/em1.png",
      สายฟ้า: "icon/em2.png",
      น้ำแข็ง: "icon/em3.png",
      มืด: "icon/em4.png",
      แสง: "icon/em5.png",
    }[el] || ""
  ); // ไม่มี -> คืนค่าว่าง
}

const rarityIconPath = (r) =>
  ({ SSR: "icon/SSR.png", SS: "icon/SS.png", S: "icon/S.png", A: "icon/A.png" }[
    r
  ] || "icon/S.png");

/* ---------- fetchers ---------- */
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

/* ---------- helpers ---------- */
function splitElems(val) {
  // รองรับค่าว่าง, 1 ธาตุ, หรือคั่นด้วย , ได้สูงสุด 2 อัน
  return String(val || "")
    .split(",")
    .map((s) => s.trim())
    .filter(Boolean)
    .slice(0, 2);
}
function elemIconsHTML(val) {
  const arr = splitElems(val);
  if (!arr.length) return "";
  return arr
    .map(
      (e) =>
        `<span class="icon-wrap"><img class="icon-img" src="${elemIconPathOne(
          e
        )}" alt="${e}"></span>`
    )
    .join("");
}

/* ---------- header ---------- */
function fillHeader(it) {
  qs("#dImg").src = fixPath(it.image || "");
  qs("#dRarity").src = rarityIconPath(it.rarity || "");
  qs("#dName").textContent = it.name_th || "(ไม่พบชื่อ)";
  qs("#dRole").textContent = it.role || "";
  qs("#dStyleIco").src = styleIconPath(it.style || "");

  const elems = splitElems(it.element);
  const iconsHTML = elems
    .map(
      (e) =>
        `<span class="icon-wrap"><img class="icon-img" src="${elemIconPathOne(
          e
        )}" alt="${e}"></span>`
    )
    .join("");
  const iconsHost = qs("#dElemIcons");
  const tagsHost = qs("#dElemTags");
  if (iconsHost) iconsHost.innerHTML = iconsHTML;
  if (tagsHost) tagsHost.textContent = elems.join(", ");
}

/* ---------- stats ---------- */
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

/* ---------- skills (with icons) ---------- */
function renderSkills(list) {
  const buckets = { skill: [], passive: [], resonance: [], limit_break: [] };
  list.forEach((s) => (buckets[s.tab] || (buckets[s.tab] = [])).push(s));

  const pill = (s) => {
    const icons = [];
    if (s.style_tag) {
      const p = styleIconPath(s.style_tag);
      if (p)
        icons.push(
          `<img class="icon-img" src="${p}" alt="${s.style_tag}" style="width:20px;height:20px;object-fit:contain">`
        );
    }
    if (s.element_tag) {
      String(s.element_tag)
        .split(",")
        .map((x) => x.trim())
        .filter(Boolean)
        .slice(0, 2)
        .forEach((e) => {
          const p = elemIconPathOne(e);
          if (p)
            icons.push(
              `<img class="icon-img" src="${p}" alt="${e}" style="width:20px;height:20px;object-fit:contain">`
            );
        });
    }
    return icons.length
      ? `<div style="display:flex;gap:6px;align-items:center;margin-top:6px">${icons.join(
          ""
        )}</div>`
      : "";
  };

  const fill = (id, items) => {
    const box = document.querySelector("#tab-" + id);
    if (!box) return;
    box.innerHTML = items.length
      ? items
          .map(
            (s) => `
          <article class="skill">
            <div class="title">${s.name || "-"}</div>
            <div class="desc">${(s.desc || "").replace(/\n/g, "<br>")}</div>
            ${pill(s)}
          </article>
        `
          )
          .join("")
      : `<div style="opacity:.7">ยังไม่มีข้อมูล</div>`;
  };

  fill("skill", buckets.skill);
  fill("passive", buckets.passive);
  fill("resonance", buckets.resonance);
  fill("limit_break", buckets.limit_break);
}

/* ---------- tab switch ---------- */
qsa(".tab-btn").forEach((btn) => {
  btn.addEventListener("click", () => {
    qsa(".tab-btn").forEach((b) => b.classList.remove("active"));
    btn.classList.add("active");
    const t = btn.dataset.tab;
    qsa(".tab").forEach((p) => p.classList.remove("active"));
    const pane = qs("#tab-" + t);
    if (pane) pane.classList.add("active");
  });
});

/* ---------- bootstrap ---------- */
(async () => {
  const id = getID();
  if (!id) return;

  // เติม header จาก cache (ถ้ามี)
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
