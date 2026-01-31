(function () {
  window.App = window.App || {};

  window.App.upload = function (url, file, onProgress) {
    var xhr = new XMLHttpRequest();
    xhr.open('POST', url, true);
    xhr.upload.onprogress = function (event) {
      if (event.lengthComputable && onProgress) {
        onProgress(Math.round((event.loaded / event.total) * 100));
      }
    };
    var formData = new FormData();
    formData.append('file', file);
    xhr.send(formData);
  };
})();
