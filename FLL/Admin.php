<?php namespace FLL;

!defined('ABSPATH') && exit;

use FLL\Lockdown;
use FLL\Lockout;

/**
 * Admin area functionality for the plugin
 */
class Admin
{
    use \FLL\Traits\Singleton;

    /**
     * Settings section.
     *
     * @since   1.0
     */
    const SECTION = 'default';
    const PAGE = 'fll';

    /**
     * Make it happen. Hook the `_setup` method into `plugins_loaded`.
     *
     * @since   1.0
     * @access  public
     * @uses    add_action
     * @return  void
     */
    public function init()
    {
        add_action('admin_init', [$this, 'register']);
        add_action('admin_menu', [$this, 'register_pages']);
        add_action('admin_head-options-reading.php', [$this, 'display_errors']);
    }

    public function display_errors()
    {
        if ( !empty($_GET['fll-error']) )
            add_settings_error('general', esc_attr( 'invalid_ip' ), $_GET['fll-error'], 'error');
        elseif ( !empty($_GET['fll-success']) )
            add_settings_error('general', esc_attr( 'settings_updated' ), $_GET['fll-success'], 'updated');
    }

    public function register_pages()
    {
        add_options_page(
            'Flyn Login Lockdown',
            'Login Lockdown',
            'manage_options',
            'flyn-login-lockdown',
            [$this, 'settings_page']
        );
    }

    /**
     * Fires on `admin_init`. Registers the settings and settings field.
     *
     * @since   1.0
     * @access  public
     * @uses    register_setting
     * @uses    add_settings_section
     * @uses    add_settings_field
     * @return  void
     */
    public function register()
    {
        register_setting(
            self::PAGE,
            Lockdown::SETTING,
            [$this, 'clean_settings']
        );

        add_settings_section(
            self::SECTION,
            '',
            null,
            self::PAGE
        );

        add_settings_field(
            Lockdown::SETTING . '[limit]',
            'IP Login Attempt Limit',
            [$this, 'ip_attempts_cb'],
            self::PAGE,
            self::SECTION,
            ['label_for' => Lockdown::SETTING . '[limit]', 'key' => 'limit']
        );

        add_settings_field(
            Lockdown::SETTING . '[user_limit]',
            'User Login Attempt Limit',
            [$this, 'user_attempts_cb'],
            self::PAGE,
            self::SECTION,
            ['label_for' => Lockdown::SETTING . '[user_limit]', 'key' => 'user_limit']
        );

        add_settings_field(
            Lockdown::SETTING . '[time]',
            'Login Lockdown Time',
            [$this, 'time_cb'],
            self::PAGE,
            self::SECTION,
            ['label_for' => Lockdown::SETTING . '[time]', 'key' => 'time']
        );

        add_settings_field(
            Lockdown::SETTING . '[unlock_email]',
            'Unlock Link in Notification',
            [$this, 'unlock_email_cb'],
            self::PAGE,
            self::SECTION,
            ['label_for' => Lockdown::SETTING . '[unlock_email]', 'key' => 'unlock_email']
        );

        add_settings_field(
            Lockdown::SETTING . '[locked_ip_list]',
            'Locked Out',
            [$this, 'locked_ip_list'],
            self::PAGE,
            self::SECTION,
            ['lockouts' => $this->get_lockouts()]
        );
    }

    public function settings_page()
    {
        // check user capabilities
        if ( !current_user_can( 'manage_options' ) )
            return;

        echo self::partial(__DIR__.'/../partials/admin/options_page.php');
    }

    /**
     * Validate the settings on way into the database.
     *
     * @since   0.2
     * @access  public
     * @uses    absint
     * @return  array
     */
    public function clean_settings(array $in)
    {
        $out = [];

        foreach ( ['time', 'limit', 'user_limit', 'unlock_email'] as $k)
            if ( !empty($in[$k]) )
                $out[$k] = absint($in[$k]);

        return $out;
    }

    /********** Settings Field/Section Callbacks **********/

    /**
     * The callback for the attempt allowance settings field
     *
     * @since   0.2
     * @access  public
     * @uses    selected
     * @param   array $args Field arguments from add_settings_field
     * @return  void
     */
    public function ip_attempts_cb(array $args)
    {
        $limit = Lockdown::opt(@$args['key'], 5);

        echo self::partial(__DIR__.'/../partials/admin/ip_attempts_cb.php', [
            'limit' => $limit,
            'args' => $args,
        ]);
    }

    /**
     * The callback for the attempt allowance settings field
     *
     * @since   0.2
     * @access  public
     * @uses    selected
     * @param   array $args Field arguments from add_settings_field
     * @return  void
     */
    public function unlock_email_cb(array $args)
    {
        $unlock_email = Lockdown::opt(@$args['key'], 0);

        echo self::partial(__DIR__.'/../partials/admin/unlock_email_cb.php', [
            'unlock_email' => $unlock_email,
            'args' => $args,
        ]);
    }

    /**
     * The callback for the attempt allowance settings field
     *
     * @since   0.2
     * @access  public
     * @uses    selected
     * @param   array $args Field arguments from add_settings_field
     * @return  void
     */
    public function user_attempts_cb(array $args)
    {
        $limit = Lockdown::opt(@$args['key'], 5);

        echo self::partial(__DIR__.'/../partials/admin/user_attempts_cb.php', [
            'limit' => $limit,
            'args' => $args,
        ]);
    }

    /**
     * The callback for the time limit settings field
     *
     * @since   0.2
     * @access  public
     * @uses    selected
     * @return  void
     */
    public function time_cb($args)
    {
        $time = Lockdown::opt($args['key'], 60);

        $options = apply_filters('fll_time_values', [
            30      => '30 Minutes',
            60      => '60 Minutes',
            120     => '2 Hours',
            180     => '3 Hours',
            240     => '4 Hours',
            480     => '8 Hours',
            1440    => '24 Hours',
        ]);

        echo self::partial(__DIR__.'/../partials/admin/time_cb.php', [
            'time' => $time,
            'options' => $options,
            'args' => $args,
        ]);
    }

    /**
     * Get an array of lockouts for the current site
     *
     * @return array
     */
    public function get_lockouts()
    {
        return Lockout::lockouts();
    }

    /**
     * Callback for the blocked ip list section.
     *
     * @param  array $args
     *
     * @return void
     */
    public function locked_ip_list($args)
    {
        $lockouts = isset($args['lockouts']) ? $args['lockouts'] : [];

        echo self::partial(__DIR__.'/../partials/admin/locked_ip_list.php', [
            'lockouts' => $lockouts,
        ]);
    }

    /**
     * Returns a given partial with variables loaded.
     *
     * @param  [type] $filepath [description]
     * @param  array  $vars     [description]
     * @return [type]           [description]
     */
    public static function partial($filepath, array $vars = [])
    {
        extract($vars);
        ob_start();
            require $filepath;
        return ob_get_clean();
    }
} // end class
