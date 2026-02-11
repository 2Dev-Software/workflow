(function () {
  window.App = window.App || {};

  function request(method, url, data) {
    var headers = {
      'X-Requested-With': 'XMLHttpRequest',
    };
    if (window.App.csrfToken) {
      headers['X-CSRF-Token'] = window.App.csrfToken;
    }

    var options = {
      method: method,
      headers: headers,
    };

    if (data) {
      headers['Content-Type'] = 'application/json';
      options.body = JSON.stringify(data);
    }

    return fetch(url, options).then(function (response) {
      return response.json();
    });
  }

  window.App.ajax = {
    get: function (url) {
      return request('GET', url);
    },
    post: function (url, data) {
      return request('POST', url, data);
    },
  };
})();
