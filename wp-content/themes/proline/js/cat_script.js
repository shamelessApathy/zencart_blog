$(document).ready(function(){

var catCheck = function(e) {
	console.log(e.target.className);
	target = $(e.target);

	if ($(target).hasClass('category-top'))
	{
		var parent = $(e.target).parent();
		var children = $(parent).children();
		//$(parent).find('.b-cat').attr('style','display:block');
		$(children[1]).css('display','block');
		$(children[2].children[0]).addClass('b-180');
		return;
	}
	if ($(target).hasClass('fa-chevron-down'))
	{
		if ($(target).hasClass('b-180'))
		{
			var parent = $(e.target).parent();
			var parent = $(parent).parent();
			var children = parent.children();
			$(children[1]).css('display','none');
			$(target).removeClass('b-180');
			console.log('running');
		}
		else
		{
		var parent = $(e.target).parent();
		var parent = $(parent).parent();
		var children = parent.children();
		$(children[1]).css('display','block');
		$(target).addClass('b-180');
		}
	}
	else{
		var arrowArray = document.getElementsByClassName('b-180');
		$('.b-cat').css('display','none');
		for (var i=0; i < arrowArray.length; i++)
		{
			console.log('removing class');
			$(arrowArray[i]).removeClass('b-180');
			$(arrowArray[i]).addClass('b-regular');
		}


	}
}

	$(document).on('click', catCheck);

});