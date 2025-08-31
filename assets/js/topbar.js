(function(){
  const burger = document.getElementById('topbarBurger');
  const drawer = document.getElementById('topbarDrawer');
  if (!burger || !drawer) return;
  const show = ()=> drawer.classList.remove('hidden');
  const hide = (e)=> { if (!e || e.target.hasAttribute('data-close')) drawer.classList.add('hidden'); };
  burger.addEventListener('click', show);
  drawer.addEventListener('click', hide);
})();