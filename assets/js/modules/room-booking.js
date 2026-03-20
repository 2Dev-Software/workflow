(function () {
  var root = document.querySelector("[data-room-booking]");
  if (!root) {
    return;
  }

  var bookingModal = document.getElementById("bookingListModal");
  var detailModal = document.getElementById("bookingDetailModal");
  var openButtons = document.querySelectorAll("[data-booking-modal-open]");
  var closeButtons = document.querySelectorAll("[data-booking-modal-close]");
  var detailButtons = document.querySelectorAll('[data-booking-action="detail"]');
  var deleteButtons = document.querySelectorAll('[data-booking-action="delete"]');
  var bookingForm = document.querySelector(".booking-form");
  var checkButton = bookingForm
    ? bookingForm.querySelector('button[name="room_booking_check"]')
    : null;
  var deleteEndpoint = root.getAttribute("data-delete-endpoint") || "";
  var csrfToken = root.getAttribute("data-csrf") || "";
  var loadingApi = window.App && window.App.loading ? window.App.loading : null;
  var checkEndpoint =
    root.getAttribute("data-check-endpoint") ||
    "public/api/room-booking-check.php";
  var checkLoadingTarget =
    root.querySelector(".booking-form-card") || bookingForm || root;
  var listLoadingTarget =
    root.querySelector(".booking-list-card .table-responsive") ||
    root.querySelector(".booking-list-card") ||
    root;

  function getAlertsApi() {
    return window.AppAlerts || null;
  }

  function appendAlertHtml(html) {
    if (!html) return;
    var temp = document.createElement("div");
    temp.innerHTML = html;
    var payloadNode = temp.querySelector("[data-app-alert]");

    if (payloadNode) {
      var consumeApi = getAlertsApi();
      if (consumeApi && typeof consumeApi.consumePayloadElement === "function") {
        consumeApi.consumePayloadElement(payloadNode);
        return;
      }
      var payloadRaw = payloadNode.getAttribute("data-app-alert") || "";
      try {
        var payload = JSON.parse(payloadRaw);
        if (payload) {
          showBookingAlert(
            payload.type || "info",
            payload.title || "แจ้งเตือน",
            payload.message || ""
          );
        }
      } catch (error) {
        showBookingAlert("danger", "ระบบขัดข้อง", "กรุณาลองใหม่อีกครั้ง");
      }
      return;
    }
  }

  function showBookingAlert(type, title, message) {
    var alertsApi = getAlertsApi();
    if (alertsApi && typeof alertsApi.fire === "function") {
      alertsApi.fire({
        type: type,
        title: title,
        message: message || "",
      });
      return;
    }
    console.warn((title || "แจ้งเตือน") + (message ? "\n" + message : ""));
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

      if (typeof bookingForm.reportValidity === "function" && !bookingForm.reportValidity()) {
        return;
      }

      var formData = new FormData(bookingForm);
      formData.set("room_booking_check", "1");
      formData.delete("room_booking_save");

      if (loadingApi) {
        loadingApi.startComponent(checkLoadingTarget);
      }

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
        })
        .finally(function () {
          if (loadingApi) {
            loadingApi.stopComponent(checkLoadingTarget);
          }
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

  function setDeleteButtonsDisabled(buttons, disabled) {
    buttons.forEach(function (actionButton) {
      actionButton.disabled = !!disabled;
    });
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

  function confirmDeleteBooking() {
    var alertsApi = getAlertsApi();
    if (alertsApi && typeof alertsApi.confirm === "function") {
      return alertsApi.confirm("ต้องการลบรายการจองนี้ใช่หรือไม่", {
        title: "ยืนยันการลบรายการจอง",
        type: "warning",
        confirmButtonText: "ลบรายการ",
        cancelButtonText: "ยกเลิก",
      });
    }
    console.warn("Room booking confirm dialog unavailable");
    return Promise.resolve(false);
  }

  function executeDeleteBooking(bookingId, bookingRows, actionButtons) {
    var payload = {
      booking_id: bookingId,
      csrf_token: csrfToken,
    };

    if (!deleteEndpoint) {
      showBookingAlert("danger", "ไม่พบปลายทางบริการ", "กรุณาลองใหม่อีกครั้ง");
      return;
    }

    setDeleteButtonsDisabled(actionButtons, true);

    if (loadingApi) {
      loadingApi.startComponent(listLoadingTarget);
    }

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
          return;
        }

        if (data.reload) {
          window.location.reload();
          return;
        }

        bookingRows.forEach(function (row) {
          row.remove();
        });
        document
          .querySelectorAll('tbody[data-empty-message]')
          .forEach(updateEmptyState);

        if (window.roomBookingEvents) {
          Object.keys(window.roomBookingEvents).forEach(function (key) {
            var nextEvents = (window.roomBookingEvents[key] || []).filter(
              function (eventItem) {
                return eventItem.bookingId !== bookingId;
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
      .catch(function () {
        showBookingAlert("danger", "ลบรายการไม่สำเร็จ", "กรุณาลองใหม่อีกครั้ง");
      })
      .finally(function () {
        if (loadingApi) {
          loadingApi.stopComponent(listLoadingTarget);
        }
        setDeleteButtonsDisabled(actionButtons, false);
      });
  }

  deleteButtons.forEach(function (button) {
    button.addEventListener("click", function () {
      var bookingId = button.getAttribute("data-booking-id") || "";
      if (!bookingId) return;

      var bookingRows = [];
      var actionButtons = [];

      deleteButtons.forEach(function (item) {
        if (item.getAttribute("data-booking-id") !== bookingId) {
          return;
        }

        actionButtons.push(item);

        var row = item.closest("tr");
        if (row) {
          bookingRows.push(row);
        }
      });

      confirmDeleteBooking().then(function (approved) {
        if (!approved) {
          return;
        }
        executeDeleteBooking(bookingId, bookingRows, actionButtons);
      });
    });
  });

  if (bookingModal) {
    bookingModal.addEventListener("click", function (event) {
      if (event.target === bookingModal) {
        bookingModal.classList.add("hidden");
      }
    });
  }
})();
