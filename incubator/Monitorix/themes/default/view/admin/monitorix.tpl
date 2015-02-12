
<form action="monitorix.php" method="post" name="shown_graph" id="shown_graph">
	<label>
		<select name="graph_name">
			<option value="-1">{TR_MONITORIX_SELECT_NAME_NONE}</option>
			<!-- BDP: monitorix_item -->
			<option value="{TR_MONITORIX_SELECT_VALUE}" {MONITORIX_NAME_SELECTED}>{TR_MONITORIX_SELECT_NAME}</option>
			<!-- EDP: monitorix_item -->
		</select>
	</label>
	<label>
		<select name="show_when">
			<option value="hour" {M_HOUR_SELECTED}>{M_HOUR}</option>
			<option value="day" {M_DAY_SELECTED}>{M_DAY}</option>
			<option value="week" {M_WEEK_SELECTED}>{M_WEEK}</option>
			<option value="month" {M_MONTH_SELECTED}>{M_MONTH}</option>
			<option value="year" {M_YEAR_SELECTED}>{M_YEAR}</option>
		</select>
	</label>
	<input type="hidden" name="action" value="go_show"/>
	
	<div class="buttons" style="display:inline">
		<input name="Submit" type="submit" value="{TR_SHOW}"/>
	</div>
</form>

<table>
	<thead>
	<tr>
		<th colspan="2">{TR_MONITORIXGRAPH}</th>
	</tr>
	</thead>
	<tfoot>
	<tr>
		<td colspan="2">{TR_MONITORIXGRAPH}</td>
	</tr>
	</tfoot>
	<tbody>
	<!-- BDP: monitorixgraph_not_selected -->
	<tr>
		<td><div class="message static_info">{MONITORIXGRAPHIC_NOT_SELECTED}</div></td>
	<tr>
	<!-- EDP: monitorixgraph_not_selected -->
	<!-- BDP: monitorixgraph_error -->
	<tr>
		<td><div class="message static_error">{MONITORIXGRAPHIC_ERROR}</div></td>
	<tr>
	<!-- EDP: monitorixgraph_error -->
	<!-- BDP: monitorixgraph_selected -->
	<tr>
		<td>
			<div class="monitorix_wrapper">
				<!-- BDP: monitorix_graph_item -->
				<a href="/Monitorix/themes/default/assets/images/graphs/{MONITORIXGRAPH}" class="open">
					<img src="/Monitorix/themes/default/assets/images/graphs/{MONITORIXGRAPH}" width="{MONITORIXGRAPH_WIDTH}" height="{MONITORIXGRAPH_HEIGHT}" style="float:left;margin: 0px 5px 5px 0px;border:1px dashed #000;"/>
				</a>
				<!-- EDP: monitorix_graph_item -->
			</div>
			<div style="clear:left"></div>
		</td>
	</tr>
	<!-- EDP: monitorixgraph_selected -->
	</tbody>
</table>

<link rel="stylesheet" href="/Monitorix/themes/default/assets/css/monitorix.css?v={MONITORIX_ASSET_VERSION}">

<script>
	$(function() {
		$('.open').click(function(){
			var src = $(this).attr('href');
			open_modal(src);
			return false;
		});
	});
	
	function open_modal(src) {
		$('body').append(
			'<div class="monitorix-modal"><div class="monitorix-content">' +
			'<span class="monitorix-close">CLOSE</span><img src="' + src + '"></div></div>'
		);

		var $monitorixContent = $('.monitorix-content');
		$monitorixContent.fadeIn(200);

		$('.monitorix-close, .monitorix-modal').bind('click', function() {
			$('.monitorix-modal').fadeOut(200, function() {
				$(this).remove();
			});
		});

		$monitorixContent.bind('click', function(event){
			event.stopPropagation();
		});
	}
</script>
