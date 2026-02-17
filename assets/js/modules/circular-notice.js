(function () {
  var root = document.querySelector("[data-circular-notice]");
  if (!root) {
    return;
  }

  var filterForm = document.getElementById("circularFilterForm");
  if (filterForm) {
    document.querySelectorAll(".custom-select-wrapper").forEach(function (wrapper) {
      var targetId = wrapper.getAttribute("data-target") || "";
      var input = targetId ? document.getElementById(targetId) : null;
      var nativeSelect = wrapper.querySelector("select");
      var options = wrapper.querySelectorAll(".custom-option");
      var valueDisplay = wrapper.querySelector(".select-value");

      options.forEach(function (option) {
        option.addEventListener("click", function () {
          var value = option.getAttribute("data-value") || "";
          if (input) {
            input.value = value;
          }
          if (nativeSelect) {
            nativeSelect.value = value;
          }
          if (valueDisplay) {
            valueDisplay.textContent = option.textContent.trim();
          }
          if (input) {
            filterForm.submit();
          }
        });
      });
    });

    var typeCheckboxes = document.querySelectorAll("[data-filter-type]");
    if (typeCheckboxes.length) {
      var filterTypeInput = document.getElementById("filterTypeInput");
      var updateTypeFilter = function () {
        if (!filterTypeInput) return;
        var checked = Array.prototype.slice
          .call(typeCheckboxes)
          .filter(function (checkbox) {
            return checkbox.checked;
          })
          .map(function (checkbox) {
            return checkbox.value;
          });
        var value = "all";
        if (checked.length === 1) {
          value = checked[0] || "all";
        }
        filterTypeInput.value = value;
        filterForm.submit();
      };

      typeCheckboxes.forEach(function (checkbox) {
        checkbox.addEventListener("change", updateTypeFilter);
      });
    }

    var readCheckboxes = document.querySelectorAll("[data-filter-read]");
    if (readCheckboxes.length) {
      var filterReadInput = document.getElementById("filterReadInput");
      var updateReadFilter = function () {
        if (!filterReadInput) return;
        var checked = Array.prototype.slice
          .call(readCheckboxes)
          .filter(function (checkbox) {
            return checkbox.checked;
          })
          .map(function (checkbox) {
            return checkbox.value;
          });
        var value = "all";
        if (checked.length === 1) {
          value = checked[0] || "all";
        }
        filterReadInput.value = value;
        filterForm.submit();
      };

      readCheckboxes.forEach(function (checkbox) {
        checkbox.addEventListener("change", updateReadFilter);
      });
    }

    var searchInput = document.getElementById("search-input");
    if (searchInput) {
      searchInput.addEventListener("keydown", function (event) {
        if (event.key === "Enter") {
          event.preventDefault();
          filterForm.submit();
        }
      });
    }

    document.querySelectorAll(".table-change button[data-view]").forEach(function (button) {
      button.addEventListener("click", function () {
        var viewInput = document.getElementById("filterViewInput");
        if (viewInput) {
          viewInput.value = button.getAttribute("data-view") || "table1";
        }
        filterForm.submit();
      });
    });
  }

  var checkAll = document.getElementById("checkAllCircular");
  if (checkAll) {
    checkAll.addEventListener("change", function () {
      document
        .querySelectorAll(
          ".table-circular-notice-index .check-table:not(.checkall), .table-circular-notice-archive .check-table:not(.checkall)"
        )
        .forEach(function (checkbox) {
          checkbox.checked = checkAll.checked;
        });
    });
  }

  var modalOverlay = document.getElementById("modalNoticeKeepOverlay");
  var modalClose = document.getElementById("closeModalNoticeKeep");
  var fileSection = document.getElementById("modalFileSection");
  var modalLink = document.getElementById("modalLink");
  var modalArchiveId = document.getElementById("modalInboxId");
  var modalTypeLabel = document.getElementById("modalTypeLabel");
  var modalSubject = document.getElementById("modalSubject");
  var modalSender = document.getElementById("modalSender");
  var modalDate = document.getElementById("modalDate");
  var modalDetail = document.getElementById("modalDetail");

  var modalUrgency = document.getElementById("modalUrgency");
  var modalBookNo = document.getElementById("modalBookNo");
  var modalIssuedDate = document.getElementById("modalIssuedDate");
  var modalFromText = document.getElementById("modalFromText");
  var modalToText = document.getElementById("modalToText");
  var modalStatus = document.getElementById("modalStatus");
  var modalReceivedTime = document.getElementById("modalReceivedTime");
  var modalConsiderStatus = document.getElementById("modalConsiderStatus");
  var csrfTokenEl = document.getElementById("csrfToken");
  var csrfToken = csrfTokenEl ? csrfTokenEl.value : "";

  function buildFileItem(file, entityId) {
    var container = document.createElement("div");
    container.className = "file-banner";

    var info = document.createElement("div");
    info.className = "file-info";

    var iconWrap = document.createElement("div");
    iconWrap.className = "file-icon";
    var icon = document.createElement("i");
    var mime = (file.mimeType || "").toLowerCase();
    if (mime.indexOf("pdf") >= 0) {
      icon.className = "fa-solid fa-file-pdf";
    } else if (mime.indexOf("image") >= 0) {
      icon.className = "fa-solid fa-file-image";
    } else {
      icon.className = "fa-solid fa-file";
    }
    iconWrap.appendChild(icon);

    var text = document.createElement("div");
    text.className = "file-text";
    var nameEl = document.createElement("span");
    nameEl.className = "file-name";
    nameEl.textContent = file.fileName || "-";
    var typeEl = document.createElement("span");
    typeEl.className = "file-type";
    typeEl.textContent = file.mimeType || "";
    text.appendChild(nameEl);
    text.appendChild(typeEl);

    info.appendChild(iconWrap);
    info.appendChild(text);

    var viewAction = document.createElement("div");
    viewAction.className = "file-actions";
    var viewLink = document.createElement("a");
    viewLink.href =
      "public/api/file-download.php?module=circulars&entity_id=" +
      encodeURIComponent(entityId) +
      "&file_id=" +
      encodeURIComponent(file.fileID || "");
    viewLink.target = "_blank";
    viewLink.rel = "noopener";
    viewLink.innerHTML = '<i class="fa-solid fa-eye"></i>';
    viewAction.appendChild(viewLink);

    var downloadAction = document.createElement("div");
    downloadAction.className = "file-actions";
    var downloadLink = document.createElement("a");
    downloadLink.href =
      "public/api/file-download.php?module=circulars&entity_id=" +
      encodeURIComponent(entityId) +
      "&file_id=" +
      encodeURIComponent(file.fileID || "") +
      "&download=1";
    downloadLink.innerHTML = '<i class="fa-solid fa-download"></i>';
    downloadAction.appendChild(downloadLink);

    container.appendChild(info);
    container.appendChild(viewAction);
    container.appendChild(downloadAction);

    return container;
  }

  function renderFiles(files, entityId) {
    if (!fileSection) return;
    fileSection.innerHTML = "";
    if (!files || files.length === 0) {
      fileSection.innerHTML =
        '<div class="file-banner"><div class="file-info"><div class="file-text"><span class="file-name">ไม่มีไฟล์แนบ</span></div></div></div>';
      return;
    }
    files.forEach(function (file) {
      fileSection.appendChild(buildFileItem(file, entityId));
    });
  }

  function openModal() {
    if (!modalOverlay) return;
    modalOverlay.style.display = "flex";
  }

  function closeModal() {
    if (!modalOverlay) return;
    modalOverlay.style.display = "none";
  }

  function markRead(inboxId, row) {
    if (!inboxId || !csrfToken) {
      return;
    }

    fetch("public/api/circular-read.php", {
      method: "POST",
      headers: {
        "Content-Type": "application/x-www-form-urlencoded",
      },
      body:
        "inbox_id=" +
        encodeURIComponent(inboxId) +
        "&csrf_token=" +
        encodeURIComponent(csrfToken),
    })
      .then(function (response) {
        return response.json();
      })
      .then(function (data) {
        if (!data || data.success === false) {
          return;
        }
        if (!row) return;
        var badge = row.querySelector(".status-badge");
        if (badge) {
          badge.classList.remove("unread");
          badge.classList.add("read");
          badge.textContent = "อ่านแล้ว";
        }
      })
      .catch(function () {});
  }

  if (modalClose) {
    modalClose.addEventListener("click", closeModal);
  }

  if (modalOverlay) {
    modalOverlay.addEventListener("click", function (event) {
      if (event.target === modalOverlay) {
        closeModal();
      }
    });
  }

  document.querySelectorAll(".js-open-circular-modal").forEach(function (button) {
    button.addEventListener("click", function () {
      var entityId = button.getAttribute("data-circular-id") || "";
      var inboxId = button.getAttribute("data-inbox-id") || "";
      var row = button.closest("tr");
      var files = button.getAttribute("data-files");
      var parsedFiles = [];
      try {
        parsedFiles = files ? JSON.parse(files) : [];
      } catch (e) {
        parsedFiles = [];
      }

      if (modalTypeLabel) {
        modalTypeLabel.textContent =
          button.getAttribute("data-type") || "ประเภทหนังสือ";
      }
      if (modalSubject) {
        var subjectValue = button.getAttribute("data-subject") || "-";
        if (
          modalSubject.tagName === "INPUT" ||
          modalSubject.tagName === "TEXTAREA"
        ) {
          modalSubject.value = subjectValue;
        } else {
          modalSubject.textContent = subjectValue;
        }
      }
      if (modalSender) {
        modalSender.textContent = button.getAttribute("data-sender") || "-";
      }
      if (modalDate) {
        modalDate.textContent = button.getAttribute("data-date") || "-";
      }
      if (modalDetail) {
        modalDetail.textContent = button.getAttribute("data-detail") || "-";
      }
      if (modalLink) {
        var linkValue = button.getAttribute("data-link") || "";
        modalLink.textContent = linkValue !== "" ? linkValue : "-";
        modalLink.href = linkValue !== "" ? linkValue : "#";
      }
      if (modalArchiveId) {
        modalArchiveId.value = inboxId;
      }

      if (modalUrgency) {
        var urgency = button.getAttribute("data-urgency") || "ปกติ";
        var urgencyClass =
          button.getAttribute("data-urgency-class") || "normal";
        modalUrgency.className =
          ("urgency-status " + urgencyClass).trim();
        var urgencyText = modalUrgency.querySelector("p");
        if (urgencyText) {
          urgencyText.textContent = urgency;
        }
      }
      if (modalBookNo) {
        modalBookNo.value = button.getAttribute("data-bookno") || "-";
      }
      if (modalIssuedDate) {
        modalIssuedDate.value = button.getAttribute("data-issued") || "-";
      }
      if (modalFromText) {
        modalFromText.value = button.getAttribute("data-from") || "-";
      }
      if (modalToText) {
        modalToText.value = button.getAttribute("data-to") || "-";
      }
      if (modalStatus) {
        modalStatus.value = button.getAttribute("data-status") || "-";
      }
      if (modalReceivedTime) {
        modalReceivedTime.value =
          button.getAttribute("data-received-time") || "-";
      }
      if (modalConsiderStatus) {
        var statusClass = button.getAttribute("data-consider") || "considering";
        modalConsiderStatus.className =
          ("consider-status " + statusClass).trim();
        modalConsiderStatus.textContent =
          button.getAttribute("data-status") || "กำลังเสนอ";
      }

      renderFiles(parsedFiles, entityId);
      openModal();
      if (inboxId) {
        markRead(inboxId, row);
      }
    });
  });

  document.addEventListener("keydown", function (event) {
    if (event.key === "Escape") {
      closeModal();
    }
  });
})();
