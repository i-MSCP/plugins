<!-- BDP: ownddns_list -->
<form action="ownddns.php" method="post" name="activate_customer" id="activate_customer">
    <label>
        <select name="admin_id">
            <option value="-1">{TR_OWNDDNS_SELECT_NAME_NONE}</option>
            <!-- BDP: ownddns_select_item -->
            <option value="{TR_OWNDDNS_SELECT_VALUE}">{TR_OWNDDNS_SELECT_NAME}</option>
            <!-- EDP: ownddns_select_item -->
        </select>
    </label>
    <input type="hidden" name="action" value="activate"/>

    <div class="buttons" style="display:inline">
        <input name="Submit" type="submit" value="{TR_SHOW}"/>
    </div>
</form>
<!-- BDP: ownddns_customer_list -->
<table>
    <thead>
    <tr>
        <th>{TR_OWNDDNS_STATUS}</th>
        <th>{TR_OWNDDNS_CUSTOMER_NAME}</th>
        <th>{TR_OWNDDNS_ACCOUNT_LIMIT}</th>
        <th>{TR_OWNDDNS_ACTIONS}</th>
    </tr>
    </thead>
    <tfoot>
    <tbody>
    <!-- BDP: ownddns_customer_item -->
    <tr>
        <td>
            <div class="icon i_{STATUS_ICON}">{OWNDDNS_STATUS}<div>
        </td>
        <td>{OWNDDNS_CUSTOMER_NAME}</td>
        <td>{OWNDDNS_ACCOUNT_LIMIT}</td>
        <td>
            <a class="icon i_edit" href="ownddns.php?action=edit&amp;admin_id={OWNDDNS_ADMIN_ID}">{TR_EDIT_OWNDDNS_ACCOUNT}</a>
            <a class="icon i_delete deactivate_ownddns" href="ownddns.php?action=delete&amp;admin_id={OWNDDNS_ADMIN_ID}">{TR_DELETE_OWNDDNS_ACCOUNT}</a>
        </td>
    </tr>
    <!-- EDP: ownddns_customer_item -->
    </tbody>
</table>
<br />

<div class="paginator">
    <!-- BDP: scroll_prev -->
    <a class="icon i_prev" href="ownddns.php?psi={PREV_PSI}" title="{TR_PREVIOUS}">{TR_PREVIOUS}</a>
    <!-- EDP: scroll_prev -->
    <!-- BDP: scroll_prev_gray -->
    <a class="icon i_prev_gray" href="#"></a>
    <!-- EDP: scroll_prev_gray -->
    <!-- BDP: scroll_next_gray -->
    <a class="icon i_next_gray" href="#"></a>
    <!-- EDP: scroll_next_gray -->
    <!-- BDP: scroll_next -->
    <a class="icon i_next" href="ownddns.php?psi={NEXT_PSI}" title="{TR_NEXT}">{TR_NEXT}</a>
    <!-- EDP: scroll_next -->
</div>

<script>
/*<![CDATA[*/
    $(document).ready(function(){
        $(".deactivate_ownddns").click(function(){
            return confirm("{DEACTIVATE_CUSTOMER_ALERT}");
        });
    });
/*]]>*/
</script>
<!-- EDP: ownddns_customer_list -->

<!-- BDP: ownddns_no_customer_item -->
<table>
    <thead>
    <tr>
        <th>{TR_OWNDDNS_STATUS}</th>
        <th>{TR_OWNDDNS_CUSTOMER_NAME}</th>
        <th>{TR_OWNDDNS_ACTIONS}</th>
    </tr>
    </thead>
    <tfoot>
    <tr>
        <td colspan="3">{TR_OWNDDNS_NO_CUSTOMER}</td>
    </tr>
    </tfoot>
    <tbody>
    <tr>
        <td colspan="3"><div class="message info">{OWNDDNS_NO_CUSTOMER}</div></td>
    </tr>
    </tbody>
</table>
<!-- EDP: ownddns_no_customer_item -->
<!-- EDP: ownddns_list -->
<!-- BDP: ownddns_edit -->
<form action="ownddns.php?action=edit&amp;admin_id={OWNDDNS_ADMIN_ID}" method="post" name="edit_OWNDDNS_ACCOUNT" id="edit_OWNDDNS_ACCOUNT">
    <table class="firstColFixed">
        <thead>
        <tr>
            <th>{TR_ACCOUNT_LIMITS}</th>
            <th>{TR_LIMIT_VALUE}</th>
        </tr>
        </thead>
        <tbody>
        <tr>
            <td><label for="max_ownddns_accounts">{TR_MAX_ACCOUNT_LIMIT}</label></td>
            <td><input type="text" name="max_ownddns_accounts" id="max_ownddns_accounts" value="{MAX_ACCOUNTS}"/></td>
        </tr>
        </tbody>
    </table>

    <div class="buttons">
        <input name="submit" type="submit" value="{TR_UPDATE}"/>
        <a class ="link_as_button" href="ownddns.php">{TR_CANCEL}</a>
    </div>
</form>
<!-- EDP: ownddns_edit -->
