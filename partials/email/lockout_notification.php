<p>
	<?= \FLL\Lockdown::opt($lockout['type'] == \FLL\Lockout::TYPE_IP ? 'limit' : 'user_limit') ?>
	failed login attempts from IP: <a href="https://tools.keycdn.com/geo?host=<?= $lockout['ip'] ?>" target="_blank"><?= $lockout['ip'] ?></a>
</p>

<p>Last user login attempted: <?= esc_html($lockout['username']) ?></p>
<p>
	This <?= $lockout['type'] == \FLL\Lockout::TYPE_IP ? 'IP' : 'user login' ?> has been blocked for <?= \FLL\Lockdown::opt('time') ?> minutes and will
	be allowed to log in again at
	<?=
		DateTime::createFromFormat(
			'U',
			$lockout['expires'],
			new DateTimeZone('+'.get_option('gmt_offset').'00')
		)->format('D j M, g:iA')
	?>.

	<?php if ( $include_unlock_link ): ?>
		If you'd like to unlock this <?= $lockout['type'] == \FLL\Lockout::TYPE_IP ? 'IP' : 'user login' ?>
		immediately, <a href="<?= \FLL\Lockout::unlock_url($lockout) ?>" target="_blank">click here</a>.
	<?php endif; ?>
</p>