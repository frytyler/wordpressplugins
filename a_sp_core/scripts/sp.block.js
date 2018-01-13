$_ = jQuery.noConflict();
$_(document).ready(function(){
	$_(".sp_block .content .panel:first-child").addClass('active');
	$_(".sp_block .nav li:first-child a").addClass('active');
	$_(".sp_block .nav a").on('click',function(e){
		if($_('body').hasClass('post-php') || $_('body').hasClass('page-php')) {
			e.preventDefault();
		}
		$_('.sp_block .nav a').removeClass('active');
		$_(this).addClass('active');
		var target = $_(this).attr('href');
		var panels = $_(".sp_block .content .panel");
		panels.removeClass('active');
		$_(target).addClass('active');
	});
	if(window.location.hash){
		$_(".sp_block .content .panel").removeClass('active');
		$_(window.location.hash).addClass('active');
		$_('.sp_block .nav a').removeClass('active');
		$_('[href="'+window.location.hash+'"]').addClass('active');
	}
});