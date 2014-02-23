$( document ).ready(function() {
	var document_height = $(document).height();
	var wishlist_width = $("#wishlist").width();
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
		$('#update a').removeClass("updateActive");
		$('#menu').removeClass("editing");
		$('#userStats').removeClass("editing");
		$('#updateForm').addClass("hidden");
		$('#leftSidebar .name').removeClass("hidden editing");
		$('#leftSidebar .ville').removeClass("hidden editing");
	}else{
		$('#update a').addClass("updateActive");
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
	data = '<div class="newWish wish"> </div>'
	var node = $(data, {
    	html: $('.newWish').html()
	});
	$('#wishlist').prepend(node);
	$('#wishlist').isotope( 'prepended', node);
	$container.isotope('layout');
	$('.newWish').html("<img class='itemLoader' src='public/images/itemLoader.GIF'>");
	$.ajax({
		type: "POST",
		data: $(this).serialize(),
		url: "addProduct/"
	})
	.done(function(data) {
		$('.newWish').html(data);
		var classes = $("#tags_value").val();
		if(classes != "Toutes"){
			$('.newWish').addClass("Toutes");
		}
		$('.newWish').addClass(classes);
		$('.newWish').removeClass("newWish");
	})
	.fail(function(a) {
		console.log(a);
	});

});

$('#category').on('change', function() {
  if(this.value == "newtag"){
  	$("#newtag").addClass("active");
  }
  else{
  	$("#newtag").removeClass("active");
  }
});

$('#tags li a').on('click', function(e){
	e.preventDefault();
	$('#tags li a').removeClass("active");
	$(this).addClass("active");
});

$(document).on({
    mouseenter: function () {
        $(this).find('.wish_prix').fadeIn(0);
        $(this).find('.delete').fadeIn(0);
    },
    mouseleave: function () {
        $(this).find('.wish_prix').fadeOut(0);
        $(this).find('.delete').fadeOut(0);
    }
}, '.wish');

//.Wish Hover






