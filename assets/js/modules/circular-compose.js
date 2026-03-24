(function () {
  var form = document.getElementById('circularTrackFilterForm');
  if (!form) {
    return;
  }

  var trackSectionSelector = '#circularTrack';
  var trackResultsSelector = '#circularTrackResults';
  var loadingApi = window.App && window.App.loading ? window.App.loading : null;
  var isTrackRequestInFlight = false;
  var trackRequestToken = 0;
  var pendingTrackRequest = null;
  var trackSearchTimer = null;
  var isTrackSearchComposing = false;

  var getTrackSection = function () {
    return document.querySelector(trackSectionSelector);
  };

  var getTrackResults = function () {
    return document.querySelector(trackResultsSelector);
  };

  var buildTrackRequestUrl = function () {
    var params = new URLSearchParams();
    var searchInput = form.querySelector('input[name="q"]');
    var statusSelect = form.querySelector('select[name="status"]');
    var sortSelect = form.querySelector('select[name="sort"]');
    var searchValue = searchInput ? String(searchInput.value || '') : '';
    var statusValue = statusSelect ? String(statusSelect.value || 'all') : 'all';
    var sortValue = sortSelect ? String(sortSelect.value || 'newest') : 'newest';

    params.set('tab', 'track');
    params.set('status', statusValue !== '' ? statusValue : 'all');
    params.set('sort', sortValue !== '' ? sortValue : 'newest');

    if (searchValue !== '') {
      params.set('q', searchValue);
    }

    var action = form.getAttribute('action') || window.location.pathname;
    var query = params.toString();
    return query === '' ? action : action + '?' + query;
  };

  var applyTrackSectionUpdate = function (htmlText, requestUrl) {
    var parser = new DOMParser();
    var nextDocument = parser.parseFromString(htmlText, 'text/html');
    var currentTrackResults = getTrackResults();
    var nextTrackResults = nextDocument.querySelector(trackResultsSelector);

    if (!currentTrackResults || !nextTrackResults) {
      window.location.assign(requestUrl);
      return;
    }

    currentTrackResults.replaceWith(nextTrackResults);
    window.history.replaceState({}, '', requestUrl);
  };

  var submitTrackFilter = function (options) {
    options = options || {};
    var targetUrl = options.requestUrl || buildTrackRequestUrl();

    if (
      targetUrl === '' ||
      typeof window.fetch !== 'function' ||
      typeof window.DOMParser !== 'function'
    ) {
      form.submit();
      return;
    }

    if (isTrackRequestInFlight) {
      pendingTrackRequest = { requestUrl: targetUrl };
      return;
    }

    isTrackRequestInFlight = true;
    trackRequestToken += 1;
    var currentToken = trackRequestToken;

    if (loadingApi) {
      loadingApi.startComponent(getTrackResults() || getTrackSection());
    }

    window
      .fetch(targetUrl, {
        headers: {
          'X-Requested-With': 'XMLHttpRequest',
        },
        credentials: 'same-origin',
      })
      .then(function (response) {
        if (!response.ok) {
          throw new Error('Failed to fetch circular track list');
        }
        return response.text();
      })
      .then(function (htmlText) {
        if (currentToken !== trackRequestToken) {
          return;
        }

        applyTrackSectionUpdate(htmlText, targetUrl);
      })
      .catch(function () {
        window.location.assign(targetUrl);
      })
      .finally(function () {
        if (loadingApi) {
          loadingApi.stopComponent(getTrackResults() || getTrackSection());
        }

        if (currentToken === trackRequestToken) {
          isTrackRequestInFlight = false;
        }

        if (pendingTrackRequest !== null) {
          var nextRequest = pendingTrackRequest;
          pendingTrackRequest = null;
          submitTrackFilter(nextRequest);
        }
      });
  };

  var queueTrackSearch = function () {
    if (isTrackSearchComposing) {
      return;
    }
    if (trackSearchTimer) {
      window.clearTimeout(trackSearchTimer);
    }
    trackSearchTimer = window.setTimeout(function () {
      submitTrackFilter();
    }, 450);
  };

  if (form.dataset.ajaxBound !== 'true') {
    form.dataset.ajaxBound = 'true';

    var searchInput = form.querySelector('input[name="q"]');
    var statusSelect = form.querySelector('select[name="status"]');
    var sortSelect = form.querySelector('select[name="sort"]');

    form.addEventListener('submit', function (event) {
      event.preventDefault();
      if (trackSearchTimer) {
        window.clearTimeout(trackSearchTimer);
      }
      submitTrackFilter();
    });

    searchInput && searchInput.addEventListener('input', queueTrackSearch);
    searchInput && searchInput.addEventListener('keyup', function (event) {
      if (event.key === 'Enter') {
        event.preventDefault();
        if (trackSearchTimer) {
          window.clearTimeout(trackSearchTimer);
        }
        submitTrackFilter();
        return;
      }

      queueTrackSearch();
    });

    searchInput && searchInput.addEventListener('search', function () {
      if (trackSearchTimer) {
        window.clearTimeout(trackSearchTimer);
      }
      submitTrackFilter();
    });

    searchInput && searchInput.addEventListener('keydown', function (event) {
      if (event.key !== 'Enter') {
        return;
      }

      event.preventDefault();
      if (trackSearchTimer) {
        window.clearTimeout(trackSearchTimer);
      }
      submitTrackFilter();
    });

    searchInput && searchInput.addEventListener('compositionstart', function () {
      isTrackSearchComposing = true;
    });

    searchInput && searchInput.addEventListener('compositionend', function () {
      isTrackSearchComposing = false;
      if (trackSearchTimer) {
        window.clearTimeout(trackSearchTimer);
      }
      trackSearchTimer = window.setTimeout(function () {
        submitTrackFilter();
      }, 450);
    });

    statusSelect && statusSelect.addEventListener('change', function () {
      if (trackSearchTimer) {
        window.clearTimeout(trackSearchTimer);
      }
      submitTrackFilter();
    });

    sortSelect && sortSelect.addEventListener('change', function () {
      if (trackSearchTimer) {
        window.clearTimeout(trackSearchTimer);
      }
      submitTrackFilter();
    });

    form.addEventListener('click', function (event) {
      var option = event.target.closest('.custom-option');
      if (!option || !form.contains(option)) {
        return;
      }

      var wrapper = option.closest('.custom-select-wrapper');
      var targetSelect = wrapper ? wrapper.querySelector('select') : null;
      var optionValue = String(option.dataset.value || '');

      window.setTimeout(function () {
        if (targetSelect && optionValue !== '') {
          targetSelect.value = optionValue;
        }
        if (trackSearchTimer) {
          window.clearTimeout(trackSearchTimer);
        }
        submitTrackFilter();
      }, 0);
    });
  }

  document.addEventListener('click', function (event) {
    var paginationLink = event.target.closest(trackSectionSelector + ' .c-pagination a[href]');
    if (!paginationLink) {
      return;
    }

    event.preventDefault();
    var href = paginationLink.getAttribute('href') || '';
    if (href === '') {
      return;
    }

    var absoluteUrl = new URL(href, window.location.href);
    submitTrackFilter({
      requestUrl: absoluteUrl.pathname + (absoluteUrl.search || ''),
    });
  });
})();
