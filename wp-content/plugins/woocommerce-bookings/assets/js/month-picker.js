jQuery(document).ready(function($) {

	$('.block-picker').on('click', 'a', function(){
		var value  = $(this).data('value');
		var target = $(this).closest('div').find('input');

		target.val( value ).change();
		$(this).closest('ul').find('a').removeClass('selected');
		$(this).addClass('selected');

		return false;
	});

	var max_width  = 0;
	var max_height = 0;

	$('.block-picker a').each(function() {
		var width  = $(this).width();
		var height = $(this).height();
		if ( width > max_width ) {
			max_width = width;
		}
		if ( height > max_height ) {
			max_height = height;
		}
	});

	$('.block-picker a').width( max_width );
	$('.block-picker a').height( max_height );
});