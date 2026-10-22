/**
 * Sistem Informasi Manajemen Karyawan & Absensi
 * JavaScript Interactive Handlers
 */

document.addEventListener('DOMContentLoaded', function () {
  // 1. Live Server Digital Clock
  const clockElements = document.querySelectorAll('.live-clock');
  if (clockElements.length > 0) {
    function updateClock() {
      const now = new Date();
      const hours = String(now.getHours()).padStart(2, '0');
      const minutes = String(now.getMinutes()).padStart(2, '0');
      const seconds = String(now.getSeconds()).padStart(2, '0');
      const timeStr = `${hours}:${minutes}:${seconds} WIB`;
      clockElements.forEach(el => el.textContent = timeStr);
    }
    updateClock();
    setInterval(updateClock, 1000);
  }

  // 2. Toggle Password Visibility
  const togglePassBtns = document.querySelectorAll('.toggle-password');
  togglePassBtns.forEach(btn => {
    btn.addEventListener('click', function () {
      const targetId = this.getAttribute('data-target');
      const input = document.getElementById(targetId);
      if (input) {
        if (input.type === 'password') {
          input.type = 'text';
          this.classList.remove('fa-eye');
          this.classList.add('fa-eye-slash');
        } else {
          input.type = 'password';
          this.classList.remove('fa-eye-slash');
          this.classList.add('fa-eye');
        }
      }
    });
  });

  // 3. Admin Sidebar Toggle for Mobile View
  const sidebarToggle = document.getElementById('sidebarToggle');
  const sidebar = document.querySelector('.sidebar');
  const sidebarBackdrop = document.getElementById('sidebarBackdrop');
  const sidebarCloseBtn = document.getElementById('sidebarCloseBtn');

  function openSidebar() {
    if (sidebar) sidebar.classList.add('show');
    if (sidebarBackdrop) sidebarBackdrop.classList.add('show');
    document.body.style.overflow = 'hidden'; // Lock background scroll on mobile
  }

  function closeSidebar() {
    if (sidebar) sidebar.classList.remove('show');
    if (sidebarBackdrop) sidebarBackdrop.classList.remove('show');
    document.body.style.overflow = '';
  }

  if (sidebarToggle && sidebar) {
    sidebarToggle.addEventListener('click', function (e) {
      e.stopPropagation();
      if (sidebar.classList.contains('show')) {
        closeSidebar();
      } else {
        openSidebar();
      }
    });

    if (sidebarCloseBtn) {
      sidebarCloseBtn.addEventListener('click', closeSidebar);
    }

    if (sidebarBackdrop) {
      sidebarBackdrop.addEventListener('click', closeSidebar);
    }

    window.addEventListener('resize', function () {
      if (window.innerWidth > 992 && sidebar.classList.contains('show')) {
        closeSidebar();
      }
    });
  }

  // 4. Alert Auto Close
  const alertCloses = document.querySelectorAll('.alert-close');
  alertCloses.forEach(btn => {
    btn.addEventListener('click', function () {
      const alert = this.closest('.alert');
      if (alert) {
        alert.style.transition = 'opacity 0.2s ease, transform 0.2s ease';
        alert.style.opacity = '0';
        alert.style.transform = 'translateY(-10px)';
        setTimeout(() => alert.remove(), 200);
      }
    });
  });

  // 5. Delete Confirmation Modal
  const deleteModal = document.getElementById('deleteConfirmModal');
  const deleteConfirmForm = document.getElementById('deleteConfirmForm');
  const deleteItemName = document.getElementById('deleteItemName');
  const deleteButtons = document.querySelectorAll('.btn-confirm-delete');

  deleteButtons.forEach(btn => {
    btn.addEventListener('click', function (e) {
      e.preventDefault();
      const actionUrl = this.getAttribute('data-url');
      const name = this.getAttribute('data-name') || 'data ini';
      
      if (deleteModal && deleteConfirmForm) {
        deleteConfirmForm.action = actionUrl;
        if (deleteItemName) deleteItemName.textContent = name;
        deleteModal.classList.add('show');
      } else {
        if (confirm(`Apakah Anda yakin ingin menghapus ${name}? Tindakan ini tidak dapat dibatalkan.`)) {
          window.location.href = actionUrl;
        }
      }
    });
  });

  // Modal close trigger
  const modalCloses = document.querySelectorAll('[data-close-modal]');
  modalCloses.forEach(btn => {
    btn.addEventListener('click', function () {
      const modal = this.closest('.modal-overlay');
      if (modal) modal.classList.remove('show');
    });
  });
});

