(function () {
  var root = document.querySelector("[data-room-management]");
  if (!root) {
    return;
  }

  var openButtons = document.querySelectorAll("[data-room-modal-open]");
  var closeButtons = document.querySelectorAll("[data-room-modal-close]");
  var memberModal = document.getElementById("roomMemberModal");
  var editModal = document.getElementById("roomEditModal");
  var memberConfirmModal = document.getElementById("roomMemberConfirmModal");
  var memberConfirmMessage = memberConfirmModal
    ? memberConfirmModal.querySelector("[data-room-member-confirm-message]")
    : null;
  var memberConfirmButton = memberConfirmModal
    ? memberConfirmModal.querySelector('[data-room-member-confirm="true"]')
    : null;
  var memberCancelButton = memberConfirmModal
    ? memberConfirmModal.querySelector('[data-room-member-cancel="true"]')
    : null;
  var memberRemoveConfirmModal = document.getElementById(
    "roomMemberRemoveConfirmModal"
  );
  var memberRemoveMessage = memberRemoveConfirmModal
    ? memberRemoveConfirmModal.querySelector(
        "[data-room-member-remove-message]"
      )
    : null;
  var memberRemoveConfirmButton = memberRemoveConfirmModal
    ? memberRemoveConfirmModal.querySelector(
        '[data-room-member-remove-confirm="true"]'
      )
    : null;
  var memberRemoveCancelButton = memberRemoveConfirmModal
    ? memberRemoveConfirmModal.querySelector(
        '[data-room-member-remove-cancel="true"]'
      )
    : null;
  var roomDeleteConfirmModal = document.getElementById("roomDeleteConfirmModal");
  var roomDeleteMessage = roomDeleteConfirmModal
    ? roomDeleteConfirmModal.querySelector("[data-room-delete-message]")
    : null;
  var roomDeleteConfirmButton = roomDeleteConfirmModal
    ? roomDeleteConfirmModal.querySelector('[data-room-delete-confirm="true"]')
    : null;
  var roomDeleteCancelButton = roomDeleteConfirmModal
    ? roomDeleteConfirmModal.querySelector('[data-room-delete-cancel="true"]')
    : null;
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
  var pendingMemberForm = null;
  var pendingRemoveForm = null;
  var pendingMemberCard = null;
  var pendingRoomDeleteForm = null;

  function openMemberConfirm(form) {
    pendingMemberForm = form;
    var card = form.closest(".room-admin-member-card");
    var row = form.closest("[data-member-row], tr");
    pendingMemberCard = card || row;
    var nameEl = card ? card.querySelector(".room-admin-member-name") : null;
    if (!nameEl && row) {
      nameEl = row.querySelector("strong");
    }
    var name =
      nameEl && nameEl.textContent.trim() !== ""
        ? nameEl.textContent.trim()
        : "บุคลากรคนนี้";
    if (memberConfirmMessage) {
      memberConfirmMessage.textContent =
        "โปรดยืนยันการเพิ่ม " + name + " เป็นสมาชิกทีมผู้ดูแลสถานที่/ห้อง";
    }
    if (memberConfirmModal) {
      memberConfirmModal.classList.remove("hidden");
    }
  }

  function openMemberRemoveConfirm(form) {
    pendingRemoveForm = form;
    var row = form.closest("tr");
    var nameEl = row ? row.querySelector("strong") : null;
    var name =
      nameEl && nameEl.textContent.trim() !== ""
        ? nameEl.textContent.trim()
        : "บุคลากรคนนี้";
    if (memberRemoveMessage) {
      memberRemoveMessage.textContent =
        "โปรดยืนยันการลบ " + name + " ออกจากสมาชิกทีมผู้ดูแลสถานที่/ห้อง";
    }
    if (memberRemoveConfirmModal) {
      memberRemoveConfirmModal.classList.remove("hidden");
    }
  }

  function openRoomDeleteConfirm(form) {
    pendingRoomDeleteForm = form;
    var row = form.closest("[data-room-row]");
    var roomName = row ? row.dataset.roomName || "" : "";
    var label = roomName.trim() !== "" ? roomName.trim() : "ห้อง/สถานที่นี้";
    if (roomDeleteMessage) {
      roomDeleteMessage.textContent = "โปรดยืนยันการลบ " + label + " ออกจากระบบ";
    }
    if (roomDeleteConfirmModal) {
      roomDeleteConfirmModal.classList.remove("hidden");
    }
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

  if (memberConfirmModal) {
    memberConfirmModal.addEventListener("click", function (event) {
      if (event.target === memberConfirmModal) {
        memberConfirmModal.classList.add("hidden");
        pendingMemberForm = null;
        pendingMemberCard = null;
      }
    });
  }

  if (memberRemoveConfirmModal) {
    memberRemoveConfirmModal.addEventListener("click", function (event) {
      if (event.target === memberRemoveConfirmModal) {
        memberRemoveConfirmModal.classList.add("hidden");
        pendingRemoveForm = null;
      }
    });
  }

  if (roomDeleteConfirmModal) {
    roomDeleteConfirmModal.addEventListener("click", function (event) {
      if (event.target === roomDeleteConfirmModal) {
        roomDeleteConfirmModal.classList.add("hidden");
        pendingRoomDeleteForm = null;
      }
    });
  }

  document.addEventListener("submit", function (event) {
    var form = event.target;
    if (!(form instanceof HTMLFormElement)) return;
    if (form.matches("[data-member-remove-form]")) {
      event.preventDefault();
      openMemberRemoveConfirm(form);
    }
    if (form.matches("[data-room-delete-form]")) {
      event.preventDefault();
      openRoomDeleteConfirm(form);
    }
  });

  document.addEventListener("click", function (event) {
    var addMemberBtn = event.target.closest("[data-member-add-btn]");
    if (addMemberBtn) {
      event.preventDefault();
      var form = addMemberBtn.closest("form");
      if (form) {
        openMemberConfirm(form);
      }
      return;
    }

    var deleteButton = event.target.closest("[data-room-delete-btn]");
    if (deleteButton) {
      event.preventDefault();
      var form = deleteButton.closest("[data-room-delete-form]");
      if (form) {
        openRoomDeleteConfirm(form);
      }
    }
  });

  if (memberConfirmButton) {
    memberConfirmButton.addEventListener("click", function () {
      if (!pendingMemberForm) return;
      pendingMemberForm.submit();
    });
  }

  if (memberCancelButton) {
    memberCancelButton.addEventListener("click", function () {
      if (memberConfirmModal) {
        memberConfirmModal.classList.add("hidden");
      }
      pendingMemberForm = null;
      pendingMemberCard = null;
    });
  }

  if (memberRemoveConfirmButton) {
    memberRemoveConfirmButton.addEventListener("click", function () {
      if (pendingRemoveForm) {
        pendingRemoveForm.submit();
      }
    });
  }

  if (memberRemoveCancelButton) {
    memberRemoveCancelButton.addEventListener("click", function () {
      if (memberRemoveConfirmModal) {
        memberRemoveConfirmModal.classList.add("hidden");
      }
      pendingRemoveForm = null;
    });
  }

  if (roomDeleteConfirmButton) {
    roomDeleteConfirmButton.addEventListener("click", function () {
      if (pendingRoomDeleteForm) {
        pendingRoomDeleteForm.submit();
      }
    });
  }

  if (roomDeleteCancelButton) {
    roomDeleteCancelButton.addEventListener("click", function () {
      if (roomDeleteConfirmModal) {
        roomDeleteConfirmModal.classList.add("hidden");
      }
      pendingRoomDeleteForm = null;
    });
  }

  if (memberSearchForm && memberSearchInput) {
    memberSearchForm.addEventListener("submit", function (event) {
      event.preventDefault();
      updateMemberSearch();
    });

    memberSearchInput.addEventListener("input", updateMemberSearch);
  }

  updateMemberSearch();

  var roomSearchInput = document.querySelector("[data-room-search-input]");
  var roomStatusFilter = document.querySelector("[data-room-status-filter]");
  var roomRows = Array.from(document.querySelectorAll("[data-room-row]"));
  var roomEmpty = document.querySelector("[data-room-empty]");

  function applyRoomFilters() {
    if (!roomRows.length) return;
    var query = roomSearchInput ? roomSearchInput.value.trim().toLowerCase() : "";
    var status = roomStatusFilter ? roomStatusFilter.value : "all";
    var visibleCount = 0;

    roomRows.forEach(function (row) {
      var haystack = (row.dataset.roomSearch || "").toLowerCase();
      var rowStatus = row.dataset.roomStatus || "";
      var matchQuery = query === "" || haystack.includes(query);
      var matchStatus = status === "all" || rowStatus === status;
      var isVisible = matchQuery && matchStatus;
      row.style.display = isVisible ? "" : "none";
      if (isVisible) visibleCount += 1;
    });

    if (roomEmpty) {
      roomEmpty.classList.toggle("hidden", visibleCount !== 0);
    }
  }

  if (roomSearchInput) {
    roomSearchInput.addEventListener("input", applyRoomFilters);
    roomSearchInput.addEventListener("keydown", function (event) {
      if (event.key === "Enter") {
        event.preventDefault();
        applyRoomFilters();
      }
    });
  }

  if (roomStatusFilter) {
    roomStatusFilter.addEventListener("change", applyRoomFilters);
  }

  applyRoomFilters();
})();
