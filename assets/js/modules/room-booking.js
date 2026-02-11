(function () {
  var root = document.querySelector("[data-room-booking]");
  if (!root) {
    return;
  }

  var bookingModal = document.getElementById("bookingListModal");
  var detailModal = document.getElementById("bookingDetailModal");
  var deleteModal = document.getElementById("bookingDeleteModal");
  var openButtons = document.querySelectorAll("[data-booking-modal-open]");
  var closeButtons = document.querySelectorAll("[data-booking-modal-close]");
  var detailButtons = document.querySelectorAll('[data-booking-action="detail"]');
  var deleteButtons = document.querySelectorAll('[data-booking-action="delete"]');
  var deleteConfirmButton = document.querySelector('[data-booking-delete-confirm="true"]');
  var deleteCancelButton = document.querySelector('[data-booking-delete-cancel="true"]');
  var bookingForm = document.querySelector(".booking-form");
  var checkButton = bookingForm
    ? bookingForm.querySelector('button[name="room_booking_check"]')
    : null;
  var pendingDeleteRows = [];
  var pendingDeleteId = "";
  var deleteEndpoint = root.getAttribute("data-delete-endpoint") || "";
  var csrfToken = root.getAttribute("data-csrf") || "";
  var checkEndpoint =
    root.getAttribute("data-check-endpoint") ||
    "public/api/room-booking-check.php";

  function removeActiveAlerts() {
    document.querySelectorAll(".alert-overlay").forEach(function (overlay) {
      if (overlay.id !== "bookingDeleteModal") {
        overlay.remove();
      }
    });
  }

  function attachAlertBehaviors(overlay) {
    if (!overlay) return;
    overlay.querySelectorAll('[data-alert-close="true"]').forEach(function (btn) {
      btn.addEventListener("click", function () {
        overlay.remove();
      });
    });

    var redirectUrl = overlay.getAttribute("data-alert-redirect") || "";
    var delayValue = parseInt(overlay.getAttribute("data-alert-delay"), 10);
    if (redirectUrl) {
      var delay = Number.isNaN(delayValue) ? 0 : delayValue;
      window.setTimeout(function () {
        window.location.href = redirectUrl;
      }, delay);
    }
  }

  function appendAlertHtml(html) {
    if (!html) return;
    var temp = document.createElement("div");
    temp.innerHTML = html;
    var overlay = temp.querySelector(".alert-overlay");
    if (!overlay) return;
    removeActiveAlerts();
    document.body.appendChild(overlay);
    attachAlertBehaviors(overlay);
  }

  function buildAlertHtml(type, title, message) {
    var iconMap = {
      success: "fa-check",
      warning: "fa-triangle-exclamation",
      danger: "fa-xmark",
    };
    var alertType = iconMap[type] ? type : "danger";
    var icon = iconMap[alertType] || "fa-xmark";
    return (
      '<div class="alert-overlay" data-alert-redirect="" data-alert-delay="0">' +
      '<div class="alert-box ' +
      alertType +
      '">' +
      '<div class="alert-header"><div class="icon-circle"><i class="fa-solid ' +
      icon +
      '"></i></div></div>' +
      '<div class="alert-body">' +
      "<h1>" +
      title +
      "</h1>" +
      (message ? "<p>" + message + "</p>" : "") +
      '<button type="button" class="btn-close-alert" data-alert-close="true">ยืนยัน</button>' +
      "</div></div></div>"
    );
  }

  function showBookingAlert(type, title, message) {
    appendAlertHtml(buildAlertHtml(type, title, message));
  }

  var detailFields = detailModal
    ? {
        room: detailModal.querySelector('[data-booking-detail="room"]'),
        date: detailModal.querySelector('[data-booking-detail="date"]'),
        time: detailModal.querySelector('[data-booking-detail="time"]'),
        attendees: detailModal.querySelector('[data-booking-detail="attendees"]'),
        topic: detailModal.querySelector('[data-booking-detail="topic"]'),
        detail: detailModal.querySelector('[data-booking-detail="detail"]'),
        status: detailModal.querySelector('[data-booking-detail="status"]'),
        reason: detailModal.querySelector('[data-booking-detail="reason"]'),
        reasonRow: detailModal.querySelector('[data-booking-detail="reason-row"]'),
        approvalLabel: detailModal.querySelector('[data-booking-detail="approval-label"]'),
        approvalName: detailModal.querySelector('[data-booking-detail="approval-name"]'),
        approvalAt: detailModal.querySelector('[data-booking-detail="approval-at"]'),
        approvalItem: detailModal.querySelector('[data-booking-detail="approval-item"]'),
        created: detailModal.querySelector('[data-booking-detail="created"]'),
        updated: detailModal.querySelector('[data-booking-detail="updated"]'),
      }
    : null;

  function setDetailValue(node, value, fallback) {
    if (!node) return;
    var text = (value || "").toString().trim();
    node.textContent = text !== "" ? text : fallback || "-";
  }

  if (checkButton && bookingForm) {
    checkButton.addEventListener("click", function (event) {
      event.preventDefault();

      var formData = new FormData(bookingForm);
      formData.set("room_booking_check", "1");
      formData.delete("room_booking_save");

      fetch(checkEndpoint, {
        method: "POST",
        credentials: "same-origin",
        headers: {
          Accept: "application/json",
        },
        body: formData,
      })
        .then(function (response) {
          if (!response.ok) {
            throw new Error("response-error");
          }
          return response.json();
        })
        .then(function (data) {
          if (data && typeof data.html === "string" && data.html.trim() !== "") {
            appendAlertHtml(data.html);
            return;
          }
          showBookingAlert("danger", "ระบบขัดข้อง", "กรุณาลองใหม่อีกครั้ง");
        })
        .catch(function () {
          showBookingAlert("danger", "ระบบขัดข้อง", "กรุณาลองใหม่อีกครั้ง");
        });
    });
  }

  openButtons.forEach(function (button) {
    button.addEventListener("click", function () {
      var targetId = button.getAttribute("data-booking-modal-open");
      if (!targetId) return;
      var targetModal = document.getElementById(targetId);
      if (targetModal) {
        targetModal.classList.remove("hidden");
      }
    });
  });

  closeButtons.forEach(function (button) {
    button.addEventListener("click", function () {
      var targetId = button.getAttribute("data-booking-modal-close");
      if (!targetId) return;
      var targetModal = document.getElementById(targetId);
      if (targetModal) {
        targetModal.classList.add("hidden");
      }
    });
  });

  detailButtons.forEach(function (button) {
    button.addEventListener("click", function () {
      if (!detailModal || !detailFields) return;
      var dataset = button.dataset || {};
      var statusLabel = dataset.bookingStatusLabel || "-";
      var statusClass = dataset.bookingStatusClass || "pending";
      var reasonValue =
        dataset.bookingStatus === "2"
          ? dataset.bookingStatusReason || "ไม่ระบุเหตุผล"
          : "-";
      var approvalLabel = dataset.bookingApprovalLabel || "ผู้อนุมัติ";
      var approvalName = dataset.bookingApprovalName || "-";
      var approvalAt = dataset.bookingApprovalAt || "-";

      setDetailValue(detailFields.room, dataset.bookingRoom);
      setDetailValue(detailFields.date, dataset.bookingDate);
      setDetailValue(detailFields.time, dataset.bookingTime);
      var attendeesValue = (dataset.bookingAttendees || "").toString().trim();
      var attendeesLabel =
        attendeesValue !== "" && attendeesValue !== "-"
          ? attendeesValue + " คน"
          : "-";
      setDetailValue(detailFields.attendees, attendeesLabel);
      setDetailValue(detailFields.topic, dataset.bookingTopic);
      setDetailValue(detailFields.detail, dataset.bookingDetail, "ไม่มีรายละเอียดเพิ่มเติม");
      setDetailValue(detailFields.created, dataset.bookingCreated);
      setDetailValue(detailFields.updated, dataset.bookingUpdated);
      setDetailValue(detailFields.reason, reasonValue);
      setDetailValue(detailFields.approvalLabel, approvalLabel, "ผู้อนุมัติ");
      setDetailValue(detailFields.approvalName, approvalName);
      setDetailValue(detailFields.approvalAt, approvalAt);

      if (detailFields.status) {
        detailFields.status.textContent = statusLabel;
        detailFields.status.classList.remove("approved", "pending", "rejected");
        if (statusClass) {
          detailFields.status.classList.add(statusClass);
        }
      }

      if (detailFields.reasonRow) {
        detailFields.reasonRow.classList.toggle(
          "hidden",
          reasonValue === "-" || reasonValue === ""
        );
      }

      if (detailFields.approvalItem) {
        detailFields.approvalItem.classList.toggle(
          "hidden",
          dataset.bookingStatus === "0"
        );
      }

      if (detailFields.approvalAt) {
        detailFields.approvalAt.classList.toggle(
          "hidden",
          approvalAt === "-" || approvalAt === ""
        );
      }

      detailModal.classList.remove("hidden");
    });
  });

  deleteButtons.forEach(function (button) {
    button.addEventListener("click", function () {
      var bookingId = button.getAttribute("data-booking-id") || "";
      if (!bookingId) return;
      pendingDeleteId = bookingId;
      pendingDeleteRows = [];
      deleteButtons.forEach(function (item) {
        if (item.getAttribute("data-booking-id") === bookingId) {
          var row = item.closest("tr");
          if (row) {
            pendingDeleteRows.push(row);
          }
        }
      });
      if (deleteModal) {
        deleteModal.classList.remove("hidden");
      }
    });
  });

  function closeDeleteModal() {
    pendingDeleteRows = [];
    pendingDeleteId = "";
    if (deleteModal) {
      deleteModal.classList.add("hidden");
    }
  }

  function updateEmptyState(tbody) {
    var dataRows = Array.from(tbody.querySelectorAll("tr")).filter(function (row) {
      return !row.querySelector(".booking-empty");
    });
    if (dataRows.length === 0) {
      var emptyMessage =
        tbody.getAttribute("data-empty-message") || "ไม่พบข้อมูล";
      var table = tbody.closest("table");
      var colCount = table ? table.querySelectorAll("thead th").length : 1;
      tbody.innerHTML =
        '<tr><td colspan="' +
        colCount +
        '" class="booking-empty">' +
        emptyMessage +
        "</td></tr>";
    }
  }

  if (deleteConfirmButton) {
    deleteConfirmButton.addEventListener("click", function () {
      if (!pendingDeleteId) {
        closeDeleteModal();
        return;
      }

      var payload = {
        booking_id: pendingDeleteId,
        csrf_token: csrfToken,
      };

      if (!deleteEndpoint) {
        showBookingAlert("danger", "ไม่พบปลายทางบริการ", "กรุณาลองใหม่อีกครั้ง");
        closeDeleteModal();
        return;
      }

      deleteConfirmButton.disabled = true;
      deleteConfirmButton.textContent = "กำลังลบ...";

      fetch(deleteEndpoint, {
        method: "POST",
        headers: {
          "Content-Type": "application/json",
          "X-CSRF-Token": csrfToken,
        },
        body: JSON.stringify(payload),
      })
        .then(function (response) {
          return response.json().then(function (data) {
            return {
              ok: response.ok,
              data: data || {},
            };
          });
        })
        .then(function (payloadData) {
          var data = payloadData.data || {};
          if (!payloadData.ok) {
            if (data.html) {
              appendAlertHtml(data.html);
            } else {
              showBookingAlert(
                "danger",
                "ลบรายการไม่สำเร็จ",
                data.message || "กรุณาลองใหม่อีกครั้ง"
              );
            }
            closeDeleteModal();
            return;
          }

          closeDeleteModal();
          if (data.reload) {
            window.location.reload();
            return;
          }

          pendingDeleteRows.forEach(function (row) {
            row.remove();
          });
          document
            .querySelectorAll('tbody[data-empty-message]')
            .forEach(updateEmptyState);

          if (window.roomBookingEvents) {
            Object.keys(window.roomBookingEvents).forEach(function (key) {
              var nextEvents = (window.roomBookingEvents[key] || []).filter(
                function (eventItem) {
                  return eventItem.bookingId !== pendingDeleteId;
                }
              );
              if (nextEvents.length > 0) {
                window.roomBookingEvents[key] = nextEvents;
              } else {
                delete window.roomBookingEvents[key];
              }
            });
          }

          if (
            window.roomBookingCalendar &&
            typeof window.roomBookingCalendar.updateCalendar === "function"
          ) {
            window.roomBookingCalendar.updateCalendar();
          }

          if (data.html) {
            appendAlertHtml(data.html);
          } else {
            showBookingAlert(
              "success",
              "ลบรายการเรียบร้อยแล้ว",
              "รายการจองถูกลบออกจากระบบแล้ว"
            );
          }
        })
        .finally(function () {
          deleteConfirmButton.disabled = false;
          deleteConfirmButton.textContent = "ลบรายการ";
        });
    });
  }

  if (deleteCancelButton) {
    deleteCancelButton.addEventListener("click", closeDeleteModal);
  }

  if (bookingModal) {
    bookingModal.addEventListener("click", function (event) {
      if (event.target === bookingModal) {
        bookingModal.classList.add("hidden");
      }
    });
  }

  if (deleteModal) {
    deleteModal.addEventListener("click", function (event) {
      if (event.target === deleteModal) {
        closeDeleteModal();
      }
    });
  }
})();
