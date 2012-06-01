jQuery(document).ready(function($) {  
		// *bad programming style. must be fixed by sending array of language form php
		
		if(post_langs != null){
			var _post_langs = $.parseJSON(post_langs);
		}
		
		if( $('#poststuff').length ){
			//var langs = [];
			var id_prefix = 'post-in-';
			// move elements inside one div
			$('#titlediv').before('<div id="post-tabs"><div id="default-lang-editor"></div></div>');
			$('#titlediv').detach().appendTo($('#default-lang-editor'));
			$('#postdivrich').detach().appendTo($('#default-lang-editor'));
			// finding all elemets with id = post-in-"lang"
			$('div#poststuff div').each(function(indx, element){
				var idStr = element.id;
				if(idStr.substring(0,id_prefix.length)==id_prefix) {
					$(element).detach().appendTo($('#post-tabs'));		
					// clean metaboxes
					$(element).find('.handlediv').detach();
					$(element).find('.hndle').detach();
					element.className = "";
				}
			});
			// add navigation
			var nav_html = '<div class="tabs-nav"><ul><li><a href="#default-lang-editor">' + _post_langs['langs'][0][1] + '<span class="default-label"> ( ' + _post_langs['default_str'] + ' )</span></a></li>';
			for(i = 1; i < _post_langs['langs'].length; i++){
				nav_html+= '<li><a href="#' + id_prefix + _post_langs['langs'][i][0] + '">' + _post_langs['langs'][i][1] + '</a></li>';
			}
			nav_html += '</ul></div>';
			$('#default-lang-editor').before(nav_html);
			
			// turning tabs on
			$('#post-tabs').tabs();
		}
}); 


