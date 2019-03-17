<?php namespace FLL;

use FLL\Lockdown;

/**
 * Handles the lockout functionality
 */
class Lockout
{
    /**
     * Available lockout types
     */
    const TYPE_LOGIN = 'login';
    const TYPE_IP = 'ip';

    /**
     * Prefix for the transients/lockdown options.
     */
    const PREFIX = 'fll_lo_';

    /**
     * Get the current login count for a given IP.
     *
     * @param   string $type   [ip|login]
     * @param   string $value  an ip or a login
     * @return  boolean
     */
    public static function exists($type, $value)
    {
        return self::get($type, $value) !== false;
    }

    /**
     * Retrieves a given lockout if it exists
     *
     * @param   string $type   [ip|login]
     * @param   string $value  an ip or a login
     * @return array|false
     */
    public static function get($type, $value)
    {
        return \get_transient(self::key($type, $value));
    }

    /**
     * Return the lockout's value based on its type
     *
     * @param  array  $lockout
     * @return string
     */
    public static function get_value(array $lockout)
    {
        if ( $lockout['type'] == self::TYPE_IP )
            return $lockout['ip'];

        return $lockout['username'];
    }

    public static function unlock_url(array $lockout)
    {
        $unlock_url_args = http_build_query([
            'action' => 'fll-unlock',
            'type' => $lockout['type'],
            'value' => self::get_value($lockout),
            'code' => $lockout['unlock_code'],
        ]);

        return \admin_url('admin-ajax.php?' . $unlock_url_args);
    }

    /**
     * Attempt to grab the end user's IP address. Use fll_ip filter for
     * custom IP retrieval.
     *
     * @since   0.1
     * @access  private
     * @uses    apply_filters
     * @return  string|bool The IP if it's there, false if not.
     */
    public static function get_ip()
    {
        $ip = false;

        if ( isset($_SERVER['CF-Connecting-IP']) )
            $ip = $_SERVER['CF-Connecting-IP'];
        elseif ( isset($_SERVER['HTTP_X_FORWARDED_FOR']) )
            $ip = $_SERVER['HTTP_X_FORWARDED_FOR'];
        elseif ( isset($_SERVER['REMOTE_ADDR']) )
            $ip = $_SERVER['REMOTE_ADDR'];

        return apply_filters('fll_ip', $ip);
    }

    public static function lockouts()
    {
        global $wpdb;

        // In LIKE queries _ is considered a wildcard character, so escape with \
        $lockouts = $wpdb->get_results("
            SELECT option_id, option_name, option_value
            FROM  $wpdb->options
            WHERE `option_name` LIKE '_transient_".str_replace('_', '\\_', self::PREFIX)."%'
        ", ARRAY_A);

        foreach ( $lockouts as $key => $lockout )
        {
            $lockout = self::load_value($lockout);

            if ( $lockout['expires'] < current_time('timestamp') )
            {
                self::delete(
                    $lockout['type'],
                    $lockout['type'] == self::TYPE_IP ? $lockout['ip'] : $lockout['username']
                );

                unset($lockouts[$key]);
            }
            else
                $lockouts[$key] = $lockout;
        }

        return $lockouts;
    }

    /**
     * Decrypts and returns lockout data from a record in the DB
     *
     * @param array $lockout A record straight from the DB.
     *
     * @return See generate_value() method
     */
    public static function load_value( array $lockout )
    {
        $json = maybe_unserialize($lockout['option_value']);
        $json['id'] = $lockout['option_id'];

        return $json;
    }

    /**
     * Generates the data to be stored in our lockout transient
     *
     * @param  string     $type     login|ip
     * @param  string     $ip
     * @param  string     $username
     * @param  timestamp  $expires  Transient expiration timestamp
     * @return array
     */
    public static function generate_value( $type, $ip, $username, $expires )
    {
        return [
            'type' => $type,
            'ip' => $ip,
            'username' => $username,
            'timestamp' => current_time('timestamp'),
            'expires' => $expires,
            // Used for the email that goes out. Required as a security
            // precauation as the email offers an AJAX URL available to logged
            // out users to stop the super admin locking themself out
            'unlock_code' => bin2hex(openssl_random_pseudo_bytes(10)),
        ];
    }

    /**
     * Increment the count login attemp count for a given $ip
     *
     * @param   string $type      [ip|login]
     * @param   string $ip
     * @param   string $username
     * @return  int               The incremented count
     */
    public static function set($type, $ip, $username)
    {
        $len = absint(Lockdown::opt('time', 60));

        if(!$len || $len < 0)
            $len = 60;

        $len *= MINUTE_IN_SECONDS;

        $len = apply_filters('fll_length', $len);

        $timestamp = current_time('timestamp') + $len;
        $lockout = self::generate_value($type, $ip, $username, $timestamp);

        set_transient(
            self::key($type, $type == self::TYPE_IP ? $ip : $username),
            $lockout,
            $len
        );

        self::send_notification_email($lockout);
    }

    public static function send_notification_email(array $lockout)
    {
        $users = get_users([
            'role' => 'administrator',
            'fields' => [
                'user_email'
            ],
        ]);
        array_walk($users, function(&$user) {
            $user = $user->user_email;
        });
        $blogname = is_multisite() ? get_site_option('site_name') : get_option('blogname');
        $subject = sprintf("[%s] Too many failed login attempts", $blogname);

        $html = \FLL\Admin::partial(
            __DIR__.'/../partials/email/lockout_notification.php', [
                'lockout' => $lockout,
                'include_unlock_link' => Lockdown::opt('unlock_email', 0),
            ]
        );

        \wp_mail($users, $subject, $html);
    }

    /**
     * Remove the count.
     *
     * @param   string $type   [ip|login]
     * @param   string $value  an ip or a login
     */
    public static function delete($type, $value)
    {
        delete_transient(self::key($type, $value));
    }

    /**
     * Get the prefixed transient key
     *
     * @param   string $type   [ip|login]
     * @param   string $value  an ip or a login
     * @return  string
     */
    private static function key($type, $value)
    {
        return self::PREFIX . $type . '_' . md5($value);
    }
}