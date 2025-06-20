
// js launchpad
// based on github.com/linkorb/launchpad # (c) 2015 LinkORB
// (c) 2025-present unix-world.org
// r.20250223.2358

(function(w, d) {
	const lp = {
		data: [],
		overlay: null,
		escapeHtml: function(str) {
			//-- format sting
			if((str == undefined) || (str == '')) {
				return '';
			} //end if
			//-- force string
			str = String(str);
			//-- replacements map
			var map = {
				'&': '&amp;',
				'<': '&lt;',
				'>': '&gt;',
				'"': '&quot;'
			};
			//-- do replace
			return String(str.replace(/[&\<\>"]/g, function(m){ return map[m] })); // fix to return empty string instead of null
			//--
		},
		init: function() {
			return this.createOverlay().populateApps();
		},
		createOverlay: function () {
			if(document.getElementById('launchpad_overlay')) {
				return this;
			}
			this.overlay = d.createElement('div');
			this.overlay.id = 'launchpad_overlay';
			this.overlay.style.display = 'none';
			this.overlay.addEventListener('click', this.overlayClick);
			d.body.appendChild(this.overlay);
			return this;
		},
		populateApps: function() {
			let html = '';
			for(let i = 0; i < this.data.length; i++) {
				if(this.data[i].group) {
					html += this.drawAppGroup(this.data[i]);
				} else {
					html += this.drawApp(this.data[i]);
				}
			}
			this.overlay.innerHTML = '<div class="launchpad-canvas">' + html + '</div>';
			return this;
		},
		drawAppGroup: function(group) {
			let h = '<div class="launchpad-app-group"><div class="launchpad-app-group-header">' + this.escapeHtml(group.group) + '</div>';
			h+= '<div class="launchpad-app-group-body">';
			for (let i = 0; i < group.apps.length; i++) {
				h += this.drawApp(group.apps[i]);
			}
			h += '</div></div>';
			return h;
		},
		drawApp: function(app) {
			let h = '';
			h += '<div class="launchpad-app-container">';
			h += '<a href="' + this.escapeHtml(app.link || '#') + '">';
			h += '<div class="launchpad-app-icon">' + '<img src="' + this.escapeHtml(app.icon || 'data:,') + '" height="100%">' + '</div>';
			h += '<div class="launchpad-app-label">' + this.escapeHtml(app.label || 'App') + '</div>';
			h += '</a>';
			h += '</div>';
			return h;
		},
		toggle: function () {
			if(this.overlay.style.display == 'none') {
				this.overlay.style.display = '';
				setTimeout(function(){ launchpad.overlay.style.opacity = 1; }, 20);
			} else {
				this.overlay.style.display = 'none';
				this.overlay.style.opacity = 0;
			}
			this.toggleClass(d.body, 'launchpad-active');
			return this;
		},
		toggleClass: function (element, className) {
			let classString = element.className, nameIndex = classString.indexOf(className);
			if(nameIndex == -1) {
				classString = className; // this should be: addClass(className)
			} else {
				classString = ''; // this should be: removeClass(className)
			}
			element.className = classString;
		},
		overlayClick: function(event) {
			launchpad.toggle();
		},
		setData: function(data) {
			this.data = data;
			return this;
		}
	}
	w.launchpad = lp;
	w.onload = function(){
		lp.init();
	};
})(window, document);

// #end
