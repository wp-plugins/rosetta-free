jQuery(document).ready(function($) {
	$('#about-rosetta-full').cycle({ 
		fx:      'scrollLeft', 
		speed:    500, 
		timeout:  5000 
	});
	
	$('#about-rosetta-full').click(function () {
		$(window.location).attr('href', 'http://store.theme.fm/plugins/rosetta/');
    });
});

