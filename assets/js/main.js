/**
 * Main Application Script
 * Level: Production
 * Structure: Module Pattern (IIFE)
 */
window.addEventListener("load", function () {
  const overlay = document.getElementById("preloader-overlay");

  overlay.classList.add("preloader-hidden");

  overlay.addEventListener("transitionend", function () {
    overlay.remove();
  });
});

// Show password FEATURE FUTURE
// document.addEventListener("DOMContentLoaded", function () {
//   const eyeicon = document.getElementById("eyeicon");
//   const password = document.getElementById("password-toggle");

//   if(!eyeicon || !password){
//     return ;
//   }

//   eyeicon.onclick = function () {
//     if (password.type === "password") {
//       password.type = "text";
//       eyeicon.classList.remove("fa-eye-slash");
//       eyeicon.classList.add("fa-eye");
//     } else {
//       password.type = "password";
//       eyeicon.classList.remove("fa-eye");
//       eyeicon.classList.add("fa-eye-slash");
//     }
//   };
// });

//Calendar
const monthYear = document.getElementById("month-year");
const dates = document.getElementById("dates-calendar");
const prevBtn = document.getElementById("prev-btn");
const nextBtn = document.getElementById("next-btn");

let currentDate = new Date();

const updateCalendar = () => {
  const year = currentDate.getFullYear();
  const month = currentDate.getMonth();

  const firstDay = new Date(year, month, 1);
  const lastDay = new Date(year, month + 1, 0);

  const totalDay = lastDay.getDate();
  const firstDayIndex = firstDay.getDay();
  const lastDayIndex = lastDay.getDay();

  const monthYearString = currentDate.toLocaleString("th-TH", {
    month: "long",
    year: "numeric",
  });
  monthYear.textContent = monthYearString;

  let html = "";

  for (let i = 0; i < firstDayIndex; i++) {
    html += `<div class="date inactive"></div>`;
  }

  for (let i = 1; i <= totalDay; i++) {
    const date = new Date(year, month, i);
    const isToday = date.toDateString() === new Date().toDateString();
    const event = isToday ? "date active" : "date";
    html += `<div class="${event}">${i}</div>`;
  }

  for (let i = lastDayIndex + 1; i <= 6; i++) {
    html += `<div class="date inactive"></div>`;
  }

  dates.innerHTML = html;
};

prevBtn.addEventListener("click", () => {
  currentDate.setMonth(currentDate.getMonth() - 1);
  updateCalendar();
});
nextBtn.addEventListener("click", () => {
  currentDate.setMonth(currentDate.getMonth() + 1);
  updateCalendar();
});

updateCalendar();
