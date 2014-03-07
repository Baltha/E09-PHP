$( document ).ready(function() {
	gestionHeight();
});

function ouvre_popup(page) {
   window.open(page,"Api GiftMe","menubar=no, status=no, scrollbars=no, menubar=no, width=750, height=500");
}
//Isotope

var $container = $('#wishlist');
// init
$container.isotope({
  // options
  itemSelector : '.wish',
  layoutMode : 'masonry',
  masonry: {
  columnWidth: 200,
  gutter: 25,
  isFitWidth: true
  }

});

$('#tags a').on('click', function(){
  var selector = $(this).attr('data-filter');
  $container.isotope({ filter: selector });
  return false;
});

$('#hand > a').on('click', function(e){
e.preventDefault();
if($('#handForm').hasClass("hidden")){
			$('#tagList').addClass("hidden");
			$('#content').addClass("hidden");
			$('#wishlist').addClass("hidden");
			$('#handForm').removeClass("hidden");
			var $this = $(this);
			var lien = $(this).attr('href');
			$.ajax({
				url: lien
			})
			.done(function(data){
				$('#handForm').html(data);
			});
		}
else
{
	$('#handForm').addClass("hidden");
	$('#tagList').removeClass("hidden");
	$('#content').removeClass("hidden");
	$('#wishlist').removeClass("hidden");
}

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
		$('.profilPicture').removeClass('profilPictureModif');
	}else{
		$('.profilPicture').addClass('profilPictureModif');
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

$('body').on('click', '.delete', function(e){
	e.preventDefault();
	var $this = $(this);
	var lien = $(this).attr('href');
	$.ajax({
		url: lien
	})
	.done(function() {
	    $container.isotope('remove', $this.parent());
	    $container.isotope('layout');
	    var nbWisg = $('#nbWish').text();
		var nbWish = parseInt(nbWisg);
		$('#nbWish').text(nbWish - 1);
	})
	.fail(function(a) {
		console.log(a);
	});
	return false;
});

$('body').on('keyup', function(e){
	if(e.keyCode == 13) {
		e.preventDefault();
		e.stopPropagation();
	}
});


$('input[name="name"]').bind('keyup',function(e){
/*	if(e.keyCode == 13) {
		e.preventDefault();
		e.stopPropagation();
		return false;
	}*/
    	
	var $this=$(this);
	var $parent=$this.parent('form');
	var name=$this.val();
	if(name.length > 2){
		var datas={'name':name};
		$.ajax({
			url:$parent.attr('action'),
			method:$parent.attr('method'),
			data:datas
		})
		.success(function(data){
			$('.users').html(data);
		})
	}
})

$('.rewishlister').on('click', function(e){
	e.preventDefault();
	var lien = $(this).attr('href');
	$.ajax({
		url: lien
	})
	.done(function(status) {
		var nbWisg = $('#nbWish').text();
		var nbWish = parseInt(nbWisg);
		$('#nbWish').text(nbWish + 1);
	})
	.fail(function(status) {
	});
	return false;
});

$('.like').on('click', function(e){
	e.preventDefault();
	var lien = $(this).attr('href');
	$.ajax({
		url: lien
	})
	.done(function(status) {
	})
	.fail(function(status) {
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
		if(data==="0")
		{	
			$('#wishlist').isotope( 'remove', $('.newWish'));
			$container.isotope('layout');	
			PopUp("HandAdd");
			return;
		}
		$('.newWish').html(data);
		var classes = $("#tags_value").val();
		if(classes != "Toutes"){
			$('.newWish').addClass("Toutes");
		}
		$('.newWish').addClass(classes);
		$('.newWish').removeClass("newWish");
		var nbWisg = $('#nbWish').text();
		var nbWish = parseInt(nbWisg);
		$('#nbWish').text(nbWish + 1);
		$('#nowish').remove();
	})
	.fail(function(a) {

		$('#wishlist').isotope( 'remove', $('.newWish'));
		$container.isotope('layout');		
		console.log(a);
	});

});


$('#inscription').on('click', function(e){
	e.preventDefault();
	PopUp("callForm");
	$('.popup').addClass('popupInsc');
});

$('#addContrib').on('click', function(e){
	e.preventDefault();
	PopUp("../../addContrib");
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
	$(this).toggleClass("active");
});

//.Wish Hover

$(document).on({
    mouseenter: function () {
        $(this).find('.wish_prix').fadeIn(0);
        $(this).find('.delete').fadeIn(0);
        $(this).find('.rewishlister').fadeIn(0);
        $(this).find('.like').fadeIn(0);
    },
    mouseleave: function () {
        $(this).find('.wish_prix').fadeOut(0);
        $(this).find('.delete').fadeOut(0);
        $(this).find('.rewishlister').fadeOut(0);
        $(this).find('.like').fadeOut(0);
    }
}, '.wish');

//.Mycontrib li Hover

$(".myContributions ul li a").on({
    mouseenter: function () {
        $(this).parent().addClass("hover");
    },
    mouseleave: function () {
    	$(this).parent().removeClass("hover");
    }
});


// Gestion de la hauteur

function gestionHeight(){
	var document_height = $(document).height();
	var window_height = $(window).height();
	var wishlist_width = $("#wishlist").width();
	$("#leftSidebar").height(document_height);
	$(".sectiontop").height(window_height);
}

function PopUp(url){
	var popup = $('<div class="popupBackground"></div>').hide().height($(document).height());
	var content = $('<div class="popup"></div>');
	popup.append(content);
	$('body').append(popup);
	content.load(url, function(){
	    $('.popupBackground').fadeIn(200)
	    .find('.popup').prepend('<div class="popupClose" onclick="PopUpClose()">&times;</div>');
	});
	$('.popupBackground').on('click', function(e){
		var $target = $(event.target);
	    if($target.is('.popupBackground')) {
	        PopUpClose();
	    }
	});
}

function PopUpClose()
{
	$('.popupBackground').fadeOut(200, function(){$(this).remove()});
}
//si windows=resize

$(window).resize(function(){
	gestionHeight();
});

