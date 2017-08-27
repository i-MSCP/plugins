<!-- BDP: customer_list -->
<table>
    <thead>
    <tr>
        <th style="width:15%">{TR_KEY_STATUS}</th>
        <th style="width:10%">{TR_DOMAIN_NAME}</th>
        <th style="width:20%">{TR_DNS_NAME}</th>
        <th style="width:55%">{TR_DOMAIN_KEY}</th>
    </tr>
    </thead>
    <tbody>
    <!-- BDP: domainkey_item -->
    <tr>
        <td ><div class="icon i_{STATUS_ICON}">{KEY_STATUS}<div></td>
        <td><label for="keyid_{OPENDKIM_ID}">{DOMAIN_NAME}</label></td>
        <td>{DNS_NAME}</td>
        <td>
            <textarea id="keyid_{OPENDKIM_ID}" name="opendkim_key" style="background: #fff none;width:95%;height:100px;resize:none;" readonly>{DOMAIN_KEY}</textarea>
        </td>
    </tr>
    <!-- EDP: domainkey_item -->
    </tbody>
</table>
<!-- EDP: customer_list -->
