<?php

/**
 * Script to print log files!
 ********************************/

/* verify that user is admin */
checkAdmin();

?>

<h4><?php print _('Log files'); ?>:</h4>
<hr>

<!-- severity filter -->
<form id="logs" name="logs">
    <?php print _('Informational'); ?>	<input type="checkbox" name="Informational" value="Informational" checked> ::
    <?php print _('Notice'); ?>			<input type="checkbox" name="Notice"        value="Notice"        checked> ::
    <?php print _('Warning'); ?>		<input type="checkbox" name="Warning"       value="Warning"       checked>

	
	<!-- download log files -->
	<button id="downloadLogs" class="btn btn-sm btn-default" style="margin-left:20px"><i class="fa fa-download"></i> <?php print _('Download logs'); ?></button>

	<!-- download log files -->
	<!-- <button id="clearLogs" class="btn btn-sm btn-default"><i class="fa fa-trash-o"></i> <?php print _('Clear logs'); ?></button> -->
	
	<span class="pull-right" id="logDirection">
	<div class="btn-group">
		<button class="btn btn-xs btn-default" data-direction="prev" name="next" rel="tooltip" data-container='body' title="<?php print _('Previous page'); ?>"><i class="fa fa-angle-left"></i></button>
		<button class="btn btn-xs btn-default" data-direction="next" name="next" rel="tooltip" data-container='body' title="<?php print _('Next page'); ?>"><i class="fa fa-angle-right"></i></button>
	</div>
	</span>
</form>


<!-- show table -->
<div class="normalTable logs">
<?php include('logResult.php'); ?>
</div>		<!-- end filter overlay div -->