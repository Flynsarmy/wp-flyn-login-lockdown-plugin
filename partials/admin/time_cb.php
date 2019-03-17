<select name="<?= esc_attr($args['label_for']) ?>" id="<?= esc_attr($args['label_for']) ?>">
    <?php foreach($options as $t => $label): ?>
        <option value="<?= esc_attr($t) ?>" <?= selected($t, absint($time), false) ?>><?= esc_html($label) ?></option>',
    <?php endforeach; ?>
</select>

<p class="description">
    After the number of failed login attempts (specified above), how long should the user be locked out?
</p>