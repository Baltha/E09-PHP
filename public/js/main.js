$( document ).ready(function() {
	var document_height = $(document).height();
	$("#leftSidebar").height(document_height);
});


//Isotope

var $container = $('#wishlist');
// init
$container.isotope({
  // options
  itemSelector: '.wish',
  layoutMode: 'fitRows'
});

$('#tags a').on('click', function(){
  var selector = $(this).attr('data-filter');
  $container.isotope({ filter: selector });
  return false;
});

$('.delete').on('click', function(e){
	e.preventDefault();
	var lien = $(this).attr('href');
		console.log(lien);

	$.ajax({
		url: lien
	})
	.done(function() {
	    alert( "success" );
	  })
	  .fail(function(a) {
	    console.log(a);
	  });

		//$container.isotope('remove', $(this).parent());
	return false;
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




