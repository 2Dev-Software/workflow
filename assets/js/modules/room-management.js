(function () {
  var root = document.querySelector("[data-room-management]");
  if (!root) {
    return;
  }

  var CONFIRM_APPROVED_ATTR = "data-room-confirm-approved";
  var openButtons = document.querySelectorAll("[data-room-modal-open]");
  var closeButtons = document.querySelectorAll("[data-room-modal-close]");
  var memberModal = document.getElementById("roomMemberModal");
  var editModal = document.getElementById("roomEditModal");
  var memberSearchForm = memberModal
    ? memberModal.querySelector("[data-member-search-form]")
    : null;
  var memberSearchInput = memberModal
    ? memberModal.querySelector("[data-member-search]")
    : null;
  var memberCards = memberModal
    ? Array.from(memberModal.querySelectorAll("[data-member-card], [data-member-row]"))
    : [];
  var memberEmptyState = memberModal
    ? memberModal.querySelector("[data-member-empty]")
    : null;
  var memberCount = memberModal
    ? memberModal.querySelector("[data-member-count]")
    : null;
  var editButtons = Array.from(document.querySelectorAll("[data-room-edit]"));
  var editIdInput = editModal
    ? editModal.querySelector("[data-room-edit-id]")
    : null;
  var editNameInput = editModal
    ? editModal.querySelector("[data-room-edit-name]")
    : null;
  var editStatusSelect = editModal
    ? editModal.querySelector("[data-room-edit-status]")
    : null;
  var editNoteInput = editModal
    ? editModal.querySelector("[data-room-edit-note]")
    : null;
  ["roomMemberConfirmModal", "roomMemberRemoveConfirmModal", "roomDeleteConfirmModal"].forEach(function (legacyId) {
    var legacyModal = document.getElementById(legacyId);
    if (legacyModal && legacyModal.parentNode) {
      legacyModal.parentNode.removeChild(legacyModal);
    }
  });
  document.querySelectorAll(".alert-overlay").forEach(function (overlay) {
    if (
      overlay.querySelector(
        "[data-room-member-confirm='true'],[data-room-member-cancel='true'],[data-room-member-remove-confirm='true'],[data-room-member-remove-cancel='true'],[data-room-delete-confirm='true'],[data-room-delete-cancel='true']"
      )
    ) {
      if (overlay.parentNode) {
        overlay.parentNode.removeChild(overlay);
      }
    }
  });

  function submitApprovedForm(form) {
    if (!(form instanceof HTMLFormElement)) {
      return;
    }
    form.setAttribute(CONFIRM_APPROVED_ATTR, "1");
    if (typeof form.requestSubmit === "function") {
      form.requestSubmit();
      return;
    }
    form.submit();
  }

  function getAlertsApi() {
    return window.AppAlerts || null;
  }

  function confirmAction(message, options) {
    var opts = options || {};
    var alertsApi = getAlertsApi();
    if (alertsApi && typeof alertsApi.confirm === "function") {
      return alertsApi.confirm(message, {
        title: opts.title || "ยืนยันการทำรายการ",
        type: opts.type || "warning",
        confirmButtonText: opts.confirmButtonText || "ยืนยัน",
        cancelButtonText: opts.cancelButtonText || "ยกเลิก",
      });
    }
    console.warn("Room management confirm dialog unavailable");
    return Promise.resolve(false);
  }

  function openMemberConfirm(form) {
    var card = form.closest(".room-admin-member-card");
    var row = form.closest("[data-member-row], tr");
    var nameEl = card ? card.querySelector(".room-admin-member-name") : null;
    if (!nameEl && row) {
      nameEl = row.querySelector("strong");
    }
    var name =
      nameEl && nameEl.textContent.trim() !== ""
        ? nameEl.textContent.trim()
        : "บุคลากรคนนี้";
    var message = "โปรดยืนยันการเพิ่ม " + name + " เป็นสมาชิกทีมผู้ดูแลสถานที่/ห้อง";

    confirmAction(message, {
      title: "ยืนยันการเพิ่มสมาชิก",
      type: "warning",
      confirmButtonText: "ยืนยัน",
      cancelButtonText: "ยกเลิก",
    }).then(function (approved) {
      if (!approved) {
        return;
      }
      submitApprovedForm(form);
    });
  }

  function openMemberRemoveConfirm(form) {
    var row = form.closest("tr");
    var nameEl = row ? row.querySelector("strong") : null;
    var name =
      nameEl && nameEl.textContent.trim() !== ""
        ? nameEl.textContent.trim()
        : "บุคลากรคนนี้";
    var message = "โปรดยืนยันการลบ " + name + " ออกจากสมาชิกทีมผู้ดูแลสถานที่/ห้อง";

    confirmAction(message, {
      title: "ยืนยันการลบสมาชิก",
      type: "danger",
      confirmButtonText: "ยืนยัน",
      cancelButtonText: "ยกเลิก",
    }).then(function (approved) {
      if (!approved) {
        return;
      }
      submitApprovedForm(form);
    });
  }

  function openRoomDeleteConfirm(form) {
    var row = form.closest("[data-room-row]");
    var roomName = row ? row.dataset.roomName || "" : "";
    var label = roomName.trim() !== "" ? roomName.trim() : "ห้อง/สถานที่นี้";
    var message = "โปรดยืนยันการลบ " + label + " ออกจากระบบ";

    confirmAction(message, {
      title: "ยืนยันการลบห้อง",
      type: "danger",
      confirmButtonText: "ยืนยัน",
      cancelButtonText: "ยกเลิก",
    }).then(function (approved) {
      if (!approved) {
        return;
      }
      submitApprovedForm(form);
    });
  }

  function updateMemberSearch() {
    if (!memberModal || !memberSearchInput) return;
    var query = memberSearchInput.value.trim().toLowerCase();
    var visibleCount = 0;

    memberCards.forEach(function (item) {
      var haystack = (item.dataset.memberSearch || "").toLowerCase();
      var isMatch = query === "" || haystack.includes(query);
      item.style.display = isMatch ? "" : "none";
      if (isMatch) visibleCount += 1;
    });

    if (memberCount) {
      memberCount.textContent =
        query === "" ? "ทั้งหมด " + visibleCount + " คน" : "พบ " + visibleCount + " คน";
    }

    if (memberEmptyState) {
      memberEmptyState.classList.toggle("hidden", visibleCount !== 0);
    }
  }

  openButtons.forEach(function (button) {
    button.addEventListener("click", function () {
      var targetId = button.getAttribute("data-room-modal-open");
      if (!targetId) return;
      var targetModal = document.getElementById(targetId);
      if (targetModal) {
        targetModal.classList.remove("hidden");
        if (targetId === "roomMemberModal" && memberSearchInput) {
          memberSearchInput.value = "";
          updateMemberSearch();
          memberSearchInput.focus();
        }
      }
    });
  });

  editButtons.forEach(function (button) {
    button.addEventListener("click", function () {
      var row = button.closest("[data-room-row]");
      if (!row || !editModal) return;
      var roomId = row.dataset.roomId || "";
      var roomName = row.dataset.roomName || "";
      var roomStatus = row.dataset.roomStatusLabel || "";
      var roomNote = row.dataset.roomNote || "";

      if (editIdInput) editIdInput.value = roomId;
      if (editNameInput) editNameInput.value = roomName;
      if (editStatusSelect) {
        editStatusSelect.value = roomStatus;
        editStatusSelect.dispatchEvent(new Event("change", { bubbles: true }));
      }
      if (editNoteInput) editNoteInput.value = roomNote;

      editModal.classList.remove("hidden");
    });
  });

  var initialModal = root.getAttribute("data-room-open-modal") || "";
  if (initialModal) {
    var targetModal = document.getElementById(initialModal);
    if (targetModal) {
      targetModal.classList.remove("hidden");
      if (initialModal === "roomMemberModal" && memberSearchInput) {
        updateMemberSearch();
        memberSearchInput.focus();
      }
    }
  }

  closeButtons.forEach(function (button) {
    button.addEventListener("click", function () {
      var targetId = button.getAttribute("data-room-modal-close");
      if (!targetId) return;
      var targetModal = document.getElementById(targetId);
      if (targetModal) {
        targetModal.classList.add("hidden");
      }
    });
  });

  document.querySelectorAll(".modal-overlay").forEach(function (overlay) {
    overlay.addEventListener("click", function (event) {
      if (event.target === overlay) {
        overlay.classList.add("hidden");
      }
    });
  });

  document.addEventListener("submit", function (event) {
    var form = event.target;
    if (!(form instanceof HTMLFormElement)) return;
    if (form.getAttribute(CONFIRM_APPROVED_ATTR) === "1") {
      form.removeAttribute(CONFIRM_APPROVED_ATTR);
      return;
    }
    if (form.matches("[data-member-remove-form]")) {
      event.preventDefault();
      event.stopImmediatePropagation();
      openMemberRemoveConfirm(form);
    }
    if (form.matches("[data-room-delete-form]")) {
      event.preventDefault();
      event.stopImmediatePropagation();
      openRoomDeleteConfirm(form);
    }
  });

  document.addEventListener("click", function (event) {
    var addMemberBtn = event.target.closest("[data-member-add-btn]");
    if (addMemberBtn) {
      event.preventDefault();
      event.stopImmediatePropagation();
      var form = addMemberBtn.closest("form");
      if (form) {
        openMemberConfirm(form);
      }
      return;
    }

    var deleteButton = event.target.closest("[data-room-delete-btn]");
    if (deleteButton) {
      event.preventDefault();
      event.stopImmediatePropagation();
      var form = deleteButton.closest("[data-room-delete-form]");
      if (form) {
        openRoomDeleteConfirm(form);
      }
    }
  });

  if (memberSearchForm && memberSearchInput) {
    memberSearchForm.addEventListener("submit", function (event) {
      event.preventDefault();
      updateMemberSearch();
    });

    memberSearchInput.addEventListener("input", updateMemberSearch);
  }

  updateMemberSearch();

  var roomFilterForm = document.querySelector("[data-room-filter-form]");
  var roomSearchInput = document.querySelector("[data-room-search-input]");
  var roomStatusFilter = document.querySelector("[data-room-status-filter]");
  var roomFilterRoomInput = document.querySelector("[data-room-filter-room]");
  var roomFilterTimer = null;

  function submitRoomFilter() {
    if (!(roomFilterForm instanceof HTMLFormElement)) {
      return;
    }

    if (roomFilterRoomInput && String(roomFilterRoomInput.value || "").trim() === "") {
      roomFilterRoomInput.value = "all";
    }

    if (typeof roomFilterForm.requestSubmit === "function") {
      roomFilterForm.requestSubmit();
      return;
    }

    roomFilterForm.submit();
  }

  if (roomSearchInput) {
    roomSearchInput.addEventListener("input", function () {
      if (roomFilterTimer !== null) {
        window.clearTimeout(roomFilterTimer);
      }

      roomFilterTimer = window.setTimeout(function () {
        submitRoomFilter();
      }, 250);
    });

    roomSearchInput.addEventListener("search", function () {
      if (roomFilterTimer !== null) {
        window.clearTimeout(roomFilterTimer);
      }
      submitRoomFilter();
    });

    roomSearchInput.addEventListener("keydown", function (event) {
      if (event.key !== "Enter") {
        return;
      }

      event.preventDefault();

      if (roomFilterTimer !== null) {
        window.clearTimeout(roomFilterTimer);
      }
      submitRoomFilter();
    });
  }

  if (roomStatusFilter) {
    roomStatusFilter.addEventListener("change", function () {
      submitRoomFilter();
    });
  }
})();
