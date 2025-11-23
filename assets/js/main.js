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
