$(document).ready(function(){
	var bSlider = function(el){
		this.init = function()
		{
			console.log(el);
			this.el = el;
			this.slideHolder = el.getElementsByClassName('bslider-holder')[0];
			this.howManySlides = $(this.slideHolder).find('.bslider-slide');
			this.slideWidth = $('.bslider-slide').width();
			this.slideWidthString = "-=" + this.slideWidth;
			this.slideHolderWidth = this.howManySlides.length * this.slideWidth;
			$(this.slideHolder).css('width', this.slideHolderWidth);
			this.action = function()
			{	
			var offset = -((this.howManySlides.length-1) * $(this.slideHolder).find('.bslider-slide').outerWidth())+'px';

				if($(this.slideHolder).css('margin-left') == offset)
				{
					$(this.slideHolder).css('margin-left', 0);
				}
				else
				{
					$(this.slideHolder).css('margin-left', this.slideWidthString);
				}
			}.bind(this);
			this.initializeTimer = setInterval(this.action, 5000);
		}
		this.init();
	}
	var howMany = $('.bslider-outer');
	for (var i=0; i < howMany.length; i++)
	{
		var bSlider2 = new bSlider(howMany[i]);
	}
	//$(document).on('click', bSlider);
})