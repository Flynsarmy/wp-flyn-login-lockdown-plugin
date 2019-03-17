<?php namespace FLL;

/**
 * Class used to keep track of invalid login attempts
 */
class Counter
{
    /**
     * Prefix for the transients/lockdown options.
     */
    const PREFIX = 'fll_';

    /**
     * Get the current login count for a given IP.
     *
     * @param   string $type   [ip|login]
     * @param   string $value
     * @return  int            The current attempt count
     */
    public static function count($type, $value)
    {
        if ( $c = get_transient(self::key($type, $value)) )
            return absint($c);

        return 0;
    }

    /**
     * Increment the count login attemp count for a given $ip
     *
     * @param   string $type   [ip|login]
     * @param   string $value
     * @return  int            The current attempt count
     */
    public static function increment($type, $value)
    {
        $c = self::count($type, $value) + 1;

        set_transient(self::key($type, $value), $c,
            apply_filters('fll_timer', 60*60, $type, $value));

        return $c;
    }

    /**
     * Remove the count.
     *
     * @param   string $type   [ip|login]
     * @param   string $value
     */
    public static function delete($type, $value)
    {
        delete_transient(self::key($type, $value));
    }

    /**
     * Get the prefixed transient key
     *
     * @param   string $type   [ip|login]
     * @param   string $value
     * @return  string
     */
    private static function key($type, $value)
    {
        return self::PREFIX . $type . '_' . md5($value);
    }
}