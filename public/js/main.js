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
		$container.isotope( 'insert', data);
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




