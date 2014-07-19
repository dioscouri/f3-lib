<div class="row" data-element-type="note-form">
	<div class="col-lg-12 col-md-12 col-xs-12">
		<div class="panel-group" data-element-type="add-note-form">
			<div class="panel panel-default" id="panel-add-note">
				<div class="panel-heading">
					<h4 class="panel-title">
						<a data-toggle="collapse" data-target="#collapseAddNote" href="javascript:;">
						Add a new note
						</a>
					</h4>
				</div>
				<div id="collapseAddNote" class="panel-collapse collapse">
					<div class="panel-body">
						<div id="form-add-note">
							<div class="row" >
								<div class="col-ls-12 col-md-12 col-xs-12 form-group">
									<label>Title</label>
									<input type="text" value="" id="inpAddNoteTitle"  placeholder="Title of note (Optional)" class="form-control" />
								</div>
							</div>
							<div class="row" >
								<div class="col-ls-12 col-md-12 col-xs-12 form-group">
									<label>Description</label>
									<textarea class="form-control" id="inpAddNoteDesc" rows="3" placeholder="Text of your note"></textarea>
								</div>
							</div>
							<div class="row" >
								<div class="col-ls-12 col-md-12 col-xs-12 form-group">
									<button class="btn btn-success" id="btnAddNote" data-task="add-note">Save</button>
									<button class="btn btn-danger" id="btnClearNote" data-task="clear-note">Clear</button>
								</div>
							</div>
						</div>
					</div>
				</div>
			</div>
		</div>
		
	</div>
</div>