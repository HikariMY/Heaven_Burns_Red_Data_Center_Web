(function(){
  const $  = (s, r=document)=>r.querySelector(s);
  const $$ = (s, r=document)=>Array.from(r.querySelectorAll(s));
  const fix = (p)=>!p ? "" :
    (/^https?:\/\//i.test(p) || p.startsWith("/") || p.startsWith("uploads/") || p.startsWith("img/")) ? p
    : "uploads/events/" + p.replace(/^\/+/, "");

  const segFilter = $("#segFilter");
  const qInput    = $("#q");
  const ongoingWrap = $("#ongoingWrap");
  const upcomingWrap = $("#upcomingWrap");
  const ongoingGrid = $("#ongoingGrid");
  const upcomingGrid = $("#upcomingGrid");
  const newsGrid  = $("#newsGrid");
  const panelHost = $("#panelHost");
  const totalInfo = $("#totalInfo");

  let openPanel = null;
  let selectedCard = null;

  let ALL = [];      // ดิบจาก API
  let ongoing = [];  // วันนี้อยู่ในช่วง
  let upcoming = []; // ยังไม่ถึงวันเริ่ม
  let past = [];     // หมดช่วงไปแล้ว / ข่าวทั่วไปที่จบแล้ว

  let segActive = ""; // "", "ongoing", "upcoming", "past"
  let q = "";

  function fmtDate(s){
    if(!s) return "";
    try{
      const d = new Date(s);
      if (Number.isNaN(d.getTime())) return s;
      const pad = n => String(n).padStart(2,"0");
      return `${d.getFullYear()}-${pad(d.getMonth()+1)}-${pad(d.getDate())}`;
    }catch(_){ return s; }
  }
  function isOngoing(it, today){
    const s = it.start ? new Date(it.start) : null;
    const e = it.end ? new Date(it.end) : null;
    if (s && today < s) return false;      // ยังไม่เริ่ม
    if (e && today > e) return false;      // จบแล้ว
    return !!(s || e);                     // ต้องมีอย่างใดอย่างหนึ่ง
  }
  function isUpcoming(it, today){
    const s = it.start ? new Date(it.start) : null;
    return !!s && today < s;               // มี start และยังไม่ถึงวันนั้น
  }
  function isPast(it, today){
    const e = it.end ? new Date(it.end) : null;
    if (e) return today > e;
    // ไม่มี end: ถ้ามี start และวันนี้เลยช่วงไปแล้วถือไม่ใช่ past (จะจัด ongoing/upcoming ไปแล้ว)
    // ข่าวที่ไม่มีทั้ง start/end => past/ทั่วไป
    return !(it.start || it.end);
  }

  function card(it){
    const during = [fmtDate(it.start), fmtDate(it.end)].filter(Boolean).join(" — ");
    const badge = it.__seg==="ongoing"
      ? `<span class="live-badge">LIVE</span>`
      : it.__seg==="upcoming"
      ? `<span class="upcoming-badge">SOON</span>`
      : "";
    return `
      <article class="card" data-id="${it.id}">
        <div class="cover">
          ${it.image ? `<img src="${fix(it.image)}" alt="${it.title}">` : ""}
          ${badge}
        </div>
        <div class="body">
          <div class="ttl">${it.title}</div>
          <div class="meta">${during || "-"}</div>
        </div>
      </article>
    `;
  }

  function setSelected(card){
    if (selectedCard) selectedCard.classList.remove("is-selected");
    selectedCard = card || null;
    if (selectedCard) selectedCard.classList.add("is-selected");
  }
  function closePanel(){
    if (openPanel) openPanel.remove();
    openPanel = null;
    setSelected(null);
  }
  function insertAfterRowEnd(container, card, panel, itemClass){
    const rowTop = card.offsetTop;
    const items = [...container.children].filter(x => x.classList && x.classList.contains(itemClass));
    let rowLast = card;
    for (const el of items) if (el.offsetTop === rowTop) rowLast = el;
    rowLast.insertAdjacentElement("afterend", panel);
  }
  async function openDetailUnder(container, card, itemClass){
    const id = card.dataset.id;
    if (openPanel && openPanel.dataset.forId === id) { closePanel(); return; }
    closePanel();
    const store = card.__data || JSON.parse(card.getAttribute("data-json") || "{}");
    const panel = document.createElement("div");
    panel.className = "panel";
    panel.dataset.forId = id;
    panel.innerHTML = `
      <div class="detail">
        <div class="detail-head">
          <div class="font-bold">${store.title || ""}</div>
          <button class="detail-close" type="button">ปิด</button>
        </div>
        ${store.image ? `<img src="${fix(store.image)}" alt="" style="width:100%;max-height:360px;object-fit:cover;border-radius:10px;border:1px solid #eee">` : ""}
        <div class="muted" style="margin-top:6px">${[fmtDate(store.start), fmtDate(store.end)].filter(Boolean).join(" — ") || "-"}</div>
        <div style="margin-top:8px;white-space:pre-line">${store.description_text || ""}</div>
      </div>
    `;
    insertAfterRowEnd(container, card, panel, itemClass);
    openPanel = panel;
    setSelected(card);
    panel.querySelector(".detail-close").addEventListener("click", closePanel, {once:true});
    panel.scrollIntoView({behavior:"smooth", block:"start"});
  }
  function bindCardClicks(container, itemClass){
    container.addEventListener("click", e=>{
      const c = e.target.closest("."+itemClass);
      if (!c) return;
      openDetailUnder(container, c, itemClass);
    });
  }

  function textMatch(it){
    if (!q) return true;
    const hay = [it.title||"", it.description_text||""].join(" ").toLowerCase();
    return hay.includes(q);
  }

  function render(){
    // filter by segment + text
    const og = ongoing.filter(textMatch);
    const up = upcoming.filter(textMatch);
    const ps = past.filter(textMatch);

    // toggle section by segActive
    const showO = !segActive || segActive==="ongoing";
    const showU = !segActive || segActive==="upcoming";
    const showP = !segActive || segActive==="past";

    ongoingWrap.style.display = (showO && og.length) ? "" : "none";
    upcomingWrap.style.display = (showU && up.length) ? "" : "none";

    ongoingGrid.innerHTML  = og.map(it => card(it).replace('<article class="card"', `<article class="card og-item" data-json='${JSON.stringify(it).replace(/'/g,"&#39;")}'`)).join("");
    upcomingGrid.innerHTML = up.map(it => card(it).replace('<article class="card"', `<article class="card up-item" data-json='${JSON.stringify(it).replace(/'/g,"&#39;")}'`)).join("");
    newsGrid.innerHTML     = (showP ? ps : []).map(it => card(it).replace('<article class="card"', `<article class="card news-item" data-json='${JSON.stringify(it).replace(/'/g,"&#39;")}'`)).join("");

    $$(".og-item", ongoingGrid).forEach((el,i)=>{ el.__data = og[i]; });
    $$(".up-item", upcomingGrid).forEach((el,i)=>{ el.__data = up[i]; });
    $$(".news-item", newsGrid).forEach((el,i)=>{ el.__data = (showP ? ps : [])[i]; });

    totalInfo.textContent = `ทั้งหมด ${ALL.length} · ดำเนินอยู่ ${ongoing.length} · กำลังจะมา ${upcoming.length} · ที่ผ่านมา ${past.length}`;
  }

  function bindGlobal(){
    // bind filters
    segFilter.addEventListener("click", e=>{
      const btn = e.target.closest(".chip"); if(!btn) return;
      $$(".chip", segFilter).forEach(x=>x.classList.remove("active"));
      btn.classList.add("active");
      segActive = btn.dataset.seg || "";
      closePanel();
      render();
    });
    qInput.addEventListener("input", e=>{
      q = (e.target.value || "").trim().toLowerCase();
      closePanel();
      render();
    });

    // click outside to close panel
    document.addEventListener("click", e=>{
      if(!openPanel) return;
      const inside = openPanel.contains(e.target);
      const onCard = e.target.closest(".og-item,.up-item,.news-item");
      if(!inside && !onCard) closePanel();
    });
    document.addEventListener("keydown", e=>{ if(e.key==="Escape") closePanel(); });

    bindCardClicks(ongoingGrid, "og-item");
    bindCardClicks(upcomingGrid, "up-item");
    bindCardClicks(newsGrid, "news-item");
  }

  async function load(){
    try{
      const r = await fetch("events.php", {cache:"no-store"});
      if(!r.ok) throw new Error(await r.text());
      const raw = await r.json();
      const today = new Date(); today.setHours(0,0,0,0);

      ALL = (Array.isArray(raw)?raw:[]).map(x => ({
        ...x,
        start: x.start || x.start_date || null,
        end  : x.end   || x.end_date   || null,
      }));

      ongoing = [];
      upcoming = [];
      past = [];
      for(const it of ALL){
        if (isUpcoming(it, today))      upcoming.push({...it, __seg:"upcoming"});
        else if (isOngoing(it, today))  ongoing.push({...it, __seg:"ongoing"});
        else if (isPast(it, today))     past.push({...it, __seg:"past"});
        else                            past.push({...it, __seg:"past"}); // safety
      }

      bindGlobal();
      render();
    }catch(e){
      console.error(e);
      ongoingWrap.style.display = "none";
      upcomingWrap.style.display = "none";
      newsGrid.innerHTML = `<div class="muted">โหลดข้อมูลไม่สำเร็จ</div>`;
    }
  }

  load();
})();
