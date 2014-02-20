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

$('#tags a').click(function(){
  var selector = $(this).attr('data-filter');
  $container.isotope({ filter: selector });
  return false;
});

$(".delete").click(function(){
	$container.isotope( 'remove', $(this).parent());
});

function addProduct(){
	$.ajax({
		url:"partials/addProduct"
	})
	.success(function(data){
		$('body').html(data);
	});
}


//.Wish Hover

$('.wish').mouseover(function(){
	$(this).children().eq(2).addClass('active');
	$(this).children().eq(3).addClass('active');
});
$('.wish').mouseleave(function(){
	$(this).children().eq(2).removeClass('active');
	$(this).children().eq(3).removeClass('active');
});




