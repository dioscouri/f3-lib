<?php 
	$last_idx = 0;
	$username = 'Guest';
	$identity = \Dsc\System::instance()->get('auth')->getIdentity();
	if (!empty($identity->id)) {
		$username = $identity->fullName();
	}
	$notes = (array) $flash->old('notes');
	
	echo $this->renderLayout('SystemTheme/Views::notes/fields_add_note.php');
?>

<?php if( $c = count( $notes ) ) {
	$last_idx = $c;
	for( $idx = $c - 1; $idx > -1; $idx-- ){
		$note = $notes[$idx];
		$title = empty( $note['title']) ? 'Note #'.($idx+1) : $note['title'];
	?>
	<div class="panel panel-default" data-element-type="note" data-mode="" data-note-idx="<?php echo $idx; ?>">
	  <div class="panel-heading">
	  	<h3 class="panel-title">
			<span class="note-title"><?php echo $title; ?> <small>(<?php echo $note['datetime']['local']; ?> by <?php echo $note['user']['name']; ?>)</small></span>
	    	<div class="pull-right" data-element-type="toolbar">
	    		<strong data-element-type="note-status"></strong>
	    		&nbsp;
				<a class="btn btn-xs btn-secondary" href="javascript:;" data-task="edit">
					<i class="fa fa-pencil"></i>
				</a>
				&nbsp;
				<a class="btn btn-xs btn-danger" href="javascript:;" data-task="delete">
					<i class="fa fa-times"></i>
				</a>
				<a class="btn btn-xs btn-success" href="javascript:;" data-task="undelete">
					<i class="fa fa-times"></i>
				</a>
				</div>
	    </h3>
	  </div>
	  <div class="panel-body">
	  	<div data-element-type="original-text"><?php echo $note['description']; ?></div>
		<div data-type="edit" data-element-type="edit-tools">
			<div class="form-group">
				<label>Title</label>
				<input type="text" class="form-control" id="note_<?php echo $idx; ?>_title"/>
			</div>
			<div class="form-group">
				<label>Description</label>
				<textarea class="form-control" rows="3" id="note_<?php echo $idx; ?>_description"></textarea>
			</div>
			<div class="form-group">
				<button class="btn btn-success" data-task="save">Save</button>
				<button class="btn btn-danger" data-task="cancel">Cancel</button>
			</div>
		</div>
		<input type="hidden" name="notes[<?php echo $idx; ?>][title]" value="<?php echo $note['title']; ?>" />
		<input type="hidden" name="notes[<?php echo $idx; ?>][description]" value="<?php echo $note['description']; ?>" />
		</div>
	</div>
	
<?php }
} else { ?>
<div class="row" data-element-type="message-no-notes">
	<div class="col-md-12">
		<div class="alert alert-info">
		No notes ... 
		</div>
	</div>
</div>
<?php } ?>
<input name="__notesToDelete" id="notesToDelete" type="hidden" value="" />
<script type="text/javascript">
$(function(){
	Notes.setIndex( <?php echo $last_idx; ?> );
	Notes.setUserName( '<?php echo $username; ?>' );
});
</script>