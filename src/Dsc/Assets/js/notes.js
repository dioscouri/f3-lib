var Notes = Notes || {

	// note_idx => current index for notes
	// note_title => note title
	// note_desc => note description	
	// note_datetime => date and time in format YYYY-MM-DD HH:MM
	// note_user => username
	tmpl_note : '<div class="panel" data-element-type="note" data-mode="" data-note-idx="{note_idx}"> \
					<div class="panel-heading"> \
						<h3 class="panel-title"> \
							<span class="note-title">{note_title} <small>({note_datetime} by {note_user})</small></span> \
							<div class="pull-right" data-element-type="toolbar"> \
								<strong data-element-type="note-status"></strong> \
								&nbsp; \
								<a class="btn btn-xs btn-secondary" href="javascript:;" data-task="edit"> \
									<i class="fa fa-pencil"></i> \
								</a> \
								&nbsp; \
								<a class="btn btn-xs btn-danger" href="javascript:;" data-task="delete"> \
									<i class="fa fa-times"></i> \
								</a> \
								<a class="btn btn-xs btn-success" href="javascript:;" data-task="undelete"> \
									<i class="fa fa-times"></i> \
								</a> \
							</div> \
						</h3> \
					</div> \
					<div class="panel-body"> \
						<div data-element-type="original-text">{note_desc}</div> \
						<div data-type="edit" data-element-type="edit-tools"> \
						<div class="form-group"> \
							<label>Title</label> \
							<input type="text" class="form-control" id="note_{note_idx}_title"/> \
						</div> \
						<div class="form-group"> \
							<label>Description</label> \
							<textarea class="form-control" rows="3" id="note_{note_idx}_description"></textarea> \
						</div> \
						<div class="form-group"> \
							<button class="btn btn-success" data-task="save">Save</button> \
							<button class="btn btn-danger" data-task="cancel">Cancel</button> \
						</div> \
					</div> \
				<input type="hidden" name="notes[{note_idx}][title]" value="{note_title}" /> \
				<input type="hidden" name="notes[{note_idx}][description]" value="{note_desc}" /> \
			</div> \
		</div> \
		',
	
	
	// set up initial CSS constants
	init : function(){
		this.userName = 'guest';
		this.speed = 'fast';
		this.idx = 0;  // index from which counting new notes should start off
		this.toDelete = []; // list of idx of notes to be deleted		
		this.beingSaved = false; // whether user is saving  the document at this moment
		
		$( 'div[data-element-type="add-note-form"] button[data-task="clear-note"]' ).hide();
	},
	
	// checks, if the element was modified
	isModified : function( el ){
		return  el.attr( 'data-modified') == '1';
	},
	
	// checks, if the element was just added
	isJustAdded : function( el ){
		return  el.attr( 'data-added') == '1';
	},

	// sets current name used when editing note
	setUserName : function(name){
		this.userName = name;
	},
	
	// sets starting index for notes
	setIndex : function( new_idx ){
		this.idx = new_idx;
	},
	
	// sets the note to modified mode
	setModified : function( el ){
		el.attr( 'data-modified', '1' );		
	},
	
	// sets whether the note was deleted or not (deleted notes notify user that they had to save their actions)
	setDeletedState : function( el, state ){
		el.attr( 'data-deleted', state );		
	},
		
	// sets appropriate properties when note is being added
	setAddedState : function( el ){
		el.attr( 'data-added', '1' );
		
		el.removeClass( 'panel-warning' ).removeClass( 'panel-danger' ).addClass('panel-default');
		this.setNoteStatusText( el, "Waiting to be added");		
	},
	
	// sets text status for note
	setNoteStatusText : function( el, txt ){
		el.find( 'strong[data-element-type="note-status"]' ).html(txt);
	},
	
	toggleToolbarDeleteState : function( el, speed ){
		if( el.attr( 'data-deleted' ) == '1' ){
			el.find( 'div[data-element-type="toolbar"] a[data-task!="undelete"]' ).hide(speed, function(){
				el.find( 'div[data-element-type="toolbar"] a[data-task="undelete"]' ).show(speed);
			});
		} else {
			el.find( 'div[data-element-type="toolbar"] a[data-task="undelete"]' ).hide(speed, function(){
				el.find( 'div[data-element-type="toolbar"] a[data-task!="undelete"]' ).show(speed);							
			});
		}	},
	
	// binds events to all control elements
	bindEvents : function(){
		var current_this = this;
		
		$('body')
			// edit button
			.on('click',
				'div[data-element-type="note"] h3.panel-title div[data-element-type="toolbar"] a[data-task="edit"]',
				function (e){
					var $this = $( e.currentTarget );
					var $note = $this.parents( 'div[data-element-type="note"]' );

					current_this.changeToEditMode( $note, current_this.speed );
			})
			// delete button
			.on('click',
				'div[data-element-type="note"] h3.panel-title div[data-element-type="toolbar"] a[data-task="delete"]',
				function (e){
					bootbox.confirm( 'Do you want to delete this note?', function( res ){
						if( res == true ){
							var $this = $( e.currentTarget );
							var $note = $this.parents( 'div[data-element-type="note"]' );
			
							current_this.changeToDeletedMode( $note, current_this.speed );							
						}
					});
			})
			// undelete button
			.on('click',
				'div[data-element-type="note"] h3.panel-title div[data-element-type="toolbar"] a[data-task="undelete"]',
				function (e){
					bootbox.confirm( 'Do you want to undelete this note?', function( res ){
						if( res == true ){
							var $this = $( e.currentTarget );
							var $note = $this.parents( 'div[data-element-type="note"]' );
			
							current_this.changeToUnDeletedMode( $note, current_this.speed );							
						}
					});
			})
			// cancel button
			.on('click',
				'div[data-element-type="note"] div[data-element-type="edit-tools"] button[data-task="cancel"]',
				function (e){
					e.preventDefault();
					var $this = $( e.currentTarget );
					var $note = $this.parents( 'div[data-element-type="note"]' );

					current_this.changeToNormalMode( $note, false, current_this.speed );
			})
			// save button
			.on('click',
				'div[data-element-type="note"] div[data-element-type="edit-tools"] button[data-task="save"]',
				function (e){
					e.preventDefault();
					var $this = $( e.currentTarget );
					var $note = $this.parents( 'div[data-element-type="note"]' );

					current_this.changeToNormalMode( $note, true, current_this.speed );
			})
			// Add note button
			.on('click',
				'div[data-element-type="add-note-form"] button[data-task="add-note"]',
				function (e){
					e.preventDefault();
					var $this = $( e.currentTarget );
					var $form = $this.parents( 'div[data-element-type="add-note-form"]' );
	
					current_this.addNewNote( $form );
			})
			// Clear note button
			.on('click',
				'div[data-element-type="add-note-form"] button[data-task="clear-note"]',
				function (e){
					e.preventDefault();
					current_this.clearAddNoteForm();
			})
			.on( 'keypress',
				'div[data-element-type="add-note-form"] :input', 
				function( e ){
					current_this.toggleClearButton();
				})
		.on( 'keyup',
				'div[data-element-type="add-note-form"] :input', 
				function( e ){
					current_this.toggleClearButton();				
				});
		
		$(window).on( 'beforeunload', function(){ return current_this.handleExitPage(); });
		
	},
	
	// binds submitting logic on an element
	bindSaveButton : function( selector ){
		var current_this = this;
		$( selector ).on( 'click', function( e ){
			current_this.handleSubmit();
		});
	},
	
	// show/hide clear button on note form
	toggleClearButton : function( ){
		var s = '';
		var $panel = $( 'div[data-element-type="add-note-form"]' );
		$( ':input', $panel ).each( function( idx, e ){
			s += $(e).val();
		});

		if( s.length > 0 ){
			$panel.find( 'button[data-task="clear-note"]' ).show();
		} else {
			$panel.find( 'button[data-task="clear-note"]' ).hide()
		}
	},
	
	// this method clears data in "add-note" form
	// el is instance of jQuery pointing to panel group with "add-note" form
	clearAddNoteForm : function( ){
		// empty fields
		var $panel = $( 'div[data-element-type="add-note-form"]' );
		$( '#inpAddNoteTitle', $panel ).val( '' );
		$( '#inpAddNoteDesc', $panel ).val( '' );
		
		// and hide "clear" button
		this.toggleClearButton();
	},
	
	// changes note to edit mode
	//el => main panel div for note
	changeToEditMode : function( el, speed ){
		if( el.data( 'mode' ) == 'edit' ){
			return;
		}
		
		// first, move data to visible input fields
		var idx = el.data( 'note-idx' );
		var title = el.find('input[name="notes['+idx+'][title]"]').val();
		var desc = el.find('input[name="notes['+idx+'][description]"]').val();
		el.find('#note_'+idx+'_title').val(title);
		el.find('#note_'+idx+'_description').val(desc);
		
		speed = speed || null;
		if( speed == null ){
			el.find( 'div[data-element-type="original-text"]' ).hide(function(){
				el.find( 'div[data-element-type="toolbar"]' ).hide();
				el.find( 'div[data-element-type="edit-tools"]' ).show();
			});
		} else {
			el.find( 'div[data-element-type="original-text"]' ).fadeOut(speed, function() {
				el.find( 'div[data-element-type="toolbar"]' ).fadeOut( speed );
				el.find( 'div[data-element-type="edit-tools"]' ).fadeIn( speed );				
			});
		}

		this.setNoteMode( el, 'edit' );
	},
	
	// sets new mode for note and store previous one for later use
	setNoteMode : function( el, new_mode ){
		var act_mode = el.data( 'mode' );
		el.attr('data-prev-mode', act_mode );
		el.attr( 'data-mode', new_mode );		
	},
	
	// sets new mode for note and store previous one for later use
	restoreNoteMode : function( el, mode ){
		var act_mode = el.data( 'prev-mode' ) || 'normal';
		el.attr('data-prev-mode', '' );
		el.attr( 'data-mode', act_mode );		
	},
	
	// changes note to normal mode
	// el => main panel div for note
	// use_change => -1 = do nothing; 0 = discharge changes; 1 = save changes
	changeToNormalMode : function( el, use_change, speed ){
		var act_mode = el.data( 'mode' );
		if(  $.inArray( act_mode, [ 'normal', 'deleted', 'undeleted' ]) > -1 ){
			return;
		}
		
		var idx = el.data( 'note-idx' );
		if( use_change == 1){ // user hit "Save" so we need to change this 
			var title = el.find('#note_'+idx+'_title').val();
			var desc = el.find('#note_'+idx+'_description').val();

			// update hidden fields
			el.find( 'input[name="notes['+idx+'][title]"]' ).val(title);
			el.find( 'input[name="notes['+idx+'][description]"]' ).val(desc);

			// mark this note as modified so browser knows user needs to save the document
			el.removeClass( 'panel-default' ).addClass('panel-warning');
			
			// now, update visible html elements
			el.find( 'span.note-title' ).html( title + " <small>(now by " + this.userName + ")</smalll>" );
			el.find( 'div[data-element-type="original-text"]' ).html( desc );			
			
			this.setNoteStatusText(el, 'Waiting to be saved');
			this.setModified( el );
		}

		speed = speed || null;
		if( speed == null ){
			el.find( 'div[data-element-type="edit-tools"]' ).hide( function() {
				el.find( 'div[data-element-type="original-text"]' ).show();
				el.find( 'div[data-element-type="toolbar"]' ).show();				
			});
		} else {
			el.find( 'div[data-element-type="edit-tools"]' ).fadeOut( speed, function() {
				el.find( 'div[data-element-type="original-text"]' ).fadeIn( speed );
				el.find( 'div[data-element-type="toolbar"]' ).fadeIn(speed);				
			});
		}

		this.toggleToolbarDeleteState(el);
		this.restoreNoteMode(el);
	},
	
	// changes note to deleted mode
	// el => main panel div for note
	changeToDeletedMode : function( el, speed ){
		if( el.attr( 'data-mode' ) != 'normal' ){
			return;
		}
		var isAdded = this.isJustAdded( el );
		
		if( !isAdded ) {
			var note_idx = el.attr( 'data-note-idx' );
			// check, if we didnt delete this note already
			if( $.inArray( note_idx, this.toDelete ) > -1 ) {
				return;
			}
			this.toDelete.push( note_idx );
			$( "#notesToDelete" ).val( this.toDelete.join( ',') );			
		}
		
		el.removeClass( 'panel-default' ).addClass( 'panel-danger' );
		el.find( 'div[data-element-type="toolbar"] a' ).hide();

		if( isAdded ){
			this.setNoteStatusText(el, 'Deleted');			
		} else {
			this.setNoteStatusText(el, 'Deleted and waiting to be saved');			
		}
		this.setDeletedState( el, '1' );
		this.setNoteMode( el, 'deleted' );
		this.toggleToolbarDeleteState(el, 'fast' );
	},
	
	// changes note to undeleted mode (consider that user might have done some changes to the note)
	// el => main panel div for note
	changeToUnDeletedMode : function( el, speed ){
		// check, if this note was ever deleted
		if( el.attr( 'data-deleted' ) != '1' || el.attr( 'data-mode' ) != 'deleted' ){
			// nope :P
			return;
		}
		
		var just_added = this.isJustAdded( el );
		var was_modified = this.isModified( el );
		
		if( !just_added ){
			var note_idx = el.attr( 'data-note-idx' );
			// find position of this index in toDelete array and delete it
			var  key_idx = this.toDelete.indexOf( note_idx );
			if( key_idx == -1 ){ // not found here. Why? Some questions you simply do not ask.
				return;
			} else {
				this.toDelete.splice( key_idx, 1 );
			}
			$( "#notesToDelete" ).val( this.toDelete.join( ',') );					
		}
		
		// let me see, if note was modified before being deleted
		if( was_modified ){
			// it was modified so let's go back to that state
			el.removeClass( 'panel-danger' ).addClass( 'panel-warning' );
			this.setNoteStatusText(el, 'Waiting to be saved');			
		} else {
			// oki, now check, if this note wasn added just now
			if( el.attr( 'data-added' ) == '1' ){
				this.setAddedState(el);
			} else {
				// it wasnt, so let's leave it as it was before
				this.setNoteStatusText(el, '');
				el.removeClass( 'panel-danger' ).addClass( 'panel-default' );
			}
		}
		el.find( 'div[data-element-type="toolbar"] a' ).show();			
		this.setDeletedState(el, '');
		this.setNoteMode( el, 'normal' );
		this.toggleToolbarDeleteState(el, 'fast');
	},
	
	// adds new note to list
	addNewNote : function( el ){
		// first check, if all requirements are met (for now, only description is required)
		var desc = $('#inpAddNoteDesc').val();
		if( desc.trim().length == 0 ){
			bootbox.alert( "Please, fill in description for your note");
			return;
		}
		
		
		var el_title = el.find('#inpAddNoteTitle');
		var el_desc = el.find('#inpAddNoteDesc');

		var title = el_title.val();
		var desc = el_desc.val();
		var current_datetime = 'now';
		
		title = title.length ? title : 'Note #' + (this.idx + 1);
		var final_html = this.tmpl_note
								.replace( /{note_idx}/g, this.idx )
								.replace( /{note_user}/g, this.userName )
								.replace( /{note_datetime}/g, current_datetime )
								.replace( /{note_title}/g, title )
								.replace( /{note_desc}/g, desc );

		var $no_notes = $( 'div[data-element-type="message-no-notes"]' );
		if( $no_notes.size() ){
			$no_notes.hide();
		}
		$( 'div[data-element-type="note-form"]' ).after( final_html );
		
		var el = $( 'div[data-element-type="note"][data-note-idx="'+this.idx+'"]' );
		this.setAddedState(el);
		this.changeToNormalMode( el, -1, -1 );
		
		this.idx += 1;
		// clean form
		el_title.val('');
		el_desc.val('');
		
		// hide clear button
		this.toggleClearButton();
	},
	
	// sets all notes to normal mode
	allNotesToNormalMode : function(){
		var current_this = this;
		$( 'div.panel[data-element-type="note"]' ).each( function( idx, el ) {
			current_this.changeToNormalMode( $( el ), -1 );
		} );
	},
	
	// delete all notes which were marked to be deleted after being added
	removeDeletedNotes : function(){
		$('div[data-element-type="note"][data-deleted="1"][data-added="1"]').remove();
	},
	
	// checks, if user can freely leave the page
	handleExitPage : function(){
		
		if( this.beingSaved == false ){ // check all changes, if user is not saving the document
			// checks, if there are any modified notes
			var modified_notes = $('div[data-element-type="note"][data-modified="1"][data-deleted!="1"]');
			if( modified_notes.size() ){
				return "Do you really want to leave this page without saving your changes?";
			}
			
			// checks, if there are any added notes
			var added_notes = $('div[data-element-type="note"][data-added="1"][data-deleted!="1"]');
			if( added_notes.size() ){
				return "Do you really want to leave this page without saving your changes?";
			}

			// checks, if there are any deleted notes
			var deleted_notes = $('div[data-element-type="note"][data-deleted="1"][data-added!="1"]');
			if( deleted_notes.size() ){
				return "Do you really want to leave this page without saving your changes?";
			}			
		}
		return;
	},
	
	// handles Saving process for this page (removes all deleted notes and etc.)
	handleSubmit : function(){
		this.removeDeletedNotes();
		this.beingSaved = true;
	}
};

$(function() {
	Notes.init();
	Notes.bindEvents();
	Notes.allNotesToNormalMode();	
});