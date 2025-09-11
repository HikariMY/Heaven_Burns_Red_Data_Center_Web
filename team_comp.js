const $  = s => document.querySelector(s);
const $$ = s => [...document.querySelectorAll(s)];

const fixPath = (src) => !src ? "" :
  (/^https?:\/\//i.test(src) || src.startsWith("/") || src.startsWith("uploads/") || src.startsWith("img/"))
  ? src : "uploads/seraphs/" + src;

const rarityIcon = (r) => ({SSR:"icon/SSR.png",SS:"icon/SS.png",S:"icon/S.png",A:"icon/A.png"}[r] || "icon/S.png");
const styleIcon  = (st) => st==="ฟัน"?"icon/icon1.png":(st==="ยิง"?"icon/icon2.png":"icon/icon3.png");
const elemIcon   = (el) => ({ไฟ:"icon/em1.png",สายฟ้า:"icon/em2.png",น้ำแข็ง:"icon/em3.png",มืด:"icon/em4.png",แสง:"icon/em5.png"}[el] || "icon/em5.png");

let activeElem = "";
let openPanel = null; // แผงรายละเอียดที่เปิดอยู่
let selectedCard = null;

/* ---------------- builders ---------------- */
function memberHTML(m){
  return `<div class="mem">
    <img src="${fixPath(m.image)}" alt="${m.name_th}">
    <img class="rar" src="${rarityIcon(m.rarity)}" alt="${m.rarity}">
    <div class="chips">
      <img src="${styleIcon(m.style)}" alt="${m.style}">
      <img src="${elemIcon(m.element)}" alt="${m.element}">
    </div>
  </div>`;
}
function compCard(c){
  return `<article class="comp-card" data-id="${c.id}">
    <div class="comp-head">
      <span class="comp-badge"><img class="icon-img" src="${elemIcon(c.element)}" style="width:20px;height:20px"> ${c.title}</span>
    </div>
    <div class="member-six">${(c.members||[]).map(memberHTML).join("")}</div>
  </article>`;
}

/* -------------- load list -------------- */
async function loadComps(){
  const url = "team_comps.php" + (activeElem?`?element=${encodeURIComponent(activeElem)}`:"");
  const r = await fetch(url,{cache:"no-store"});
  const arr = await r.json();
  $("#compGrid").innerHTML = (arr||[]).map(compCard).join("") || `<div class="muted">ยังไม่มีทีม</div>`;
  closePanel(); // รีเซ็ต panel และไฮไลต์
}

/* -------------- detail helpers -------------- */
function setSelected(card) {
  if (selectedCard) selectedCard.classList.remove('is-selected');
  selectedCard = card || null;
  if (selectedCard) selectedCard.classList.add('is-selected');
}

function closePanel(){
  if (openPanel) { openPanel.remove(); openPanel = null; }
  setSelected(null); // เคลียร์ไฮไลต์ด้วย
}

// หา “ปลายแถว” ของการ์ดที่คลิก แล้วค่อยแทรก panel หลัง element นั้น
function insertAfterRowEnd(container, card, panel){
  const rowTop = card.offsetTop;
  const items = [...container.children].filter(el => el.classList && el.classList.contains('comp-card'));
  let rowLast = card;
  for (const el of items) {
    if (el.offsetTop === rowTop) rowLast = el;
  }
  rowLast.insertAdjacentElement('afterend', panel);
}

async function openDetailUnder(card){
  const id = card.dataset.id;
  const grid = $("#compGrid");

  if (openPanel && openPanel.dataset.forId === id) { 
    closePanel(); 
    return; 
  }
  closePanel();

  const r = await fetch("team_comp_detail.php?id="+id,{cache:"no-store"});
  if(!r.ok) return;
  const data = await r.json();

  const rows = (data.details||[]).map(d=>`
    <div class="detail-row">
      <div class="d-avatar"><img src="${fixPath(d.image)}" alt="${d.name_th}"></div>
      <div class="d-text">
        <div class="title">${d.name_th||""}</div>
        <div class="desc">${(d.description||"").replace(/\n/g,"<br>")}</div>
        ${d.swaps?.length?`<div class="d-swaps">${d.swaps.map(s=>`<img src="${fixPath(s.image)}" title="${s.name_th}">`).join("")}</div>`:""}
      </div>
    </div>`).join("");

  const panel = document.createElement('div');
  panel.className = 'detail-panel';
  panel.dataset.forId = id;
  panel.style.gridColumn = '1 / -1';
  panel.innerHTML = `
    <div class="detail-box">
      <div class="detail-header">
        <span class="muted">รายละเอียด V</span>
        <button class="detail-close" aria-label="close" type="button">✕</button>
      </div>
      ${rows || `<div class="muted" style="padding:10px">ยังไม่มีรายละเอียด</div>`}
    </div>`;
  insertAfterRowEnd(grid, card, panel);
  openPanel = panel;

  setSelected(card); // ไฮไลต์การ์ดที่เลือก

  panel.querySelector('.detail-close').addEventListener('click', () => {
    closePanel();
  }, {once:true});

  panel.scrollIntoView({behavior:"smooth", block:"start"});
}

/* -------------- events -------------- */
$("#elemFilter").addEventListener("click", e=>{
  const b = e.target.closest(".elem-chip"); if(!b) return;
  $$("#elemFilter .elem-chip").forEach(x=>x.classList.remove("is-active"));
  b.classList.add("is-active");
  activeElem = b.dataset.element || "";
  loadComps();
});

$("#compGrid").addEventListener("click", e=>{
  const card = e.target.closest(".comp-card"); if(!card) return;
  openDetailUnder(card);
});

// ปิดเมื่อคลิกพื้นว่าง
document.addEventListener('click', (e)=>{
  if (!openPanel) return;
  const insidePanel = openPanel.contains(e.target);
  const onCard      = e.target.closest('.comp-card');
  const onFilter    = e.target.closest('#elemFilter');
  if (!insidePanel && !onCard && !onFilter) closePanel();
});
// ปิดด้วย ESC
document.addEventListener('keydown', (e)=>{ if(e.key==='Escape') closePanel(); });

// สไตล์เสริม
const extra = document.createElement('style');
extra.textContent = `
.comp-card.is-selected{
  border:2px solid #ff5a7a;
  box-shadow:0 4px 14px rgba(255,90,122,.2);
  border-radius:12px;
}
.detail-panel{margin-top:8px}
.detail-box{background:#fff;border:1px solid #eee;border-radius:14px;padding:10px;box-shadow:var(--shadow)}
.detail-header{display:flex;justify-content:space-between;align-items:center;margin:0 6px 8px}
.detail-close{border:0;background:#f1f5f9;border-radius:8px;padding:2px 8px;cursor:pointer}
`;
document.head.appendChild(extra);

loadComps();
