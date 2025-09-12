const $ = s=>document.querySelector(s);
const $$= s=>Array.from(document.querySelectorAll(s));
const fix = (p)=>!p?"":(/^https?:\/\//i.test(p)||p.startsWith("/")||p.startsWith("uploads/")||p.startsWith("img/"))?p:("uploads/guides/"+p.replace(/^\/+/,""));

let STATE={mode:"", q:""};
let openPanel=null, selected=null;

function card(g){
  const img = g.image1 || g.image2 || "";
  const when = new Date(g.created_at).toLocaleString("th-TH",{dateStyle:"medium", timeStyle:"short"});
  const excerpt = (g.excerpt||"").replace(/\n/g," ").slice(0,180);
  return `
  <article class="card" data-id="${g.id}">
    ${img?`<img class="thumb" src="${fix(img)}" alt="">`:''}
    <h3 class="mt-2 font-black">${g.title}</h3>
    <div class="text-sm muted">${g.mode} · โดย ${g.author_name} · ${when}</div>
    <p class="mt-1">${excerpt}${excerpt.length===180?'…':''}</p>
  </article>`;
}

async function load(){
  const url = new URL('guides_list.php', location.href);
  if(STATE.mode) url.searchParams.set('mode', STATE.mode);
  if(STATE.q)    url.searchParams.set('q', STATE.q);
  const r = await fetch(url, {cache:'no-store'});
  const arr = await r.json();
  $("#grid").innerHTML = arr.map(card).join("") || `<div class="muted">ยังไม่มีไกด์</div>`;
  closePanel();
}

function closePanel(){
  if(openPanel){ openPanel.remove(); openPanel = null; }
  if(selected){ selected.classList.remove('is-selected'); selected=null; }
}
function insertAfterRowEnd(container, card, panel){
  const rowTop = card.offsetTop;
  const items = [...container.children].filter(el=>el.classList.contains('card'));
  let last = card;
  for(const el of items){ if(el.offsetTop===rowTop) last = el; }
  last.insertAdjacentElement('afterend', panel);
}

async function openDetail(card){
  const id = card.dataset.id;
  if(openPanel && openPanel.dataset.forId===id){ closePanel(); return; }
  closePanel();

  const r = await fetch('guide_detail.php?id='+id, {cache:'no-store'});
  if(!r.ok) return;
  const d = await r.json();

  const panel = document.createElement('div');
  panel.className='detail-panel';
  panel.dataset.forId = id;
  panel.innerHTML = `
    <div class="detail">
      <div class="detail-head">
        <div>
          <div class="font-black text-lg">${d.title}</div>
          <div class="text-sm muted">${d.mode} · โดย ${d.author_name} · ${new Date(d.created_at).toLocaleString('th-TH')}</div>
        </div>
        <button class="detail-close">ปิด</button>
      </div>
      <div class="detail-body">
        ${d.image1?`<p><img src="${fix(d.image1)}" alt=""></p>`:''}
        ${d.image2?`<p class="mt-2"><img src="${fix(d.image2)}" alt=""></p>`:''}
        <div class="mt-3" style="white-space:pre-wrap">${d.body||''}</div>
      </div>
    </div>`;
  insertAfterRowEnd($("#grid"), card, panel);
  openPanel = panel;
  selected = card; card.classList.add('is-selected');
  panel.querySelector('.detail-close')?.addEventListener('click', closePanel, {once:true});
  panel.scrollIntoView({behavior:"smooth", block:"start"});
}

$("#modeChips").addEventListener('click', (e)=>{
  const b=e.target.closest('.chip'); if(!b) return;
  $$("#modeChips .chip").forEach(x=>x.classList.remove('active'));
  b.classList.add('active');
  STATE.mode = b.dataset.mode || "";
  load();
});
$("#q").addEventListener('input', (e)=>{ STATE.q=(e.target.value||"").trim(); load(); });

$("#grid").addEventListener('click', (e)=>{
  const c=e.target.closest('.card'); if(!c) return;
  openDetail(c);
});

document.addEventListener('click', (e)=>{
  if(!openPanel) return;
  const inside = openPanel.contains(e.target);
  const onCard = e.target.closest('.card');
  const onChip = e.target.closest('#modeChips');
  if(!inside && !onCard && !onChip) closePanel();
});
document.addEventListener('keydown', (e)=>{ if(e.key==='Escape') closePanel(); });

load();
