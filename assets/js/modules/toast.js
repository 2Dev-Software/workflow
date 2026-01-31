(function () {
  window.App = window.App || {};
  var container = document.querySelector('[data-toast-container]') || document.getElementById('toast-container');

  function show(message, type) {
    if (!container) {
      return;
    }
    var toast = document.createElement('div');
    toast.className = 'c-toast';
    toast.textContent = message;
    if (type) {
      toast.style.background = type === 'error' ? '#d64545' : '#132237';
    }
    container.appendChild(toast);
    setTimeout(function () {
      toast.remove();
    }, 3000);
  }

  window.App.toast = {
    show: show,
  };

  document.addEventListener('click', function (event) {
    var trigger = event.target.closest('[data-toast]');
    if (!trigger) {
      return;
    }
    var type = trigger.getAttribute('data-toast');
    var message = trigger.getAttribute('data-toast-message') || 'ดำเนินการเรียบร้อยแล้ว';
    show(message, type);
  });
})();
