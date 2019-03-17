<?php
    // show error/update messages
    settings_errors( 'fll_messages' );
?>
<div class="wrap">
    <h1><?php echo esc_html( get_admin_page_title() ); ?></h1>
    <form action="options.php" method="post">
        <?php
            // output security fields for the registered setting "fll"
            settings_fields( 'fll' );
            // output setting sections and their fields
            // (sections are registered for "fll", each field is registered to a specific section)
            do_settings_sections( 'fll' );
            // output save settings button
            submit_button( 'Save Settings' );
        ?>
    </form>
</div>