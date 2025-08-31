(function(){
  const badge = document.getElementById('notifBadge');
  const list = document.getElementById('notifList');
  const markAllBtn = document.getElementById('notifMarkAll');
  const audio = new Audio('/assets/sounds/notification.wav');
  audio.preload = 'auto';

  // Debug UI: small dot near bell showing poll status
  let dbg = document.getElementById('notifDebug');
  if (!dbg && badge && badge.parentElement) {
    dbg = document.createElement('span');
    dbg.id = 'notifDebug';
    dbg.style.cssText = 'position:absolute; right:-6px; bottom:-4px; width:8px; height:8px; border-radius:50%; background:#cbd5e1;';
    badge.parentElement.appendChild(dbg);
  }
  const debugEnabled = (localStorage.getItem('NOTIF_DEBUG') === '1');

  let audioUnlocked = false;
  function unlockAudio(){
    if (!audioUnlocked){
      audio.play().then(()=>{ audio.pause(); audio.currentTime=0; audioUnlocked=true; }).catch(()=>{});
    }
  }
  document.addEventListener('click', unlockAudio, {once:true});
  document.addEventListener('keydown', unlockAudio, {once:true});

  let lastCount = parseInt(badge?.dataset.count || '0', 10);

  async function poll(force=false) {
    try {
      dbg && (dbg.style.background = '#eab308'); // amber - fetching
      const resp = await fetch('/notifications/poll.php?ts=' + Date.now(), {credentials:'same-origin', cache:'no-store'});
      const ct = resp.headers.get('content-type') || '';
      const data = ct.includes('application/json') ? await resp.json() : {ok:false, error:'non-json response', raw: await resp.text()};
      if (debugEnabled) console.log('[notifs] poll data:', data);
      if (!data || data.ok === false) throw new Error(data && data.error ? data.error : 'poll failed');
      const count = data.count || 0;

      if (badge) {
        badge.dataset.count = String(count);
        if (count > 0) { badge.textContent = count; badge.style.display='inline-flex'; }
        else { badge.textContent=''; badge.style.display='none'; }
      }
      if (list && Array.isArray(data.items)) {
        list.innerHTML='';
        data.items.forEach(it=>{
          const a = document.createElement('a');
          a.className = 'block px-3 py-2 hover:bg-gray-50 transition rounded';
          a.href = it.url || '#';
          a.textContent = it.title || 'Уведомление';
          list.appendChild(a);
        });
      }
      if ((count > lastCount && count !== 0) || (force && count > 0)) {
        try { audio.currentTime = 0; audio.play().catch(()=>{}); } catch(e){}
      }
      lastCount = count;
      dbg && (dbg.style.background = '#10b981'); // green ok
    } catch(e){
      if (debugEnabled) console.warn('[notifs] poll error:', e && e.message ? e.message : e);
      dbg && (dbg.style.background = '#ef4444'); // red error
    }
  }

  async function markAll() {
    try {
      const resp = await fetch('/notifications/mark_all_read.php', {method:'POST', credentials:'same-origin', headers:{'Content-Type':'application/x-www-form-urlencoded'}, body:'csrf_token='+(window.CSRF_TOKEN||'')});
      if (resp.ok) {
        lastCount = 0;
        if (badge) { badge.textContent = ''; badge.style.display = 'none'; badge.dataset.count='0'; }
        if (list) { list.innerHTML = ''; }
      }
    } catch(e){}
  }

  if (markAllBtn) markAllBtn.addEventListener('click', function(ev){ ev.preventDefault(); markAll(); });

  setTimeout(()=>poll(true), 800);
  setInterval(poll, 5000);
  document.addEventListener('visibilitychange', ()=> { if (!document.hidden) poll(); });
})();