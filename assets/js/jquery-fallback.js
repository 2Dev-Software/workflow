(function () {
  if (window.jQuery) {
    return;
  }
  var script = document.createElement('script');
  script.src = 'assets/js/vendor/jquery-3.7.1.min.js';
  script.defer = true;
  document.head.appendChild(script);
})();
