<!-- Modal Disposisi (Step C4) -->
<div class="modal fade" id="modalDisposisi" tabindex="-1" aria-labelledby="modalDisposisiLabel" aria-hidden="true">
    <div class="modal-dialog modal-lg">
        <div class="modal-content">
            <form action="disposisi_kirim.php" method="POST" id="formDisposisi">
                <div class="modal-header bg-primary text-white">
                    <h5 class="modal-title fw-bold" id="modalDisposisiLabel">
                        <i class="fa-solid fa-paper-plane me-2"></i>Lembar Disposisi
                    </h5>
                    <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal" aria-label="Close"></button>
                </div>
                <div class="modal-body p-4">
                    <!-- Hidden Fields -->
                    <input type="hidden" name="id_sm" value="<?= $surat['id_sm'] ?>">
                    <input type="hidden" name="uuid_surat" value="<?= $surat['id_sm'] ?>"> <!-- Using ID_SM as UUID for now -->
                    
                    <div class="row g-3">
                        <!-- Sifat Surat -->
                        <div class="col-md-6">
                            <label class="form-label fw-bold">Sifat Disposisi</label>
                            <select class="form-select" name="sifat">
                                <option value="BIASA">Biasa</option>
                                <option value="SEGERA">Segera</option>
                                <option value="SANGAT_SEGERA">Sangat Segera</option>
                                <option value="RAHASIA">Rahasia</option>
                            </select>
                        </div>
                        
                        <!-- Batas Waktu -->
                        <div class="col-md-6">
                            <label class="form-label fw-bold">Batas Waktu (Deadline)</label>
                            <input type="date" class="form-control" name="deadline" min="<?= date('Y-m-d') ?>">
                        </div>

                        <!-- Penerima Disposisi -->
                        <div class="col-12">
                            <label class="form-label fw-bold">Diteruskan Kepada</label>
                            <div class="card p-3 bg-light border-0">
                                <?php
                                // Fetch Subordinates (Sekcam, Kasi, Kasubag)
                                // Assuming $db is available from parent
                                $stmtUser = $db->query("SELECT id_user, nama_lengkap, role, j.nama_jabatan 
                                                        FROM users u
                                                        LEFT JOIN jabatan j ON u.id_jabatan = j.id_jabatan
                                                        WHERE u.role IN ('sekcam', 'kasi', 'kasubag') 
                                                        AND u.is_active = 1
                                                        ORDER BY u.role, u.nama_lengkap");
                                $users = $stmtUser->fetchAll();
                                ?>
                                <div class="row">
                                    <?php foreach ($users as $u): ?>
                                    <div class="col-md-6 mb-2">
                                        <div class="form-check">
                                            <input class="form-check-input" type="checkbox" name="penerima[]" value="<?= $u['id_user'] ?>" id="user_<?= $u['id_user'] ?>">
                                            <label class="form-check-label" for="user_<?= $u['id_user'] ?>">
                                                <strong><?= htmlspecialchars($u['nama_lengkap']) ?></strong>
                                                <div class="small text-muted"><?= $u['nama_jabatan'] ?></div>
                                            </label>
                                        </div>
                                    </div>
                                    <?php endforeach; ?>
                                </div>
                            </div>
                        </div>

                        <!-- Instruksi -->
                        <div class="col-12">
                            <label class="form-label fw-bold">Instruksi / Catatan</label>
                            <div class="row">
                                <div class="col-md-6">
                                    <div class="form-check mb-1">
                                        <input class="form-check-input" type="checkbox" name="instruksi[]" value="Tindak Lanjuti" id="ins1">
                                        <label class="form-check-label" for="ins1">Tindak Lanjuti</label>
                                    </div>
                                    <div class="form-check mb-1">
                                        <input class="form-check-input" type="checkbox" name="instruksi[]" value="Untuk Diketahui" id="ins2">
                                        <label class="form-check-label" for="ins2">Untuk Diketahui</label>
                                    </div>
                                    <div class="form-check mb-1">
                                        <input class="form-check-input" type="checkbox" name="instruksi[]" value="Selesaikan" id="ins3">
                                        <label class="form-check-label" for="ins3">Selesaikan</label>
                                    </div>
                                </div>
                                <div class="col-md-6">
                                    <div class="form-check mb-1">
                                        <input class="form-check-input" type="checkbox" name="instruksi[]" value="Telaah / Saran" id="ins4">
                                        <label class="form-check-label" for="ins4">Telaah / Saran</label>
                                    </div>
                                    <div class="form-check mb-1">
                                        <input class="form-check-input" type="checkbox" name="instruksi[]" value="Siapkan Jawaban" id="ins5">
                                        <label class="form-check-label" for="ins5">Siapkan Jawaban</label>
                                    </div>
                                    <div class="form-check mb-1">
                                        <input class="form-check-input" type="checkbox" name="instruksi[]" value="Koordinasikan" id="ins6">
                                        <label class="form-check-label" for="ins6">Koordinasikan</label>
                                    </div>
                                </div>
                            </div>
                            <textarea name="catatan" class="form-control mt-2" rows="3" placeholder="Tambahan catatan khusus..."></textarea>
                        </div>
                    </div>
                </div>
                <div class="modal-footer bg-light">
                    <button type="button" class="btn btn-link text-muted text-decoration-none" data-bs-dismiss="modal">Batal</button>
                    <button type="submit" name="kirim_disposisi" class="btn btn-primary px-4 fw-bold">
                        <i class="fa-solid fa-paper-plane me-2"></i> Kirim Disposisi
                    </button>
                </div>
            </form>
        </div>
    </div>
</div>
