if (typeof(Dsc) === 'undefined') {
	var Dsc = {};
}

Dsc.setupColorbox = function() {
    $('[data-colorbox]').each(function(){
        var el = $(this);
        var type = el.attr('data-colorbox');
        var callback = el.attr('data-colorbox-callback');
        var options = el.attr('data-colorbox-options');
        
        switch (type) {
            case "iframe":
                var defaults = {iframe: true, opacity: '0.6', width: '80%', height: '80%'};
                var final_options = $.extend({}, defaults, options);
                el.colorbox(final_options);
                break;
        }
    });
}

Dsc.setupBootbox = function() {
    $('[data-bootbox]').each(function(){
        var el = $(this);
        var type = el.attr('data-bootbox');
        var message = el.attr('data-bootbox-message');
        var callback = el.attr('data-bootbox-callback');
        var options = el.attr('data-bootbox-options');
        
        switch (type) {
            case "confirm":
                if (!message) {
                    message = 'Are you sure?';
                }
                if (!callback) {
                    callback = function(result) {
                        if (result) {
                            window.location=el.attr('href');
                        }
                    }
                }
                el.click(function(e){
                    e.preventDefault();
                    bootbox.confirm(message, callback);
                });
                break;
        }
    });
}

Dsc.setupAjaxForms = function() {
    // if the form has the class dsc-ajax,
    // stop the submit buttons
    // get the form's inputs & values
    // submit the form 
    // if there is an error, display the message and leave the form populated
    // otherwise clear the form and [optional] display the message
    // if a data-callback is specified and is a function, trigger it and send the response object as the function argument    
    $('form.dsc-ajax-form').each(function(){
        form = $(this);
        form.submit(function(e){
            e.preventDefault();
            
            if (form.attr('data-message_container')) {
                $('#'+form.attr('data-message_container')).html('');
            }            
            
            var form_data = new Array();
            $.merge( form_data, form.serializeArray() );
            var url = form.attr('action');
            var request = $.ajax({
                type: 'post', 
                url: url,
                data: form_data
            }).done(function(data){
                var r = $.parseJSON( JSON.stringify(data), false);
                if (form.attr('data-message_container')) {
                    $('#'+form.attr('data-message_container')).html(r.message).delay(500).fadeOut(1000, function(){
                        $(this).html('').fadeIn();
                    });                    
                }
                if (r.redirect) {
                    window.location = r.redirect; 
                }
                if (r.error == false) {
                    form.trigger("reset");
                    if (form.attr('data-callback')) {
                        callback = form.attr('data-callback');
                        Dsc.executeFunctionByName(callback, window, r);
                    }
                    if (form.attr('data-refresh_list')) {
                        list_container = form.attr('data-list_container');
                        target = $('#'+list_container);
                        if (list_container) {
                            var request = $.ajax({
                                type: 'get', 
                                url: target.attr('action')
                            }).done(function(data){
                                var lr = $.parseJSON( JSON.stringify(data), false);
                                if (lr.result) {
                                    target.html(lr.result);
                                }
                                CustomApp.initICheck();
                            });
                        }
                    }
                }
            }).fail(function(data){

            }).always(function(data){

            });            
        });
    });
    
    // .dsc-ajax-submit
    $('.dsc-ajax-submit').click(function(e){
        e.preventDefault();
        el = $(this);                
        form = $('#'+el.attr('data-target'));
        
        if (form.attr('data-message_container')) {
            $('#'+form.attr('data-message_container')).html('');
        }            

        var form_data = new Array();
        $.merge( form_data, form.find(':input[name="parent"]').serializeArray() );
        $.merge( form_data, [{ name: "title", value: form.find(':input[name="new_category_title"]').val() }] );

        var url = form.attr('action');
        var request = $.ajax({
            type: 'post', 
            url: url,
            data: form_data
        }).done(function(data){
            var r = $.parseJSON( JSON.stringify(data), false);
            if (form.attr('data-message_container')) {
                $('#'+form.attr('data-message_container')).html(r.message).delay(500).fadeOut(1000, function(){
                    $(this).html('').fadeIn();
                });
            }
            if (r.redirect) {
                window.location = r.redirect; 
            }
            if (r.error == false) {
                form.find(':input').val("");
                if (form.attr('data-callback')) {
                    callback = form.attr('data-callback');
                    Dsc.executeFunctionByName(callback, window, r);
                }
            }
        }).fail(function(data){

        }).always(function(data){

        });

    });   
    
    $('.bulk-actions').click(function(e){
        e.preventDefault();
        el = $(this);                
        select = $('#'+el.attr('data-target'));
        form = el.closest('form');
        selected = select.find('option:selected');
            
        if (selected.attr('data-action')) {
            form.attr('action', selected.attr('data-action'));
        }
        form.submit();
    });   
}

/**
 * Resets the filters in a form.
 * 
 * @param form
 * @return
 */
Dsc.resetFormFilters = function(form)
{
    // loop through form elements
    var str = new Array();
    for(i=0; i<form.elements.length; i++)
    {
        var string = form.elements[i].name;
        if (string.substring(0,6) == 'filter')
        {
            form.elements[i].value = '';
        }
        if (string.substring(0,4) == 'list')
        {
            form.elements[i].value = '';
        }        
    }
    form.submit();
}

/**
 * 
 * @param {String} msg message for the modal div (optional)
 */
Dsc.newModal = function(msg)
{
    if (typeof window.innerWidth != 'undefined') {
        var h = window.innerHeight;
        var w = window.innerWidth;
    } else {
        var h = document.documentElement.clientHeight;
        var w = document.documentElement.clientWidth;
    }
    var t = (h / 2) - 15;
    var l = (w / 2) - 15;
	var i = document.createElement('img');
	var s = window.location.toString();
	var src = './dsc/images/ajax-loader.gif';
	i.src = src;
	i.style.position = 'absolute';
	i.style.top = t + 'px';
	i.style.left = l + 'px';
	i.style.backgroundColor = '#000000';
	i.style.zIndex = '100001';
	var d = document.createElement('div');
	d.id = 'dscModal';
	d.style.position = 'fixed';
	d.style.top = '0px';
	d.style.left = '0px';
	d.style.width = w + 'px';
	d.style.height = h + 'px';
	d.style.backgroundColor = '#000000';
	d.style.opacity = 0.5;
	d.style.filter = 'alpha(opacity=50)';
	d.style.zIndex = '100000';
	d.appendChild(i);
    if (msg != '' && msg != null) {
	    var m = document.createElement('div');
	    m.style.position = 'absolute';
	    m.style.width = '200px';
	    m.style.top = t + 50 + 'px';
	    m.style.left = (w / 2) - 100 + 'px';
	    m.style.textAlign = 'center';
	    m.style.zIndex = '100002';
	    m.style.fontSize = '1.2em';
	    m.style.color = '#ffffff';
	    m.innerHTML = msg;
	    d.appendChild(m);
	}
	document.body.appendChild(d);
}

Dsc.executeFunctionByName = function(functionName, context /*, args */) {
    var args = Array.prototype.slice.call(arguments, 2);
    var namespaces = functionName.split(".");
    var func = namespaces.pop();
    for (var i = 0; i < namespaces.length; i++) {
        context = context[namespaces[i]];
    }
    if (typeof context[func] == 'function') {
        return context[func].apply(context, args);
    }
    return null;
}

$(document).ready(function(){
    $('[data-sortable]').each(function(){
        var new_direction = 'ASC';
        var el = $(this);
        el.addClass('sorting');
        var form = el.closest('form');
        order = $('*[name="list[order]"]');
        direction = $('*[name="list[direction]"]');
        if (el.attr('data-sortable') == order.val()) {
            if (direction.val() == 'ASC' || direction.val() == 'asc' || direction.val() == '1') {
                new_direction = 'DESC';
                el.addClass('sorting_asc');
            } else {
                el.addClass('sorting_desc');
            }
        }
        
        el.on('click', function(){
            if (form) {
                if (direction.val() == '1' || direction.val() == '-1') {
                    if (new_direction == 'ASC') {
                        new_direction = '1';
                    } else {
                        new_direction = '-1';
                    }
                }
                order.val(el.attr('data-sortable'));
                direction.val(new_direction);
                form.submit();
            }
        });

    });
    
    Dsc.setupAjaxForms();
    Dsc.setupBootbox();
    Dsc.setupColorbox();
});