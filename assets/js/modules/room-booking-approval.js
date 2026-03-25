(function () {
  var root = document.querySelector("[data-room-booking-approval]");
  if (!root) {
    return;
  }
  var loadingApi = window.App && window.App.loading ? window.App.loading : null;
  var listLoadingTarget =
    root.querySelector(".booking-list-card .table-responsive") ||
    root.querySelector(".booking-list-card");

  function getAlertsApi() {
    return window.AppAlerts || null;
  }

  var detailModal = document.getElementById("bookingApprovalDetailModal");
  var closeButtons = document.querySelectorAll("[data-approval-modal-close]");

  var approvalForm = detailModal
    ? detailModal.querySelector("[data-approval-form]")
    : null;
  var approvalIdInput = approvalForm
    ? approvalForm.querySelector('[name="room_booking_id"]')
    : null;
  var approvalActionInput = approvalForm
    ? approvalForm.querySelector('[name="approval_action"]')
    : null;
  var approvalNoteInput = approvalForm
    ? approvalForm.querySelector('[name="approvalNote"]')
    : null;
  var approvalActionButtons = detailModal
    ? detailModal.querySelectorAll("[data-approval-submit]")
    : [];

  function setFieldValue(field, value) {
    if (!field) {
      return;
    }
    var normalized = value && value !== "" ? value : "-";
    if ("value" in field) {
      field.value = normalized;
      return;
    }
    field.textContent = normalized;
  }

  function toggleApprovalNoteRequired(isRequired) {
    if (!approvalNoteInput) return;
    if (isRequired) {
      approvalNoteInput.setAttribute("required", "required");
      approvalNoteInput.setAttribute("aria-required", "true");
    } else {
      approvalNoteInput.removeAttribute("required");
      approvalNoteInput.removeAttribute("aria-required");
    }
  }

  function submitApprovalForm() {
    if (!approvalForm) return false;
    if (
      typeof approvalForm.reportValidity === "function" &&
      !approvalForm.reportValidity()
    ) {
      return false;
    }
    if (typeof approvalForm.requestSubmit === "function") {
      approvalForm.requestSubmit();
    } else {
      approvalForm.submit();
    }
    return true;
  }

  function confirmApprovalAction(action) {
    var isApprove = action === "approve";
    var title = isApprove ? "ยืนยันการอนุมัติ" : "ยืนยันการไม่อนุมัติ";
    var message = isApprove
      ? "คุณต้องการอนุมัติรายการนี้ใช่หรือไม่?"
      : "คุณต้องการไม่อนุมัติรายการนี้ใช่หรือไม่?";
    var confirmButtonText = isApprove ? "ยืนยันอนุมัติ" : "ยืนยันไม่อนุมัติ";
    var alertsApi = getAlertsApi();

    if (alertsApi && typeof alertsApi.confirm === "function") {
      return alertsApi.confirm(message, {
        title: title,
        type: isApprove ? "success" : "danger",
        confirmButtonText: confirmButtonText,
        cancelButtonText: "ยกเลิก",
      });
    }

    console.warn("Room booking approval confirm dialog unavailable");
    return Promise.resolve(false);
  }

  // --- AJAX Search & Filter ---
  var filterForm = document.querySelector("[data-approval-filter-form]");
  var tableBody = document.querySelector(".booking-list-card table tbody");
  var searchInput = filterForm ? filterForm.querySelector('input[name="q"]') : null;
  var filterSelects = filterForm ? filterForm.querySelectorAll("select") : [];
  var searchTimeout;

  function fetchResults() {
    if (!filterForm || !tableBody) return;

    var formData = new FormData(filterForm);
    var params = new URLSearchParams(formData);
    params.append("ajax_filter", "1");

    var url = filterForm.action;
    var fullUrl = url + "?" + params.toString();

    var historyUrl = url + "?" + new URLSearchParams(formData).toString();
    window.history.pushState({}, "", historyUrl);

    if (loadingApi) {
      loadingApi.startComponent(listLoadingTarget);
    }

    fetch(fullUrl)
      .then(function (response) {
        return response.text();
      })
      .then(function (html) {
        tableBody.innerHTML = html;
      })
      .catch(function (error) {
        console.error("Error loading data:", error);
      })
      .finally(function () {
        if (loadingApi) {
          loadingApi.stopComponent(listLoadingTarget);
        }
      });
  }

  if (filterForm) {
    if (searchInput) {
      searchInput.addEventListener("input", function () {
        clearTimeout(searchTimeout);
        searchTimeout = setTimeout(fetchResults, 400);
      });
    }
    filterSelects.forEach(function (select) {
      select.addEventListener("change", fetchResults);
    });
    filterForm.addEventListener("submit", function (e) {
      e.preventDefault();
      fetchResults();
    });
  }

  // --- Detail Button Delegation ---
  var detailFields = detailModal
    ? {
        room: detailModal.querySelector('[data-approval-detail="room"]'),
        code: detailModal.querySelector('[data-approval-detail="code"]'),
        date: detailModal.querySelector('[data-approval-detail="date"]'),
        time: detailModal.querySelector('[data-approval-detail="time"]'),
        requester: detailModal.querySelector('[data-approval-detail="requester"]'),
        department: detailModal.querySelector('[data-approval-detail="department"]'),
        contact: detailModal.querySelector('[data-approval-detail="contact"]'),
        attendees: detailModal.querySelector('[data-approval-detail="attendees"]'),
        status: detailModal.querySelector('[data-approval-detail="status"]'),
        topic: detailModal.querySelector('[data-approval-detail="topic"]'),
        detail: detailModal.querySelector('[data-approval-detail="detail"]'),
        equipment: detailModal.querySelector('[data-approval-detail="equipment"]'),
        approvalItem: detailModal.querySelector('[data-approval-detail="approval-item"]'),
        approvalLabel: detailModal.querySelector('[data-approval-detail="approval-label"]'),
        approvalName: detailModal.querySelector('[data-approval-detail="approval-name"]'),
        approvalAt: detailModal.querySelector('[data-approval-detail="approval-at"]'),
        created: detailModal.querySelector('[data-approval-detail="created"]'),
        updated: detailModal.querySelector('[data-approval-detail="updated"]'),
      }
    : {};

  document.addEventListener("click", function (event) {
    var button = event.target.closest('[data-approval-action="detail"]');
    if (!button) return;

    if (!detailModal) return;

    var statusClass = button.dataset.approvalStatusClass || "pending";
    var statusLabel = button.dataset.approvalStatusLabel || "-";
    var statusValue = parseInt(button.dataset.approvalStatus || "0", 10);
    var approvalNote = button.dataset.approvalNote || "-";
    var approvalName = button.dataset.approvalName || "-";
    var approvalAt = button.dataset.approvalAt || "-";

    if (approvalIdInput) {
      approvalIdInput.value = button.dataset.approvalId || "";
    }
    if (approvalActionInput) {
      approvalActionInput.value = "";
    }
    toggleApprovalNoteRequired(false);
    if (approvalNoteInput) {
      approvalNoteInput.value =
        approvalNote !== "-" && approvalNote !== "ไม่ระบุรายละเอียด"
          ? approvalNote
          : "";
    }

    setFieldValue(detailFields.room, button.dataset.approvalRoom);
    if (detailFields.code) {
      setFieldValue(
        detailFields.code,
        "รหัสคำขอ " + (button.dataset.approvalCode || "-")
      );
    }
    setFieldValue(detailFields.date, button.dataset.approvalDate);
    setFieldValue(detailFields.time, button.dataset.approvalTime);
    setFieldValue(detailFields.requester, button.dataset.approvalRequester);
    setFieldValue(detailFields.department, button.dataset.approvalDepartment);
    setFieldValue(detailFields.contact, button.dataset.approvalContact);
    setFieldValue(detailFields.attendees, button.dataset.approvalAttendees);
    setFieldValue(detailFields.topic, button.dataset.approvalTopic);
    setFieldValue(detailFields.detail, button.dataset.approvalDetail);
    setFieldValue(detailFields.equipment, button.dataset.approvalEquipment);
    setFieldValue(detailFields.created, button.dataset.approvalCreated);
    setFieldValue(detailFields.updated, button.dataset.approvalUpdated);

    if (detailFields.status) {
      detailFields.status.textContent = statusLabel;
      detailFields.status.className = "status-pill " + statusClass;
    }

    if (detailFields.approvalItem) {
      detailFields.approvalItem.classList.toggle("hidden", statusValue === 0);
    }

    if (detailFields.approvalLabel && detailFields.approvalName && detailFields.approvalAt) {
      if (statusValue !== 0) {
        detailFields.approvalLabel.textContent =
          statusValue === 2 ? "ผู้ไม่อนุมัติ" : "ผู้อนุมัติ";
        setFieldValue(detailFields.approvalName, approvalName);
        setFieldValue(detailFields.approvalAt, approvalAt);
      }
    }

    detailModal.classList.remove("hidden");
  });

  if (approvalActionButtons.length > 0) {
    approvalActionButtons.forEach(function (button) {
      button.addEventListener("click", function (e) {
        e.preventDefault();
        var action = button.getAttribute("data-approval-submit") || "";
        if (action !== "approve" && action !== "reject") {
          return;
        }
        toggleApprovalNoteRequired(true);
        confirmApprovalAction(action).then(function (approved) {
          if (!approved) {
            return;
          }
          if (approvalActionInput) {
            approvalActionInput.value = action;
          }
          toggleApprovalNoteRequired(true);
          submitApprovalForm();
        });
      });
    });
  }

  closeButtons.forEach(function (button) {
    button.addEventListener("click", function () {
      var targetId = button.getAttribute("data-approval-modal-close");
      if (!targetId) return;
      var targetModal = document.getElementById(targetId);
      if (targetModal) {
        toggleApprovalNoteRequired(false);
        targetModal.classList.add("hidden");
      }
    });
  });

  if (detailModal) {
    detailModal.addEventListener("click", function (event) {
      if (event.target === detailModal) {
        toggleApprovalNoteRequired(false);
        detailModal.classList.add("hidden");
      }
    });
  }

})();
