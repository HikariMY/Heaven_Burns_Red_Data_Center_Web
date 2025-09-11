// buffs.js — สำหรับหน้า buffs.html (ผู้ใช้)
(() => {
  const $ = (s, r = document) => r.querySelector(s);
  const $$ = (s, r = document) => Array.from(r.querySelectorAll(s));
  const debounce = (fn, ms = 300) => { let t; return (...a)=>{clearTimeout(t); t=setTimeout(()=>fn(...a),ms);} };

  // ปรับพาธรูปให้ใช้ได้จริง
  function fixIcon(p) {
    if (!p) return "uploads/buffs/placeholder.png";
    if (/^https?:\/\//i.test(p)) return p;
    if (p.startsWith("uploads/")) return p;
    return "uploads/buffs/" + p.replace(/^\/+/, "");
  }

  // สถานะฟิลเตอร์
  const state = { type: "", cat: "", dur: "", q: "" };
  let all = [];   // data ทั้งหมด
  let view = [];  // หลังฟิลเตอร์

  // ========== โหลดข้อมูล ==========
  async function fetchBuffs() {
    try {
      // ถ้าอยากให้ฟิลเตอร์ที่ server ให้ประกอบ query จาก state ได้
      const r = await fetch("buffs.php", { cache: "no-store" }); // <-- แก้พาธถ้าเก็บ API ไว้ที่อื่น
      if (!r.ok) throw new Error(`HTTP ${r.status}: ${await r.text()}`);
      const arr = await r.json();
      if (!Array.isArray(arr)) return [];
      return arr.map(it => ({
        id: String(it.id),
        name_th: it.name_th || "-",
        icon: fixIcon(it.icon || ""),
        type: String(it.type || "").toLowerCase(),
        category: String(it.category || ""),
        duration_kind: String(it.duration_kind || "").toLowerCase(),
        duration_value: it.duration_value == null ? "" : String(it.duration_value),
        trigger: String(it.trigger || ""),
        description: String(it.description || ""),
        tags: String(it.tags || "")
      }));
    } catch (e) {
      console.error("fetchBuffs:", e);
      $("#buffGrid").innerHTML = `<div class="muted">โหลดข้อมูลไม่สำเร็จ (ดู Console เพื่อรายละเอียด)</div>`;
      return [];
    }
  }

  // ========== ฟิลเตอร์ฝั่ง client ==========
  function applyFilter() {
    view = all.filter(x =>
      (state.type ? x.type === state.type : true) &&
      (state.cat  ? x.category === state.cat  : true) &&
      (state.dur  ? x.duration_kind === state.dur : true) &&
      (state.q    ? (x.name_th+x.description+x.tags).toLowerCase().includes(state.q) : true)
    );
  }

  // ========== เรนเดอร์ chip ของหมวด ==========
  function renderCategoryChips() {
    const host = $("#catChips");
    const cats = Array.from(new Set(all.map(x => x.category).filter(Boolean))).sort((a,b)=>a.localeCompare(b,'th'));
    host.innerHTML = `<button class="chip ${state.cat===""?"active":""}" data-cat="">ทั้งหมด</button>` +
      cats.map(c=>`<button class="chip ${state.cat===c?"active":""}" data-cat="${c}">${c}</button>`).join("");
    host.onclick = (e) => {
      const btn = e.target.closest("button[data-cat]");
      if (!btn) return;
      state.cat = btn.dataset.cat;
      $$("#catChips .chip").forEach(b=>b.classList.toggle("active", b===btn));
      applyFilter(); renderGrid();
    };
  }

  // ========== เรนเดอร์กริด ==========
  function renderGrid() {
    const g = $("#buffGrid");
    if (!view.length) { g.innerHTML = `<div class="muted">ไม่พบรายการ</div>`; return; }
    g.innerHTML = view.map(x => `
      <article class="buff-card" data-id="${x.id}">
        <div class="icon-box"><img src="${x.icon}" alt=""></div>
        <div class="label-bar">${x.name_th}</div>
        <div class="mini mt-2 text-center">${x.type==='debuff'?'ดีบัพ':'บัพ'} · ${x.category||'-'}</div>
        <div class="mini text-center">ระยะ: ${x.duration_kind}${x.duration_value?` · ${x.duration_value}`:''}</div>
      </article>
    `).join("");

    // คลิกเพื่อดูรายละเอียด
    $$("#buffGrid .buff-card").forEach(card=>{
      card.addEventListener("click", () => openDetail(card.dataset.id, card));
    });
  }

  // ========== แผงรายละเอียด ==========
  async function openDetail(id, card) {
    // toggle
    const current = $("#panelHost .detail-panel");
    if (current && current.dataset.id === id) { current.remove(); card.classList.remove("is-selected"); return; }
    if (current) current.previousElem?.classList?.remove?.("is-selected");
    $$("#buffGrid .buff-card").forEach(c=>c.classList.remove("is-selected"));
    card.classList.add("is-selected");

    // โหลดรายละเอียด
    try {
      const r = await fetch(`buff_detail.php?id=${encodeURIComponent(id)}`, { cache: "no-store" });
      if (!r.ok) throw new Error(await r.text());
      const d = await r.json();

      // สร้าง DOM
      const host = $("#panelHost");
      host.innerHTML = `
        <div class="detail-panel" data-id="${id}">
          <div class="detail">
            <div class="detail-head">
              <div class="font-bold">${d.name_th}</div>
              <button class="detail-close">ปิด</button>
            </div>
            <div class="mini muted mb-2">${d.type==='debuff'?'ดีบัพ':'บัพ'} · ${d.category||'-'} · ระยะ: ${d.duration_kind}${d.duration_value?` · ${d.duration_value}`:''}</div>
            <p class="mb-2">${d.description||''}</p>
            ${Array.isArray(d.effects)&&d.effects.length?`
              <table>
                <thead><tr><th>ชื่อ</th><th>ค่า</th><th>หมายเหตุ</th></tr></thead>
                <tbody>
                  ${d.effects.map(e=>`
                    <tr><td>${e.title||''}</td><td>${e.value||''}</td><td>${e.note||''}</td></tr>
                  `).join("")}
                </tbody>
              </table>` : `<div class="muted">ไม่มีรายละเอียดผลย่อย</div>`}
          </div>
        </div>
      `;
      $(".detail-close").onclick = ()=>{ $("#panelHost").innerHTML=""; card.classList.remove("is-selected"); };
    } catch (e) {
      console.error("detail:", e);
      alert("โหลดรายละเอียดไม่สำเร็จ");
    }
  }

  // ========== bind chips & search ==========
  function bindFilters() {
    // ประเภท
    $("#typeChips").onclick = (e)=>{
      const btn = e.target.closest("button[data-type]");
      if (!btn) return;
      state.type = btn.dataset.type;
      $$("#typeChips .chip").forEach(b=>b.classList.toggle("active", b===btn));
      applyFilter(); renderGrid();
    };
    // ระยะเวลา
    $("#durChips").onclick = (e)=>{
      const btn = e.target.closest("button[data-dur]");
      if (!btn) return;
      state.dur = btn.dataset.dur;
      $$("#durChips .chip").forEach(b=>b.classList.toggle("active", b===btn));
      applyFilter(); renderGrid();
    };
    // ค้นหา
    const onSearch = debounce((e)=>{
      state.q = (e.target.value || "").trim().toLowerCase();
      applyFilter(); renderGrid();
    }, 250);
    $("#q").addEventListener("input", onSearch);
  }

  // ========== start ==========
  document.addEventListener("DOMContentLoaded", async () => {
    all = await fetchBuffs();
    applyFilter();
    renderCategoryChips();
    renderGrid();
    bindFilters();
  });
})();
