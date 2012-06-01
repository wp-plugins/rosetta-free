function delete_host(control){
	jQuery(control).parent().hide('fast',function(){ 
		jQuery(control).parent().detach();
		if( jQuery('#add-host').parent().find('.lang-wrapper').length == 0 ){}
			jQuery('#add-host').removeClass("disabled"); 
	});
	
	
}
function update_selects(){
	jQuery('.select select').each(function(){
		jQuery(this).parent().next().text(jQuery(this).val());
	});
}
function init_switchers(){
	jQuery('label.switcher-wrapper.enabled .switchbox').each(function(){
		if(jQuery(this).is(':checked')){
			jQuery(this).prev().css("left","-=44px");
			jQuery(this).next().css("backgroundColor", "rgb(162,198,109)");
			jQuery(this).next().find('.rail').css("left","-=35px");
		}
	});
}
function init_selects(){
	jQuery('.select').each(function(index, element){
		jQuery(element).after('<div class="select-value"></div>');
	});
}
function init_disabled(){
	if( jQuery('#add-host').parent().find('.lang-wrapper').length > 0 ){
		jQuery('#add-host').addClass("disabled");
	}
}
jQuery(document).ready(function() {
	
	
	init_switchers(); 
	init_selects();
	init_disabled();
	
	update_selects();
	
	jQuery('.select select').click( function() {
        jQuery(this).parent().next().text(jQuery(this).val());
    });
	jQuery('.select select').keyup( function(e) {
		if(e.keyCode == 13){
			jQuery(this).parent().next().text(jQuery(this).val());
			update_selects();
		}    
    });
	
    jQuery('#add-host').click( function() {
		if( jQuery(this).parent().find('.lang-wrapper').length > 0 ){
			alert(options_page_vars['too_many_langs_erorr']);
		}else{
			var select = jQuery("select[name='default-lang']").parent().clone();
			jQuery(select).find('select').attr("name","lang[]");
			var hidden = false;
			if( ! jQuery('#use-hosts').length )
				hidden = " hidden";
			else
				hidden = ( jQuery('#use-hosts').is(':checked') ) ? "" :  " hidden";
			jQuery(this).after('<div class="lang-wrapper"><input class="host-options'+ hidden+'" type="text" name="host[]" value="" placeholder="' + options_page_vars['host_placeholder'] + '"/><input class="square-button" type="button" name="del-host" value="' +
				options_page_vars['btn_delete'] + '" onClick="delete_host(this)"/></div>');
			jQuery(this).next().css("display","none");
			jQuery(this).next().prepend(select);
			jQuery(this).next().show('fast');
			jQuery(select).after('<div class="select-value"></div>');
			jQuery(select).find('select').click( function() {jQuery(this).parent().next().text(jQuery(this).val());});
			jQuery(select).find('select').keyup( function(e) {
				if(e.keyCode == 13){
					jQuery(this).parent().next().text(jQuery(this).val());
					update_selects();
				}
			});
			update_selects();
			jQuery(this).addClass("disabled");
		}
    });

    jQuery('#submit-options').click( function() {
        return confirm(options_page_vars['confirm_msg']);
    });
	// enabled switcher 
	jQuery('label.switcher-wrapper.enabled .switchbox').click( function(e) {
		if(jQuery(this).is(':checked')){
			jQuery(this).prev().animate({left:'-=44px'},200,"swing");
			jQuery(this).next().animate({backgroundColor: 'rgb(162,198,109)'},200,"swing");
			jQuery(this).next().find('.rail').animate({left:'-=35px'},200,"swing");
			 
		}else{
			jQuery(this).prev().animate({left:'+=44px'},200,"swing");
			jQuery(this).next().animate({backgroundColor: 'rgb(218,117,101)'},200,"swing");
			jQuery(this).next().find('.rail').animate({left:'+=35px'},200,"swing");
		} 
	});
	// disabled switcher
	jQuery('label.switcher-wrapper.disabled .switchbox').click( function() {
		alert(options_page_vars['not_available_erorr']);
	});
	// don't show hosts if use_hosts not cheked
	
	jQuery('#use-hosts').click( function(e) {
		if(jQuery(this).is(':checked')){			
			jQuery('.host-options').css('display','inline-block');
			jQuery('.host-options').animate({width:'250px',opacity:'100'},300,"swing");
			
			 
		}else{
			jQuery('.host-options').animate({width:'0px',opacity:'0'},300,"swing",function(){
				jQuery('.host-options').css('display','none');
			});
		} 
	});
	
});