$( document ).ready(function() {
	var document_height = $(document).height();
	$("#leftSidebar").height(document_height);
});


//Isotope

var $container = $('#wishlist');
// init
$container.isotope({
  // options
  itemSelector : '.wish',
  layoutMode : 'fitRows'
});

$('#tags a').on('click', function(){
  var selector = $(this).attr('data-filter');
  $container.isotope({ filter: selector });
  return false;
});


$('#update > a').on('click', function(e){
	e.preventDefault();
	if($('#menu').hasClass("editing")){
		$('#menu').removeClass("editing");
		$('#userStats').removeClass("editing");
		$('#updateForm').addClass("hidden");
		$('#leftSidebar .name').removeClass("hidden");
		$('#leftSidebar .ville').removeClass("hidden");
	}else{
		if($('#updateForm').hasClass("hidden")){
			$('#updateForm').removeClass("hidden");
		}
		var $this = $(this);
		var lien = $(this).attr('href');
		$.ajax({
			url: lien
		})
		.done(function(data){
			$('#menu').addClass("editing");
			$('#userStats').addClass("editing");
			$('#leftSidebar .name').addClass("editing");
			$('#leftSidebar .ville').addClass("editing");
			$('#updateForm').html(data);
		});
	}
	

});

$('.delete').on('click', function(e){
	e.preventDefault();
	var $this = $(this);
	var lien = $(this).attr('href');
	$.ajax({
		url: lien
	})
	.done(function() {
	    $container.isotope('remove', $this.parent());
	    $container.isotope('layout');
	})
	.fail(function(a) {
		console.log(a);
	});
	return false;
});


$('#addProduct').submit(function(e){
	e.preventDefault();
	$.ajax({
		type: "POST",
		data: $(this).serialize(),
		url: "addProduct/"
	})
	.done(function(data) {
		var node = $(data, {
    	 	html: $('.newWish').html()
		});
		$('#wishlist').prepend(node);
		$('#wishlist').isotope( 'prepended', node);
		$container.isotope('layout');

	})
	.fail(function(a) {
		console.log(a);
	});
});



//.Wish Hover

$('.wish').mouseover(function(){
	$(this).children().eq(2).addClass('active');
	$(this).children().eq(3).addClass('active');
});
$('.wish').mouseleave(function(){
	$(this).children().eq(2).removeClass('active');
	$(this).children().eq(3).removeClass('active');
});




