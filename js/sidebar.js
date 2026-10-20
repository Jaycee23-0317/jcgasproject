function toggleSidebar(sidebar) {
  const layout = document.getElementById("layoutWrapper");
  if (!layout) return;

  layout.classList.toggle("collapsed");

  const nowCollapsed = layout.classList.contains("collapsed");

  localStorage.setItem("sidebarCollapsed", nowCollapsed);
}

function toggleSubmenu(element) {
    // Check if the parent layout wrapper has the collapsed class
    const isCollapsed = document.getElementById('layoutWrapper').classList.contains('collapsed');
    
    // If it's collapsed, don't open the vertical dropdown accordion
    if (isCollapsed) return;

    const wrapper = element.parentElement;
    const submenu = wrapper.querySelector('.submenu-list');
    const arrow = element.querySelector('.submenu-arrow');
    
    if (submenu.style.display === 'block') {
        submenu.style.display = 'none';
        arrow.classList.remove('rotate');
        wrapper.classList.remove('submenu-open');
    } else {
        submenu.style.display = 'block';
        arrow.classList.add('rotate');
        wrapper.classList.add('submenu-open');
    }
}

function handleDropdownClick(element, event) {
    const isCollapsed = document.getElementById('layoutWrapper').classList.contains('collapsed');
    
    if (isCollapsed) {
        // PRO-TIP ACTION: If menu is small, grab shortcut and navigate directly
        const fallbackUrl = element.getAttribute('data-fallback');
        if (fallbackUrl) {
            window.location.href = fallbackUrl;
        }
        return;
    }

    // Standard dropdown toggle logic for expanded mode
    const wrapper = element.parentElement;
    const submenu = wrapper.querySelector('.submenu-list');
    const arrow = element.querySelector('.submenu-arrow');
    
    if (submenu.style.display === 'block') {
        submenu.style.display = 'none';
        arrow.classList.remove('rotate');
        wrapper.classList.remove('submenu-open');
    } else {
        submenu.style.display = 'block';
        arrow.classList.add('rotate');
        wrapper.classList.add('submenu-open');
    }
}

document.addEventListener("DOMContentLoaded", function () {
  const layout = document.getElementById("layoutWrapper");

  if (layout) {
    const savedState = localStorage.getItem("sidebarCollapsed");
    if (savedState === "true") {
      layout.classList.add("collapsed");
    } else if (savedState === "false") {
      layout.classList.remove("collapsed");
    }
  }
});

document.addEventListener("DOMContentLoaded", () => {
  const logoutTrigger = document.getElementById("logoutTriggerBtn");
  const confirmLogout = document.getElementById("confirmLogoutBtn");

  if (logoutTrigger && confirmLogout) {
    const modalElement = document.getElementById("logoutConfirmModal");
    const bootstrapLogoutModal = new bootstrap.Modal(modalElement);

    logoutTrigger.addEventListener("click", (event) => {
      event.preventDefault(); // Stop the standard '#' hashtag browser jump
      bootstrapLogoutModal.show(); // Trigger the static backdrop panel window
    });

    confirmLogout.addEventListener("click", () => {
      confirmLogout.disabled = true;
      confirmLogout.innerHTML = `<span class="spinner-border spinner-border-sm me-1" role="status" aria-hidden="true"></span> Logging out...`;

      window.location.href = "../php/logout.php";
    });
  }
});
