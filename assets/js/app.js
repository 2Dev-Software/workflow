(function () {
  window.App = window.App || {};
  var tokenMeta = document.querySelector('meta[name="csrf-token"]');
  if (tokenMeta) {
    window.App.csrfToken = tokenMeta.getAttribute('content');
  }

  document.addEventListener('click', function (event) {
    var toggle = event.target.closest('[data-sidebar-toggle]');
    if (!toggle) {
      return;
    }
    var sidebar = document.querySelector('.layout-sidebar');
    if (!sidebar) {
      return;
    }
    sidebar.classList.toggle('is-open');
  });
})();
