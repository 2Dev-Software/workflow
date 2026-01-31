(function () {
  var root = document.querySelector("[data-room-booking-approval]");
  if (!root) {
    return;
  }

  var detailModal = document.getElementById("bookingApprovalDetailModal");
  var confirmModal = document.getElementById("approvalConfirmationModal");
  var confirmBox = confirmModal ? confirmModal.querySelector(".alert-box") : null;
  var confirmIcon = confirmBox ? confirmBox.querySelector(".icon-circle i") : null;
  var confirmTitle = document.getElementById("approvalConfirmTitle");
  var confirmMessage = document.getElementById("approvalConfirmMessage");
  var confirmBtn = document.getElementById("btnConfirmAction");
  var confirmCloseBtn = confirmModal
    ? confirmModal.querySelector("[data-approval-confirm-close]")
    : null;
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
  var approvalReasonInput = approvalForm
    ? approvalForm.querySelector('[name="statusReason"]')
    : null;
  var approvalActionButtons = detailModal
    ? detailModal.querySelectorAll("[data-approval-submit]")
    : [];

  var pendingAction = "";

  function toggleApprovalReasonRequired(isRequired) {
    if (!approvalReasonInput) return;
    if (isRequired) {
      approvalReasonInput.setAttribute("required", "required");
      approvalReasonInput.setAttribute("aria-required", "true");
    } else {
      approvalReasonInput.removeAttribute("required");
      approvalReasonInput.removeAttribute("aria-required");
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

    fetch(fullUrl)
      .then(function (response) {
        return response.text();
      })
      .then(function (html) {
        tableBody.innerHTML = html;
      })
      .catch(function (error) {
        console.error("Error loading data:", error);
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
        reasonRow: detailModal.querySelector('[data-approval-detail="reason-row"]'),
        reason: detailModal.querySelector('[data-approval-detail="reason"]'),
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
    var reason = button.dataset.approvalReason || "-";
    var approvalName = button.dataset.approvalName || "-";
    var approvalAt = button.dataset.approvalAt || "-";

    if (approvalIdInput) {
      approvalIdInput.value = button.dataset.approvalId || "";
    }
    if (approvalActionInput) {
      approvalActionInput.value = "";
    }
    toggleApprovalReasonRequired(false);
    if (approvalReasonInput) {
      approvalReasonInput.value =
        statusValue === 2 && reason !== "-" && reason !== "ไม่ระบุเหตุผล"
          ? reason
          : "";
    }

    if (detailFields.room)
      detailFields.room.textContent = button.dataset.approvalRoom || "-";
    if (detailFields.code)
      detailFields.code.textContent =
        "รหัสคำขอ " + (button.dataset.approvalCode || "-");
    if (detailFields.date)
      detailFields.date.textContent = button.dataset.approvalDate || "-";
    if (detailFields.time)
      detailFields.time.textContent = button.dataset.approvalTime || "-";
    if (detailFields.requester)
      detailFields.requester.textContent =
        button.dataset.approvalRequester || "-";
    if (detailFields.department)
      detailFields.department.textContent =
        button.dataset.approvalDepartment || "-";
    if (detailFields.contact)
      detailFields.contact.textContent = button.dataset.approvalContact || "-";
    if (detailFields.attendees)
      detailFields.attendees.textContent = button.dataset.approvalAttendees || "-";
    if (detailFields.topic)
      detailFields.topic.textContent = button.dataset.approvalTopic || "-";
    if (detailFields.detail)
      detailFields.detail.textContent = button.dataset.approvalDetail || "-";
    if (detailFields.equipment)
      detailFields.equipment.textContent = button.dataset.approvalEquipment || "-";
    if (detailFields.created)
      detailFields.created.textContent = button.dataset.approvalCreated || "-";
    if (detailFields.updated)
      detailFields.updated.textContent = button.dataset.approvalUpdated || "-";

    if (detailFields.status) {
      detailFields.status.textContent = statusLabel;
      detailFields.status.className = "status-pill " + statusClass;
    }

    if (detailFields.reasonRow && detailFields.reason) {
      var showReason = statusValue === 2;
      detailFields.reasonRow.classList.toggle("hidden", !showReason);
      if (showReason) {
        detailFields.reason.textContent = reason;
      }
    }

    if (detailFields.approvalLabel && detailFields.approvalName && detailFields.approvalAt) {
      if (statusValue === 0) {
        detailFields.approvalLabel.textContent = "ผู้อนุมัติ";
        detailFields.approvalName.textContent = "รอการอนุมัติ";
        detailFields.approvalAt.textContent = "-";
      } else {
        detailFields.approvalLabel.textContent =
          statusValue === 2 ? "ผู้ไม่อนุมัติ" : "ผู้อนุมัติ";
        detailFields.approvalName.textContent = approvalName || "-";
        detailFields.approvalAt.textContent = approvalAt || "-";
      }
    }

    detailModal.classList.remove("hidden");
  });

  if (approvalActionButtons.length > 0) {
    approvalActionButtons.forEach(function (button) {
      button.addEventListener("click", function (e) {
        e.preventDefault();
        var action = button.getAttribute("data-approval-submit") || "";
        pendingAction = action;
        toggleApprovalReasonRequired(action === "reject");

        if (confirmModal && confirmBox && confirmIcon && confirmTitle && confirmMessage && confirmBtn) {
          confirmBox.classList.remove("success", "danger");

          if (action === "approve") {
            confirmBox.classList.add("success");
            confirmIcon.className = "fa-solid fa-check";
            confirmTitle.textContent = "ยืนยันการอนุมัติ";
            confirmMessage.textContent = "คุณต้องการอนุมัติรายการนี้ใช่หรือไม่?";
            confirmBtn.textContent = "ยืนยันอนุมัติ";
          } else if (action === "reject") {
            confirmBox.classList.add("danger");
            confirmIcon.className = "fa-solid fa-xmark";
            confirmTitle.textContent = "ยืนยันการไม่อนุมัติ";
            confirmMessage.textContent = "คุณต้องการไม่อนุมัติรายการนี้ใช่หรือไม่?";
            confirmBtn.textContent = "ยืนยันไม่อนุมัติ";
          }
          confirmModal.classList.remove("hidden");
        } else {
          if (approvalActionInput) {
            approvalActionInput.value = action;
          }
          submitApprovalForm();
        }
      });
    });
  }

  if (confirmBtn) {
    confirmBtn.addEventListener("click", function () {
      if (approvalActionInput) {
        approvalActionInput.value = pendingAction;
      }
      if (pendingAction === "approve" && approvalReasonInput) {
        approvalReasonInput.value = "";
      }
      toggleApprovalReasonRequired(pendingAction === "reject");
      submitApprovalForm();
      if (confirmModal) {
        confirmModal.classList.add("hidden");
      }
    });
  }

  if (confirmCloseBtn) {
    confirmCloseBtn.addEventListener("click", function () {
      if (confirmModal) {
        confirmModal.classList.add("hidden");
      }
    });
  }

  closeButtons.forEach(function (button) {
    button.addEventListener("click", function () {
      var targetId = button.getAttribute("data-approval-modal-close");
      if (!targetId) return;
      var targetModal = document.getElementById(targetId);
      if (targetModal) {
        targetModal.classList.add("hidden");
      }
    });
  });

  if (detailModal) {
    detailModal.addEventListener("click", function (event) {
      if (event.target === detailModal) {
        detailModal.classList.add("hidden");
      }
    });
  }

  if (confirmModal) {
    confirmModal.addEventListener("click", function (event) {
      if (event.target === confirmModal) {
        confirmModal.classList.add("hidden");
      }
    });
  }
})();
