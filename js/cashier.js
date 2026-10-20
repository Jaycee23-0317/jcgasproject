function toggleCartSidebar(sidebar) {
  const layout = document.getElementById("shopLayout");
  const incomingOrdersSidebar = document.getElementById("incomingOrdersSidebar");
  const cartSidebar = document.getElementById("cartSidebar");
  const isLayoutCollapsed = layout ? layout.classList.contains("collapsed") : false;

  if (!layout) return;

  if (sidebar === "cartSidebar") {
      if (!cartSidebar.classList.contains("d-none") && !isLayoutCollapsed) {
          layout.classList.add("collapsed");
          document.getElementById("sidebar-cart").classList.remove("active");
      } else {
          incomingOrdersSidebar.classList.add("d-none");
          cartSidebar.classList.remove("d-none");
          
          layout.classList.remove("collapsed");
          document.getElementById("sidebar-cart").classList.add("active");
          document.getElementById("sidebar-incomingOrders").classList.remove("active");
      }
  } else if (sidebar === "incomingOrdersSidebar") {
      if (!incomingOrdersSidebar.classList.contains("d-none") && !isLayoutCollapsed) {
          layout.classList.add("collapsed");
          document.getElementById("sidebar-incomingOrders").classList.remove("active");
      } else {
          incomingOrdersSidebar.classList.remove("d-none");
          cartSidebar.classList.add("d-none");
          
          layout.classList.remove("collapsed");
          document.getElementById("sidebar-incomingOrders").classList.add("active");
          document.getElementById("sidebar-cart").classList.remove("active");
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