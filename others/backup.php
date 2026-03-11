<div class="mb-3">
    <label>Volume</label>
    <select name="volume_id" id="editarVolume" class="form-select" required>
        <option value="">Selecione...</option>
        <?php foreach ($listaVolumes as $v): ?>
            <option value="<?= $v['volume_id'] ?>">
                <?= htmlspecialchars($v['volume']) ?>
            </option>
        <?php endforeach; ?>
    </select>
</div>

<div class="mb-3">
    <label>Volume</label>
    <select name="volume_id" class="form-select" required>
        <option value="">Selecione...</option>
        <?php foreach ($listaVolumes as $v): ?>
            <option value="<?= $v['volume_id'] ?>">
                <?= htmlspecialchars($v['volume']) ?>
            </option>
        <?php endforeach; ?>
    </select>
</div>