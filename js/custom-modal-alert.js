
// class CustomModalAlert {
//   constructor() {
//     this.modal = null;
//     this.initEventListeners();
//   }

//   initEventListeners() {
//     document.addEventListener('click', (e) => {
//       if (e.target.classList.contains('modal-backdrop') && 
//           !e.target.closest('.modal[id]') && // Skip Bootstrap modals with ID
//           !e.target.closest('.bootstrapModal')) { // Skip bootstrap modals from this class
//         this.close();
//       }
//     });

//     document.addEventListener('keydown', (e) => {
//       // Only close if our custom modal exists
//       if (e.key === 'Escape' && this.modal && 
//           !document.querySelector('.modal.show[id*="cancelOrderModal"]')) {
//         this.close();
//       }
//     });
//   }

//   show(title, message, type = 'info', options = {}) {
//     const { redirect = null, timeout = null, showClose = true } = options;

//     // Remove existing modal
//     this.close();

//     // Create modal HTML
//     this.modal = document.createElement('div');
//     this.modal.className = `modal-dialog modal-dialog-centered-${type}`;
//     this.modal.innerHTML = `
//       <div class="modal-dialog">
//         <div class="modal-content">
//           <div class="modal-header">
//             <div class="modal-icon modal-icon-${type}">
//               <i class="bi ${type === 'success' ? 'bi-check-circle-fill' : type === 'error' ? 'bi-x-circle-fill' : 'bi-info-circle-fill'}"></i>
//             </div>
//             <h5 class="modal-title">${this.escapeHtml(title)}</h5>
//             ${showClose ? '<button type="button" class="btn-close" data-dismiss="modal"></button>' : ''}
//           </div>
//           <div class="modal-body">
//             <p>${this.escapeHtml(message)}</p>
//           </div>
//           <div class="modal-footer">
//             <button type="button" class="btn btn-primary" onclick="customModalAlert.close('${redirect || ''}')">
//               OK
//             </button>
//           </div>
//         </div>
//       </div>
//     `;

//     document.body.appendChild(this.modal);

//     // Focus and accessibility
//     this.modal.querySelector('.modal-title').focus();

//     // Auto close/redirect
//     if (timeout) {
//       setTimeout(() => this.close(redirect), timeout);
//     }

//     // Animate in
//     setTimeout(() => this.modal.classList.add('show'), 10);
//   }

//   close(redirect = null) {
//     if (this.modal) {
//       this.modal.classList.remove('show');
//       setTimeout(() => {
//         if (this.modal) {
//           this.modal.remove();
//           this.modal = null;
//         }
//         if (redirect) window.location.href = redirect;
//       }, 300);
//     }
//   }

//   escapeHtml(text) {
//     const map = {
//       '&': '&amp;',
//       '<': '<',
//       '>': '>',
//       '"': '"',
//       "'": '&#039;'
//     };
//     return text.replace(/[&<>"'"]/g, m => map[m]);
//   }

//   showBootstrapModal(title, message, type = 'info', options = {}) {
//     const { redirect = null, timeout = null, showClose = true } = options;

//     // Remove existing bootstrap modal
//     this.closeBootstrapModal();

//     // Create Bootstrap modal using exact structure from custom-modal-alert.html
//     this.bootstrapModal = document.createElement('div');
//     this.bootstrapModal.className = `modal fade ${type === 'success' ? 'modal-success' : type === 'error' ? 'modal-error' : 'modal-info'}`;
//     this.bootstrapModal.innerHTML = `
//       <!-- Modal -->
//       <div class="modal-dialog modal-dialog-centered" role="document">
//         <div class="modal-content">
//           <div class="modal-header ${type === 'success' ? 'bg-success text-white' : type === 'error' ? 'bg-danger text-white' : 'bg-primary text-white'}">
//             <h5 class="modal-title" id="bootstrapModalLabel">${this.escapeHtml(title)}</h5>
//             ${showClose ? '<button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal" aria-label="Close"></button>' : ''}
//           </div>
//           <div class="modal-body">
//             <p>${this.escapeHtml(message)}</p>
//           </div>
//           <div class="modal-footer">
//             <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Close</button>
//             <button type="button" class="btn btn-primary" id="bootstrapModalOkBtn">${redirect ? 'Proceed' : 'OK'}</button>
//           </div>
//         </div>
//       </div>
//     `;

//     document.body.appendChild(this.bootstrapModal);

//     // Bootstrap 5 show
//     const modal = new bootstrap.Modal(this.bootstrapModal);
//     modal.show();

//     // Handle OK button
//     const okBtn = this.bootstrapModal.querySelector('#bootstrapModalOkBtn');
//     okBtn.addEventListener('click', () => {
//       modal.hide();
//       if (timeout) {
//         setTimeout(() => this.closeBootstrapModal(redirect), 300);
//       } else {
//         this.closeBootstrapModal(redirect);
//       }
//     });

//     // Auto close
//     if (timeout) {
//       setTimeout(() => {
//         modal.hide();
//         setTimeout(() => this.closeBootstrapModal(redirect), 300);
//       }, timeout);
//     }

//     // Store modal instance
//     this.currentBootstrapModal = modal;
//   }

//   closeBootstrapModal(redirect = null) {
//     if (this.currentBootstrapModal) {
//       this.currentBootstrapModal.hide();
//     }
//     if (this.bootstrapModal) {
//       this.bootstrapModal.remove();
//       this.bootstrapModal = null;
//     }
//     this.currentBootstrapModal = null;
//     if (redirect) window.location.href = redirect;
//   }
// }

// // Global instance and shortcuts
// const customModalAlert = new CustomModalAlert();

// // Shortcuts for original
// window.showModalAlert = customModalAlert.show.bind(customModalAlert);
// window.showSuccessAlert = (msg, options = {}) => showModalAlert('Success', msg, 'success', options);
// window.showErrorAlert = (msg, options = {}) => showModalAlert('Error', msg, 'error', options);
// window.showInfoAlert = (msg, options = {}) => showModalAlert('Info', msg, 'info', options);

// // NEW Bootstrap Modal Shortcuts - Uses exact HTML from custom-modal-alert.html
// window.showBootstrapModal = customModalAlert.showBootstrapModal.bind(customModalAlert);
// window.showBootstrapSuccess = (msg, options = {}) => showBootstrapModal('Success', msg, 'success', options);
// window.showBootstrapError = (msg, options = {}) => showBootstrapModal('Error', msg, 'error', options);
// window.showBootstrapInfo = (msg, options = {}) => showBootstrapModal('Info', msg, 'info', options);
// window.showBootstrapConfirm = (title, message, options = {}) => showBootstrapModal(title, message, 'info', options);

// // Auto-detect server-side script calls
// document.addEventListener('DOMContentLoaded', () => {
//   Array.from(document.scripts).forEach(script => {
//     if (script.textContent.match(/showModalAlert\s*\(/)) {
//       eval(script.textContent);
//     }
//   });
// });

// console.log('Custom Modal Alert loaded ✅ - Use showModalAlert(title, message, type)');

