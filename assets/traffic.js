(() => {
  const video=document.querySelector('#trafficVideo'), state=document.querySelector('#trafficState');
  const rows=document.querySelector('#trafficRows'), count=document.querySelector('#trafficCount');
  const button=document.querySelector('#analyzeAgain');
  const escapeHtml=v=>String(v??'').replace(/[&<>'"]/g,c=>({'&':'&amp;','<':'&lt;','>':'&gt;',"'":'&#39;','"':'&quot;'}[c]));
  const videoTime=seconds=>`${String(Math.floor(seconds/60)).padStart(2,'0')}:${String(Math.floor(seconds%60)).padStart(2,'0')}`;
  async function analyze(){
    button.disabled=true; video.pause(); video.hidden=true; state.hidden=false;
    state.innerHTML='<div class="radar"></div><h2>YOLO analizira snimak…</h2><p>Pracenje vozila, ocitavanje tablica i racunanje brzine moze potrajati nekoliko minuta.</p>';
    rows.innerHTML='<tr><td colspan="6">Obrada videa je u toku…</td></tr>';
    try{
      const response=await fetch(window.trafficPage.analyzeUrl,{method:'POST',headers:{'Content-Type':'application/json'},body:JSON.stringify({distance_m:+document.querySelector('#distance').value,speed_limit:+document.querySelector('#limit').value})});
      const data=await response.json(); if(!response.ok||!data.ok) throw new Error(data.error||'Analiza nije uspela');
      await fetch('api/traffic-save.php',{method:'POST',headers:{'Content-Type':'application/json'},body:JSON.stringify({events:data.events})});
      video.src=data.result_url+'?t='+Date.now(); video.hidden=false; state.hidden=true; video.play().catch(()=>{});
      count.textContent=data.events.length+' vozila';
      rows.innerHTML=data.events.length?data.events.map(e=>`<tr><td>${videoTime(e.video_time)}</td><td>${escapeHtml(e.vehicle_type)}</td><td><b>${escapeHtml(e.plate)}</b></td><td><b>${Number(e.speed_kmh).toFixed(1)} km/h</b></td><td>${e.speed_limit} km/h</td><td><span class="pill ${e.status==='PREKORACENJE'?'red':'green'}">${escapeHtml(e.status)}</span></td></tr>`).join(''):'<tr><td colspan="6">Nijedno vozilo nije preslo obe kontrolne linije.</td></tr>';
    }catch(error){
      state.innerHTML=`<h2>KAMERA NIJE POKRENUTA</h2><p>${escapeHtml(error.message)}</p><small>Ubacite video kao assets/videos/nadzorna-kamera.mp4 i proverite Python servis.</small>`;
      rows.innerHTML='<tr><td colspan="6">Nema rezultata.</td></tr>';
    }finally{button.disabled=false;}
  }
  button.addEventListener('click',analyze); analyze();
})();
