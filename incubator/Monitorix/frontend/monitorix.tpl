<form action="monitorix.php" method="post" name="shown_graph" id="shown_graph">
	<select name="graph_name">
		<option value="-1">{TR_MONITORIX_SELECT_NAME_NONE}</option>
		<!-- BDP: monitorix_item -->
		<option value="{TR_MONITORIX_SELECT_VALUE}" {MONITORIX_NAME_SELECTED}>{TR_MONITORIX_SELECT_NAME}</option>
		<!-- EDP: monitorix_item -->
	</select>
	<select name="show_when">
		<option value="day" {M_DAY_SELECTED}>{M_DAY}</option>
		<option value="week" {M_WEEK_SELECTED}>{M_WEEK}</option>
		<option value="month" {M_MONTH_SELECTED}>{M_MONTH}</option>
		<option value="year" {M_YEAR_SELECTED}>{M_YEAR}</option>
	</select>
	<input type="hidden" name="action" value="go_show"/>
	
	<div class="buttons" style="display:inline">
		<input name="Submit" type="submit" value="{TR_SHOW}"/>
	</div>
</form>
<table>
	<thead class="ui-widget-header">
	<tr>
		<th colspan="2">{TR_MONITORIXGRAPH}</th>
	</tr>
	</thead>
	<tfoot class="ui-widget-header">
	<tr>
		<td colspan="2">{TR_MONITORIXGRAPH}</td>
	</tr>
	</tfoot>
	<tbody class="ui-widget-content">
	<!-- BDP: monitorixgraph_not_selected -->
	<tr>
		<td><div class="message info">{MONITORIXGRAPHIC_NOT_SELECTED}</div></td>
	<tr>
	<!-- EDP: monitorixgraph_not_selected -->
	<!-- BDP: monitorixgraph_error -->
	<tr>
		<td><div class="message error">{MONITORIXGRAPHIC_ERROR}</div></td>
	<tr>
	<!-- EDP: monitorixgraph_error -->
	<!-- BDP: monitorixgraph_selected -->
	<tr>
		<td>
			<div class="monitorix_wrapper">
				<!-- BDP: monitorix_graph_item -->
				<a href="monitorixgraphics.php?{MONITORIXGRAPH}" class="open"><img src="monitorixgraphics.php?{MONITORIXGRAPH}" width="{MONITORIXGRAPH_WIDTH}" height="{MONITORIXGRAPH_HEIGHT}" style="float:left;margin: 0px 5px 5px 0px;border:1px dashed #000;"/></a>
				<!-- EDP: monitorix_graph_item -->
			</div>
			<div style="clear:left"></div>
		</td>
	</tr>
	<!-- EDP: monitorixgraph_selected -->
	</tbody>
</table>

<div class="buttons">
	<a style="color:#fff" class="syncgraphs" href="monitorix.php?action=syncgraphs" title="{TR_UPDATE_TOOLTIP}">
		{TR_UPDATE}
	</a>
</div>

<style type="text/css">
	.monitorix_wrapper img:hover {
		opacity:0.6;
	}

	.monitorix-modal {
		position: fixed;
		width: 100%;
		height: 100%;
		background-color: rgba(0, 0, 0, 0.8);
		top: 0px;
		right: 0px;
		z-index: 1000;
	}
	
	.monitorix-modal .monitorix-content {
		background: #fff;
		width: 895px;
		margin: 150px auto;
		text-align: center;
		padding: 20px;
		-webkit-box-shadow: 0 10px 25px rgba(0, 0, 0, 0.5);
		-moz-box-shadow: 0 10px 25px rgba(0, 0, 0, 0.5);
		box-shadow: 0 10px 25px rgba(0, 0, 0, 0.5);
		position: relative;
		border-radius: 5px;
		-webkit-border-radius: 5px;
		-moz-border-radius: 5px;
		display: none; /*for jQuery fadein effect */
	}
	
	.monitorix-modal .monitorix-content img {
		max-width: 895px;
		max-height: 367px;
	}
	
	span.monitorix-close {
		width: 10px;
		height: 10px;
		display: inline-block;
		position: absolute;
		top: 5px;
		right: 40px;
		cursor: pointer;
		opacity: 0.5;
		font-weight:bold;
	}
	
	span.monitorix-close:hover {
		opacity: 1;
	}
</style>

<script>
/*<![CDATA[*/

	$(function() {
		$( ".syncgraphs" ).button();
		
		$('.open').click(function(){
			var src = $(this).attr('href');
			open_modal(src);
			return false;
		});
	});
	
	function open_modal(src) {
		$('body').append('<div class="monitorix-modal"><div class="monitorix-content"><span class="monitorix-close">CLOSE</span><img src="'+src+'"></div></div>');

		$('.monitorix-content').fadeIn(200);

		$('.monitorix-close, .monitorix-modal').bind('click', function() {
			$('.monitorix-modal').fadeOut(200, function(){
				$(this).remove();
			});
		});

		$('.monitorix-content').bind('click', function(event){
			event.stopPropagation();
		})
	}
/*]]>*/
</script>
