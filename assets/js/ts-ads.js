(function() {
	"use strict";

	var a, b, c, d, e, i;

	if( this.ts_banner_ads ) {
		return;
	}

	this.ts_banner_ads = {
		ads: []
	};

	ts_banner_ads.initialize = function() {

		if( !document.getElementsByClassName ) {
			return;
		}

		a = document.getElementsByClassName('ts-banner-ad');

		for( i = 0; i < a.length; i++ ) {
			b = a[i];
			if( b.dataset.src && b.dataset.size ) {
				c = b.dataset.size.split('x');
				ts_banner_ads.ads.push({
					element: b,
					src: b.dataset.src,
					size: c
				});
				b.style.maxWidth = c[0] + 'px';
				b.style.border = 'none';
				d = c[1] / c[0] * b.clientWidth;
				b.style.height = d + 'px';
				if( b.dataset.align ) {
					switch( b.dataset.align ) {
						case 'inline-left':
						b.style.float = 'left';
						break;
						case 'inline-right':
						b.style.float = 'right';
						break;
						case 'center':
						b.style.margin = 'auto';
						break;
					}
					if( b.dataset.align == 'left' || b.dataset.align == 'right' || b.dataset.align == 'center' ) {
						b.style.display = 'inline-block';
						e = document.createElement('div');
						e.setAttribute('id', 'ts-ad-container-' + i);
						b.parentNode.insertBefore(e, b);
						e.appendChild(b);
						e.style.width = '100%';
						e.style.margin = '10px 0';
						e.style.textAlign = b.dataset.align;
					}
				}
				b.innerHTML = '<iframe src="' + b.dataset.src + '" height="100%" width="100%" frameborder="no"></iframe>';
			}
		}

		window.onresize = function() {
			for( i = 0; i < ts_banner_ads.ads.length; i++ ) {
				a = ts_banner_ads.ads[i];
				b = a.size[0];
				c = a.size[1];
				d = c / b * a.element.offsetWidth;
				a.element.style.height = d + 'px';
			}
		}

	}

	ts_banner_ads.ready = function() {
		ts_banner_ads.initialize();
		if( document.removeEventListener ) {
			document.removeEventListener( 'DOMContentLoaded', ts_banner_ads.ready, false);
		} else if( document.detachEvent ) {
			document.detachEvent( 'onreadystatechange', ts_banner_ads.ready );
		}
	}

	if( document.addEventListener ) {
		document.addEventListener( 'DOMContentLoaded', ts_banner_ads.ready, false );
	} else if( document.attachEvent ) {
		document.attachEvent( 'onreadystatechange', ts_banner_ads.ready );
	}

}).call(this);