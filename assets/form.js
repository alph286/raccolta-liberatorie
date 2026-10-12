(function () {
  var canvas = document.getElementById('firma-canvas');
  if (!canvas) return;
  var ctx = canvas.getContext('2d'), drawing = false, dirty = false;
  ctx.lineWidth = 3; ctx.lineCap = 'round'; ctx.lineJoin = 'round'; ctx.strokeStyle = '#000';

  function pos(e) {
    var r = canvas.getBoundingClientRect();
    return { x: (e.clientX - r.left) * canvas.width / r.width, y: (e.clientY - r.top) * canvas.height / r.height };
  }
  canvas.addEventListener('pointerdown', function (e) {
    drawing = true; canvas.setPointerCapture(e.pointerId);
    var p = pos(e); ctx.beginPath(); ctx.moveTo(p.x, p.y); ctx.lineTo(p.x + 0.1, p.y + 0.1); ctx.stroke(); dirty = true;
  });
  canvas.addEventListener('pointermove', function (e) {
    if (!drawing) return; var p = pos(e); ctx.lineTo(p.x, p.y); ctx.stroke();
  });
  ['pointerup', 'pointercancel', 'pointerleave'].forEach(function (ev) {
    canvas.addEventListener(ev, function () { drawing = false; });
  });
  document.getElementById('firma-clear').addEventListener('click', function () {
    ctx.clearRect(0, 0, canvas.width, canvas.height); dirty = false;
    document.getElementById('firma-data').value = '';
  });
  document.getElementById('liberatoria').addEventListener('submit', function (e) {
    document.getElementById('firma-data').value = dirty ? canvas.toDataURL('image/png') : '';
    if (!dirty) { e.preventDefault(); alert('Per favore firma nel riquadro prima di inviare.'); }
  });
})();
