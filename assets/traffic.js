(() => {
  const video=document.querySelector('#trafficVideo'), state=document.querySelector('#trafficState');
  const feed=document.querySelector('#liveFeed'), count=document.querySelector('#trafficCount');
  const button=document.querySelector('#analyzeAgain');
  let events=[], shown=new Set();
  const escapeHtml=v=>String(v??'').replace(/[&<>'"]/g,c=>({'&':'&amp;','<':'&lt;','>':'&gt;',"'":'&#39;','"':'&quot;'}[c]));
  const videoTime=s=>`${String(Math.floor(s/60)).padStart(2,'0')}:${String(Math.floor(s%60)).padStart(2,'0')}`;
  function resetFeed(){shown.clear();count.textContent='0';feed.innerHTML='<div class="feed-empty"><span>●</span><p>Čekanje prvog vozila…</p></div>'}
  function addEvent(event,index){
    if(shown.has(index))return; shown.add(index); if(shown.size===1)feed.innerHTML='';
    const over=event.status==='PREKORACENJE',item=document.createElement('article');
    item.className='feed-item'+(over?' over':'');
    item.innerHTML=`<div class="feed-top"><span class="feed-time">${videoTime(event.video_time)} · ${escapeHtml(event.vehicle_type)}</span><span class="pill ${over?'red':'green'}">${over?'PREKORAČENJE':'U REDU'}</span></div><div class="feed-plate">${escapeHtml(event.plate)}</div><div class="feed-bottom"><span class="feed-speed">${Number(event.speed_kmh).toFixed(1)} km/h</span><small>limit ${event.speed_limit} km/h</small></div>`;
    feed.prepend(item);count.textContent=String(shown.size);
  }
  video.addEventListener('timeupdate',()=>events.forEach((event,index)=>{if(event.video_time<=video.currentTime+.15)addEvent(event,index)}));
  video.addEventListener('seeking',()=>{if(video.currentTime<.5)resetFeed()});
  video.addEventListener('ended',()=>events.forEach((event,index)=>addEvent(event,index)));
  async function analyze(force=false){
    button.disabled=true;video.pause();video.hidden=true;state.hidden=false;resetFeed();
    state.innerHTML='<div class="radar"></div><h2>YOLO analizira snimak…</h2><p>Praćenje vozila, očitavanje tablica i računanje brzine može potrajati nekoliko minuta.</p>';
    try{
      const response=await fetch(window.trafficPage.analyzeUrl,{method:'POST',headers:{'Content-Type':'application/json'},body:JSON.stringify({distance_m:+document.querySelector('#distance').value,speed_limit:+document.querySelector('#limit').value,force})});
      const data=await response.json();if(!response.ok||!data.ok)throw new Error(data.error||'Analiza nije uspela');
      events=(data.events||[]).sort((a,b)=>a.video_time-b.video_time);
      video.src=data.result_url+'?t='+Date.now();video.hidden=false;state.hidden=true;video.currentTime=0;video.play().catch(()=>{});
    }catch(error){state.innerHTML=`<h2>KAMERA NIJE POKRENUTA</h2><p>${escapeHtml(error.message)}</p><small>Ubacite video kao assets/videos/nadzorna-kamera.mp4 i proverite Python servis.</small>`}
    finally{button.disabled=false}
  }
  button.addEventListener('click',()=>analyze(true));analyze(false);
})();