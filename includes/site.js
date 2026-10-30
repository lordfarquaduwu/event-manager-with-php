// Centralized site JavaScript for the app

// Sidebar toggle script: handles both mobile (open) and desktop (collapsed) states
(function () {
  function initSidebar() {
    var toggle = document.getElementById("sidebar-toggle");
    var overlay = document.getElementById("sidebar-overlay");
    function isSmall() {
      return window.matchMedia("(max-width:900px)").matches;
    }
    function setOpen(open) {
      if (isSmall()) {
        if (open) document.body.classList.add("sidebar-open");
        else document.body.classList.remove("sidebar-open");
        try {
          localStorage.setItem("sidebarOpen", open ? "1" : "0");
        } catch (e) {}
      } else {
        if (open) document.body.classList.remove("sidebar-collapsed");
        else document.body.classList.add("sidebar-collapsed");
        try {
          localStorage.setItem("sidebarCollapsed", open ? "0" : "1");
        } catch (e) {}
      }
      updateToggleAria();
    }
    function updateToggleAria() {
      if (!toggle) return;
      var expanded = isSmall()
        ? document.body.classList.contains("sidebar-open")
        : !document.body.classList.contains("sidebar-collapsed");
      toggle.setAttribute("aria-expanded", expanded ? "true" : "false");
    }

    // Initialize state from storage
    try {
      if (isSmall()) {
        var pref = localStorage.getItem("sidebarOpen");
        if (pref === "1") document.body.classList.add("sidebar-open");
      } else {
        var pref2 = localStorage.getItem("sidebarCollapsed");
        if (pref2 === "1") document.body.classList.add("sidebar-collapsed");
      }
    } catch (e) {}

    if (toggle) {
      toggle.addEventListener("click", function () {
        if (isSmall())
          setOpen(!document.body.classList.contains("sidebar-open"));
        else setOpen(document.body.classList.contains("sidebar-collapsed"));
      });
    }
    if (overlay) {
      overlay.addEventListener("click", function () {
        setOpen(false);
      });
    }
    // Close mobile sidebar when resizing to large screens
    window.addEventListener("resize", function () {
      if (!isSmall()) document.body.classList.remove("sidebar-open");
      updateToggleAria();
    });
    updateToggleAria();
  }

  if (document.readyState === "loading")
    document.addEventListener("DOMContentLoaded", initSidebar);
  else initSidebar();
})();

// Toggle registrations list on manage users page
function toggleRegs(id) {
  var el = document.getElementById("regs-" + id);
  if (!el) return;
  el.style.display =
    el.style.display === "none" || el.style.display === "" ? "block" : "none";
}

// Login form validation used on index.php
function isvalid() {
  var f = document.forms["form"];
  if (!f) return true;
  var email = f.user.value;
  var pass = f.pass.value;

  if (
    (email === undefined || email === "") &&
    (pass === undefined || pass === "")
  ) {
    alert("Email and password fields are empty");
    return false;
  }
  if (email === undefined || email === "") {
    alert("Email is empty");
    return false;
  }
  if (pass === undefined || pass === "") {
    alert("Password field is empty");
    return false;
  }
  return true;
}

// Register button behavior (events.php)
(function () {
  function initRegisterBtn() {
    var btn = document.getElementById("registerBtn");
    if (!btn) return;
    function setBtnState(registered) {
      if (registered) {
        btn.innerHTML =
          '\u2713 Registered <small style="font-weight:600">(click to unregister)</small>';
        btn.style.background = "#2e7d32";
        btn.style.color = "#fff";
        btn.setAttribute("aria-pressed", "true");
        btn.setAttribute("data-registered", "1");
        btn.title = "Click to unregister from this event";
      } else {
        btn.innerHTML = "Register";
        btn.style.background = "";
        btn.style.color = "";
        btn.setAttribute("aria-pressed", "false");
        btn.setAttribute("data-registered", "0");
        btn.title = "Click to register for this event";
      }
      btn.disabled = false;
    }

    setBtnState(btn.getAttribute("data-registered") === "1");

    btn.addEventListener("click", function (e) {
      var eventId = btn.getAttribute("data-event-id");
      if (!eventId) return;
      var currentlyRegistered = btn.getAttribute("data-registered") === "1";
      setBtnState(!currentlyRegistered);

      var action = currentlyRegistered ? "unregister" : "register";
      btn.disabled = true;

      fetch(window.location.pathname + window.location.search, {
        method: "POST",
        headers: { "Content-Type": "application/x-www-form-urlencoded" },
        body:
          "action=" +
          encodeURIComponent(action) +
          "&event_id=" +
          encodeURIComponent(eventId),
      })
        .then(function (resp) {
          return resp.text().then(function (text) {
            try {
              return JSON.parse(text);
            } catch (e) {
              throw new Error("Invalid JSON response from server: " + text);
            }
          });
        })
        .then(function (data) {
          if (data && data.success) {
            setBtnState(data.registered === true);
          } else {
            setBtnState(currentlyRegistered);
            alert(data && data.message ? data.message : "Registration failed");
          }
        })
        .catch(function (err) {
          setBtnState(currentlyRegistered);
          alert(err && err.message ? err.message : "Network error");
        });
    });
  }
  if (document.readyState === "loading")
    document.addEventListener("DOMContentLoaded", initRegisterBtn);
  else initRegisterBtn();
})();

// Feedback form AJAX handler (events.php)
(function () {
  function initFeedback() {
    var f = document.getElementById("feedbackForm");
    if (!f) return;
    f.addEventListener("submit", function (ev) {
      ev.preventDefault();
      var formData = new FormData(f);
      var body = new URLSearchParams();
      for (var pair of formData.entries()) body.append(pair[0], pair[1]);
      fetch(window.location.pathname + window.location.search, {
        method: "POST",
        headers: { "Content-Type": "application/x-www-form-urlencoded" },
        body: body.toString(),
      })
        .then(function (resp) {
          return resp.json();
        })
        .then(function (data) {
          if (data && data.success) {
            alert("Thank you — your feedback was saved.");
            window.location.reload();
          } else {
            alert(
              data && data.message ? data.message : "Failed to save feedback"
            );
          }
        })
        .catch(function (err) {
          alert("Network error: " + (err.message || err));
        });
    });
  }
  if (document.readyState === "loading")
    document.addEventListener("DOMContentLoaded", initFeedback);
  else initFeedback();
})();
