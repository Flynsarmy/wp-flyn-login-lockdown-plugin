<input type="checkbox" name="<?= esc_attr($args['label_for']) ?>" id="<?= esc_attr($args['label_for']) ?>" value="1" <?= checked($unlock_email) ?> />
Enabled

<p class="description">
    Lock out an IP address after this number of failed login attempts from that address.
</p>