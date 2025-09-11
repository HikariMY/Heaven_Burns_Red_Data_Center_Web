const $ = s => document.querySelector(s);
const $$ = s => [...document.querySelectorAll(s)];
const fix = src => !src ? "" :
  (/^https?:\/\//.test(src) || src.startsWith("/") || src.startsWith("uploads/") || src.startsWith("img/") || src.startsWith("icon/"))
    ? src : "uploads/accessories/" + src;

const elemIcon = el => ({ไฟ:"icon/em1.png",สายฟ้า:"icon/em2.png",น้ำแข็ง:"icon/em3.png",มืด:"icon/em4.png",แสง:"icon/em5.png"}[el] || "icon/em5.png");

let filters = { element:"", stars:"", type_id:"", q:"" };
let openPanel = null;
let selectedCard = null;

/* -------- helpers -------- */
function setSelected(card){
  if (selectedCard) selectedCard.classList.remove('is-selected');
  selectedCard = card || null;
  if (selectedCard) selectedCard.classList.add('is-selected');
}

function starRow(n){
  const N = Math.max(0, Math.min(6, +n||0));
  return '<div class="stars">' + Array.from({length:6}, (_,i)=>`<span class="star ${i<N?'on':''}">★</span>`).join('') + '</div>';
}

function card(a){
  return `<article class="card" data-id="${a.id}">
    <div class="imgbox"><img src="${fix(a.image)}" alt="${a.name_th}"></div>
    <div class="badge-row">
      ${starRow(a.stars)}
      <div class="badges">
        <img class="badge elem" src="${elemIcon(a.element)}" title="${a.element}">
        ${a.type_icon?`<img class="badge type" src="${fix(a.type_icon)}" title="${a.type_name}">`:''}
      </div>
    </div>
    <div class="name">${a.name_th}</div>
  </article>`;
}

async function loadTypes(){
  const r = await fetch('accessory_types.php',{cache:"no-store"});
  const arr = await r.json();
  $("#typeFilters").innerHTML =
    '<button class="chip active" data-type="">ทุกประเภท</button>' +
    arr.map(t=>`<button class="chip" data-type="${t.id}"><img src="${fix(t.icon)}" style="width:40px;height:40px;vertical-align:-3px"> ${t.name_th}</button>`).join('');
}

async function load(){
  const url = new URL('accessories.php', location.href);
  Object.entries(filters).forEach(([k,v])=>{ if(v) url.searchParams.set(k,v); });
  const r = await fetch(url,{cache:"no-store"});
  const list = await r.json();
  $("#grid").innerHTML = list.map(card).join('') || '<div class="muted">ยังไม่มีข้อมูล</div>';
  closePanel(); // ปิด panel เดิมและล้างไฮไลต์
}

/* -------- detail panel -------- */
function closePanel(){ 
  if(openPanel){ openPanel.remove(); openPanel=null; } 
  setSelected(null);
}

function insertAfterRowEnd(container, card, panel){
  const rowTop = card.offsetTop;
  const items = [...container.children].filter(el => el.classList && el.classList.contains('card'));
  let rowLast = card;
  for (const el of items) if (el.offsetTop === rowTop) rowLast = el;
  rowLast.insertAdjacentElement('afterend', panel);
}

async function openDetailUnder(card){
  const id = card.dataset.id;
  const grid = $("#grid");
  if (openPanel && openPanel.dataset.forId === id) { closePanel(); return; }
  closePanel();

  const r = await fetch('accessory_detail.php?id='+id,{cache:"no-store"});
  if(!r.ok) return;
  const a = await r.json();

  const panel = document.createElement('div');
  panel.className = 'detail-panel';
  panel.dataset.forId = id;
  panel.style.gridColumn = '1 / -1';
  panel.innerHTML = `
    <div class="detail">
      <div class="detail-header">
        <h3 style="margin:0">${a.name_th}</h3>
        <button class="detail-close" aria-label="close" type="button">✕</button>
      </div>
      <div class="drow">
        <div class="dicon"><img src="${fix(a.image)}"></div>
        <div>
          ${starRow(a.stars)}
          <div class="muted">ประเภท: ${a.type_name || "-"}</div>
          <div class="muted">ธาตุ: ${a.element || "-"}</div>
          <div class="muted">เลเวล: ${a.level ?? "-"}</div>
          <div class="muted">ราคา: ${a.price ?? "-"}</div>
        </div>
      </div>
      <div class="drow"><div></div><div>${(a.description||"").replace(/\n/g,"<br>")}</div></div>
    </div>`;
  insertAfterRowEnd(grid, card, panel);
  openPanel = panel;

  setSelected(card); // ⭐ ไฮไลต์การ์ดที่กำลังดู

  panel.querySelector('.detail-close').addEventListener('click', closePanel, {once:true});
  panel.scrollIntoView({behavior:"smooth", block:"start"});
}

/* -------- filters & events -------- */
$("#elemFilters").addEventListener('click', e=>{
  const b=e.target.closest('.chip'); if(!b) return;
  $$("#elemFilters .chip").forEach(x=>x.classList.remove('active'));
  b.classList.add('active');
  filters.element=b.dataset.el||"";
  load();
});
$("#typeFilters").addEventListener('click', e=>{
  const b=e.target.closest('.chip'); if(!b) return;
  $$("#typeFilters .chip").forEach(x=>x.classList.remove('active'));
  b.classList.add('active');
  filters.type_id=b.dataset.type||"";
  load();
});
$("#starFilters").addEventListener('click', e=>{
  const b=e.target.closest('.chip'); if(!b) return;
  $$("#starFilters .chip").forEach(x=>x.classList.remove('active'));
  b.classList.add('active');
  filters.stars=b.dataset.stars||"";
  load();
});
$("#q").addEventListener('input', e=>{ filters.q=e.target.value.trim(); load(); });

$("#grid").addEventListener('click', e=>{
  const card=e.target.closest('.card'); if(!card) return;
  openDetailUnder(card);
});

// ปิดเมื่อคลิกนอก panel/การ์ด/ฟิลเตอร์
document.addEventListener('click', (e)=>{
  if(!openPanel) return;
  const insidePanel = openPanel.contains(e.target);
  const onCard = e.target.closest('.card');
  const onFilter = e.target.closest('.chip') || e.target.closest('#elemFilters') || e.target.closest('#typeFilters') || e.target.closest('#starFilters');
  if(!insidePanel && !onCard && !onFilter) closePanel();
});
document.addEventListener('keydown', (e)=>{ if(e.key==='Escape') closePanel(); });

// extra style (รวมไฮไลต์ + ปรับขนาด badge แยกตัว)
const extra = document.createElement('style');
extra.textContent = `
.detail-panel{margin-top:8px}
.detail-header{display:flex;justify-content:space-between;align-items:center;margin-bottom:6px}
.detail-close{border:0;background:#f1f5f9;border-radius:8px;padding:2px 8px;cursor:pointer}
.card.is-selected{
  border:2px solid #ff5a7a !important;
  box-shadow:0 6px 18px rgba(255,90,122,.18);
  border-radius:14px;
}
/* ตั้งค่าให้ badges เรียงกลางแนวตั้ง */
.badges{ display:flex; align-items:center; gap:8px }
/* ขนาดแยก: ธาตุ = เล็ก, ประเภท = ใหญ่กว่า */
.badge.elem{ width:32px; height:32px; }
.badge.type{ width:55px; height:55px; }
`;
document.head.appendChild(extra);

/* init */
(async function(){ await loadTypes(); await load(); })();
