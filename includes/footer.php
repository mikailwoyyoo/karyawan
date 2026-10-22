<?php
/**
 * Layout Footer
 */
?>
  <!-- Global Modal Konfirmasi Hapus Data -->
  <div class="modal-overlay" id="deleteConfirmModal">
    <div class="modal-box">
      <div class="modal-header">
        <h4 class="modal-title"><i class="fa-solid fa-triangle-exclamation text-danger" style="margin-right: 8px;"></i> Konfirmasi Penghapusan</h4>
        <button type="button" class="modal-close" data-close-modal>&times;</button>
      </div>
      <div class="modal-body">
        <p>Apakah Anda yakin ingin menghapus data <strong id="deleteItemName" style="color: var(--primary-dark);">ini</strong>?</p>
        <p style="margin-top: 8px; font-size: 13px; color: var(--danger);">Tindakan ini tidak dapat dibatalkan dan seluruh relasi data terkait akan terhapus secara permanen.</p>
      </div>
      <div class="modal-footer">
        <button type="button" class="btn btn-outline" data-close-modal>Batal</button>
        <form id="deleteConfirmForm" method="POST" action="" style="display:inline;">
          <?= csrf_field() ?>
          <button type="submit" class="btn btn-danger">Ya, Hapus Data</button>
        </form>
      </div>
    </div>
  </div>

  <!-- Custom Scripts -->
  <script src="<?= base_url('assets/js/script.js') ?>"></script>
</body>
</html>

