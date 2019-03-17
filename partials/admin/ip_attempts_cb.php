<select name="<?= esc_attr($args['label_for']) ?>" id="<?= esc_attr($args['label_for']) ?>">
    <?php foreach(range(5, 50) as $i): ?>
        <option value="<?= $i ?>" <?= selected($limit, $i, false) ?>><?= $i ?></option>
    <?php endforeach; ?>
</select>

<p class="description">
    Lock out an IP address after this number of failed login attempts from that address.
</p>