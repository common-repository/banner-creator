(function($) {

	"use strict";

	var a, b, c;

	function initTabs() {
		$('.ad-setting-tabs .tab-content').hide().first().show().addClass('active');
		$('.ad-setting-tabs .tab-navigator ul li').first().addClass('active');
		$(document).on('click', '.ad-setting-tabs .tab-navigator ul li a', function() {
			$(this).parent().addClass('active').siblings().removeClass('active');
			a = $(this).blur().attr('href');
			$(a).fadeIn().addClass('active').siblings().removeClass('active').fadeOut();
			return false;
		});
	}

	function refreshRadioTabs() {
		$('[data-tab-for]').hide();
		a = $('.tab-radio input[type="radio"]:checked').val();
		$('[data-tab-for="' + a + '"]').show();
	}

	function initRadio() {
		refreshRadioTabs();
		$(document).on('change', '.banner-sizes-box ul li label input[type="radio"]', function() {
			$('.banner-sizes-box ul li').removeClass('active');
			$('.banner-sizes-box ul li label input[type="radio"]:checked').closest('li').addClass('active');
		});
		$(document).on('change', '.tab-radio input[type="radio"]', function() {
			refreshRadioTabs();
		});
	}

	function uploaders() {

		$('.ts-banner-image-uploader').each(function() {

			var metaBox = $(this),
			frame = metaBox.data('frame') || false,
			addImgLink = metaBox.find('.upload-banner-img'),
			delImgLink = metaBox.find( '.delete-banner-img'),
			imgContainer = metaBox.find( '.banner-img-container'),
			imgIdInput = metaBox.find( '.image-id-field' );

			addImgLink.on( 'click', function( event ) {

				event.preventDefault();

				if ( frame ) {
					frame.open();
					return;
				}

				metaBox.data('frame', wp.media({
					title: metaBox.data('frame-title'),
					button: {
						text: metaBox.data('button-text')
					},
					library: {
						type: [ 'image' ]
					},
					multiple: false
				}));

				metaBox.data('frame').on( 'select', function() {
					var attachment = metaBox.data('frame').state().get('selection').first().toJSON();
					imgContainer.find('img').remove();
					imgContainer.prepend( '<img src="'+attachment.url+'" alt="" style="max-width:100%;"/>' );
					imgIdInput.val( attachment.id );
					delImgLink.removeClass( 'hidden' );
				});

				metaBox.data('frame').open();
			});

			delImgLink.on( 'click', function( event ) {

				event.preventDefault();
				imgContainer.find('img').remove();
				delImgLink.addClass( 'hidden' );
				imgIdInput.val('');

			});
		});
	}

	$(document).ready(function() {
		initTabs();
		initRadio();
		uploaders();
	});

})(jQuery);