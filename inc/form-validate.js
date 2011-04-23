
var bp_forum_form_validate = {
	form: {},
	error_div: {},
	error_msg: {},
	
	init: function(args){
		this.form = jQuery('#'+args.form_id);
		this.error_div = jQuery('<div id="'+args.error_div_id+'"/>').hide();
		this.error_msg = args.error_msg;
		
		if( ! this.form.length ) return;
		
		if( jQuery('p.submit', this.form).length ){
			jQuery('p.submit', this.form).before(this.error_div);
		}else if( jQuery('div.submit', this.form).length ){
			jQuery('div.submit', this.form).before(this.error_div);
		}else{
			this.form.append(this.error_div);
		}
		
		this.form.bind('submit', this, this.submit);
	},
	
	submit: function(e){
		var t = e.data;
		var errors = [];
		
		t.error_div.empty().hide();
		
		// subject
		if( jQuery('input#topic_title', t.form).length 
			&& jQuery('input#topic_title', t.form).val().replace(/\s/g,'')=='' )
			errors.push(t.error_msg.title);
		
		// content
		jQuery('textarea.theEditor', t.form).filter(function(){
			if(typeof tinyMCE=='object' && typeof tinyMCE.get(this.id ? this.id : this.name)=='object') {
				if( tinyMCE.get( this.id ? this.id : this.name ).getContent().replace(/\s/g,'')=='' )
					errors.push(t.error_msg.content);
			}else{
				if( this.value.replace(/\s/g,'')=='' )
					errors.push(t.error_msg.content);
			}
		});
		
		// group id
		if( jQuery('select#topic_group_id', t.form).length 
			&& jQuery('select#topic_group_id', t.form).val().replace(/\s/g,'')=='' )
			errors.push(t.error_msg.group_id);
		
		// display error
		if( errors.length ){
			for(var i in errors) 
				t.error_div.append(errors[i]+'<br />');
			t.error_div.show();
			return false;
		}
	}
}