<style>
    .fll_ip_list th, .fll_ip_list td {padding:5px;margin:0;width:auto;}
    .fll_ip_list .actions {width:75px;}
</style>
<script>
    function fll_unlock( row )
    {
        row.find(':input').attr('disabled', 'disabled');
        tbody = row.closest('TBODY');

        jQuery.ajax({
            url: "<?= admin_url('admin-ajax.php?action=fll-unlock') ?>",
            method: 'GET',
            data: {
                type: row.data('type'),
                value: row.data('value'),
                code: row.data('code')
            },
            dataType: 'json',
            success: function(data, textStatus, jqXHR) {
                if ( !data.success )
                {
                    alert(data.message);
                    row.find(':input').removeAttr('disabled');
                }
                else
                {
                    alert("Unlock successful.");
                    row.remove();

                    if ( tbody.find('TR').size() == 0 )
                        tbody.append("<tr><td colspan='5'>No IPs locked out.</td></tr>");
                }
            },
            error: function(jqXHR, status, errorThrown) {
                alert('An unknown error occurred: ' + errorThrown);
                row.find(':input').removeAttr('disabled');
            }
        });

        return false;
    }
</script>
<table class='wp-list-table widefat fixed striped fll_ip_list' width='500' cellspacing='0' cellpadding='5'>
    <thead>
        <th class="manage-column">Type</th>
        <th class="manage-column">Username</th>
        <th class="manage-column">IP</th>
        <th class="manage-column">Expires</th>
        <th class='manage-column actions'>&nbsp;</th>
    </thead>
    <tbody>
        <?php
            if ( !$lockouts ):
                ?><tr><td colspan='5'>No IPs locked out.</td></tr><?php
            else:
                foreach ( $lockouts as $lockout ):
                    ?>
                    <tr data-type="<?= esc_attr($lockout['type']) ?>" data-value="<?= esc_attr(\FLL\Lockout::get_value($lockout)) ?>" data-code="<?= esc_attr($lockout['unlock_code']) ?>">
                        <td><?= $lockout['type'] ?></td>
                        <td><?= $lockout['username'] ?></td>
                        <td><a href="https://tools.keycdn.com/geo?host=<?= $lockout['ip'] ?>" target="_blank"><?= $lockout['ip'] ?></a></td>
                        <td><?= human_time_diff($lockout['expires'], current_time('timestamp')) ?></td>
                        <td class='actions'>
                            <input type="button" class='button' value="Unlock" onclick="return fll_unlock(jQuery(this).closest('TR'));" />
                        </td>
                    </tr>
                    <?php

                endforeach;
            endif;
        ?>
    </tbody>
    <tfoot>
        <th class="manage-column">Type</th>
        <th class="manage-column">Username</th>
        <th class="manage-column">IP</th>
        <th class="manage-column">Expires</th>
        <th class='manage-column actions'>&nbsp;</th>
    </tfoot>
</table>