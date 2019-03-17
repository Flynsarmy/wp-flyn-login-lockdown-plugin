<?php namespace FLL;

!defined('ABSPATH') && exit;

use FLL\Counter;
use FLL\Lockout;

/**
 * Handles all non-admin functionality for the plugin
 */
class Lockdown
{
    use \FLL\Traits\Singleton;

    /**
     * The option name.
     *
     * @since   1.0
     */
    const SETTING = 'fll_options';

    /**
     * Set up all the hooks we need
     *
     * @return void
     */
    public function init()
    {
        add_action('wp_login_failed', [$this, 'failed_login']);
        add_filter('authenticate', [$this, 'authenticate'], 30, 3);
        add_action('login_init', [$this, 'maybe_kill_login']);
        add_action('wp_login', [$this, 'successful_login'], 10, 2);
        add_action('wp_ajax_fll-unlock', [$this, 'handle_unlock']);
        if ( Lockdown::opt('unlock_email', 0) )
            add_action('wp_ajax_nopriv_fll-unlock', [$this, 'handle_unlock']);
    }

    public function handle_unlock()
    {
        global $wpdb;

        if ( empty($_GET['type']) || !in_array($_GET['type'], [Lockout::TYPE_IP, Lockout::TYPE_LOGIN]) )
            exit(json_encode([
                'success' => 0,
                'message' => "Invalid lockout type.",
            ]));

        if ( empty($_GET['value']) )
            exit(json_encode([
                'success' => 0,
                'message' => "Invalid lockout value.",
            ]));

        if ( empty($_GET['code']) )
            exit(json_encode([
                'success' => 0,
                'message' => "Invalid lockout code.",
            ]));

        $lockout = Lockout::get($_GET['type'], $_GET['value']);

        if ( !$lockout )
            exit(json_encode([
                'success' => 0,
                'message' => "No lockout found.",
            ]));

        if ( $lockout && $lockout['unlock_code'] != $_GET['code'] )
            exit(json_encode([
                'success' => 0,
                'message' => "Invalid lockout code.",
            ]));

        Lockout::delete($lockout['type'], $_GET['value']);

        exit(json_encode([
            'success' => 1,
            'message' => 'Successfully unblocked.',
        ]));
    }

    public function authenticate($user, $username, $password)
    {
        if ( $username && Lockout::exists(Lockout::TYPE_LOGIN, $username) )
            die('Too many login attempts for this user! Please take a break and try again later');

        return $user;
    }

    /**
     * Catch failed login attempts due a faulty username/password combination
     *
     * If a login attempt fails, this function will add/update an option with
     * a count of how many times that attempt has failed.
     *
     * @return  void
     */
    public function failed_login( $username )
    {
        if ( !($ip = Lockout::get_ip()) )
            return;

        if (apply_filters('fll_allow_ip', false, $ip))
            return;

        Counter::increment(Lockout::TYPE_IP, $ip);
        Counter::increment(Lockout::TYPE_LOGIN, $username);

        $ip_count = $count = Counter::count(Lockout::TYPE_IP, $ip);
        $login_count = Counter::count(Lockout::TYPE_LOGIN, $username);
        $ip_limit = absint(self::opt('limit', 5));
        $login_limit = absint(self::opt('login_limit', 5));

        /*
         * IP lockouts
         */
        $type = Lockout::TYPE_IP;
        // User just reached the max attempts - lock them out
        if($ip_count > $ip_limit)
        {
            Counter::delete($type, $ip);
            Lockout::set($type, $ip, $username);
            do_action('fll_count_reached', $type, $ip, $username);
        }

        /*
         * Username lockouts
         */
        $type = Lockout::TYPE_LOGIN;
        // User just reached the max attempts - lock them out
        if($login_count >= $login_limit)
        {
            Counter::delete($type, $username);
            Lockout::set($type, $ip, $username);
            do_action('fll_count_reached', $type, $ip, $username);
        }
    }

    /**
     * Kills the login page via wp_die if login attempt allowance has been
     * exceeded or the IP address is locked down.
     *
     * @since   0.1
     * @access  public
     * @return  void
     */
    public function maybe_kill_login()
    {
        if ( !($ip = Lockout::get_ip()) )
            return;

        $die = false;

        /*
         * IP lockouts
         */

        // User is already locked out
        if ( Lockout::exists(Lockout::TYPE_IP, $ip) )
        {
            $die = true;
            do_action('fll_attempt', $ip);
        }

        if ( apply_filters('fll_should_die', $die, $ip) )
        {
            wp_die(
                'Too many login attempts from one IP address! Please take a break and try again later',
                'Too many login attempts',
                ['response' => apply_filters('fll_response', 403)]
            );
        }
    }

    /**
     * Clears all lockdown data on a successful login.
     *
     * @param   string  $username
     * @param   WP_User $user
     * @access  public
     * @return  void
     */
    function successful_login($username, $user)
    {
        Counter::delete(Lockout::TYPE_LOGIN, $username);
        Lockout::delete(Lockout::TYPE_LOGIN, $username);

        if ( ($ip = Lockout::get_ip()) )
        {
            Counter::delete(Lockout::TYPE_IP, $ip);
            Lockout::delete(Lockout::TYPE_IP, $ip);
        }
    }

    /**
     * Fetch an option.
     *
     * @param   string $key The option key to fetch
     * @param   mixed $default The default to return (optional)
     * @return  mixed
     */
    public static function opt($key, $default='')
    {
        $opts = get_option(self::SETTING, []);
        return !empty($opts[$key]) ? $opts[$key] : $default;
    }
} // end class
