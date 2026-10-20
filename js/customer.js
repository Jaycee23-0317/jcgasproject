function toggleCartSidebar(sidebar) {
  const layout = document.getElementById("shopLayout");
  const cartSidebar = document.getElementById("cartSidebar");
  const isLayoutCollapsed = layout ? layout.classList.contains("collapsed") : false;

  if (!layout) return;

  if (sidebar === "cartSidebar") {
      if (!cartSidebar.classList.contains("d-none") && !isLayoutCollapsed) {
          layout.classList.add("collapsed");
          document.getElementById("sidebar-cart").classList.remove("active");
      } else {
          cartSidebar.classList.remove("d-none");
          
          layout.classList.remove("collapsed");
          document.getElementById("sidebar-cart").classList.add("active");
      }
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