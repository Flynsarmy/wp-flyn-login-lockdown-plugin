<select name="<?= esc_attr($args['label_for']) ?>" id="<?= esc_attr($args['label_for']) ?>">
    <?php foreach(range(5, 20) as $i): ?>
        <option value="<?= $i ?>" <?= selected($limit, $i, false) ?>><?= $i ?></option>
    <?php endforeach; ?>
</select>

<p class="description">
    Lock out a user account after this number of failed login attempts to that account.
</p>