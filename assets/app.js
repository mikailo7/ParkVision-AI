if (window.scanPage) {
  const input = document.querySelector('#image');
  const preview = document.querySelector('#preview');
  const button = document.querySelector('#scanBtn');
  const result = document.querySelector('#result');
  input.addEventListener('change', () => {
    if (!input.files[0]) return;
    const img = document.createElement('img');
    img.src = URL.createObjectURL(input.files[0]);
    preview.innerHTML = ''; preview.appendChild(img);
  });
  button.addEventListener('click', async () => {
    const fd = new FormData();
    if (input.files[0]) fd.append('image', input.files[0]);
    fd.append('manual_plate', document.querySelector('#manualPlate').value);
    button.disabled = true; button.textContent = 'AI analizira fotografiju…';
    result.className = 'card result-card';
    result.innerHTML = '<div class="radar"></div><p>Prepoznavanje tablice i provera baze…</p>';
    try {
      const response = await fetch('api/scan.php', {method: 'POST', body: fd});
      const data = await response.json();
      if (!data.ok) throw new Error(data.error || 'Greška sistema');
      const granted = data.decision === 'GRANTED';
      result.classList.add(granted ? 'granted' : 'denied');
      const annotated = data.annotated_image ? `<img style="display:block;width:100%;max-height:220px;object-fit:contain;border-radius:10px;margin:0 auto 18px" src="${data.annotated_image}" alt="YOLO detekcija tablice">` : '';
      const metrics = data.ai_used ? `<div style="display:flex;justify-content:center;gap:16px;flex-wrap:wrap"><small>YOLO detekcija: ${(data.yolo_confidence*100).toFixed(1)}%</small><small>OCR čitanje: ${(data.confidence*100).toFixed(1)}%</small></div>` : '<small>Tablica uneta ručno — demo režim</small>';
      result.innerHTML = `<div>${annotated}<span class="plate">${escapeHtml(data.plate)}</span><h2>${granted ? 'PRISTUP ODOBREN' : 'PRISTUP ODBIJEN'}</h2><p>${escapeHtml(data.reason)}</p>${metrics}</div>`;
      refreshEvents();
    } catch (e) { result.classList.add('denied'); result.innerHTML = `<div><h2>NIJE USPELO</h2><p>${escapeHtml(e.message)}</p></div>`; }
    finally { button.disabled = false; button.textContent = 'Prepoznaj i proveri pristup'; }
  });
}
async function refreshEvents(){const r=await fetch('api/events.php');const d=await r.json();if(!d.ok)return;document.querySelector('#events').innerHTML=d.events.map(x=>`<tr><td>${escapeHtml(x.created_at)}</td><td><b>${escapeHtml(x.plate)}</b></td><td>${x.confidence!==null?(x.confidence*100).toFixed(1)+'%':'—'}</td><td><span class="pill ${x.decision==='GRANTED'?'green':'red'}">${x.decision}</span></td><td>${escapeHtml(x.reason)}</td></tr>`).join('')}
function escapeHtml(v){return String(v??'').replace(/[&<>'"]/g,c=>({'&':'&amp;','<':'&lt;','>':'&gt;',"'":'&#39;','"':'&quot;'}[c]))}
