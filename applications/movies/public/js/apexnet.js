/**
* @package apexnet
* @copyright (c) 2015 Markus Willman, markuwil <at> gmail <dot> com / www.apexdc.net
* @license http://opensource.org/licenses/gpl-2.0.php GNU General Public License v2
*/

function ApexNet(base_url) {
	this.google_libs = [];

	this.base_url = base_url;
	this.busy_template = '<div class="text-center"><em>{message}</em></div>';
}

ApexNet.prototype = {
	update: function (content, $container, speed) {
		speed = (!speed) ? 800 : speed / 2;
		$container.fadeOut(speed, function () { $(this).html(content).slideDown(speed); });
		return this;
	},

	set_busy_template: function (tpl_string) {
		this.busy_template = tpl_string;
	},

	busy: function ($container, message) {
		if (!message) message = 'Loading data...';
		$container.html(this.busy_template.replace(new RegExp('\{message\}', 'gi'), message));
		return this;
	},

	load: function ($container, resource, args) {
		var self = this;

		self.busy($container);
		resource = (!resource) ? $(location).attr('href') : resource;
		$.get(resource, args, function (response) {
			self.update(response, $container);
		});
	},

	paginate: function ($container, $filters) {
		var self = this;

		// prevent the event handler from invalidating on ajax requests
		$container.on('click', 'ul.pagination > li > a', function (e) {
			e.preventDefault();
			self.load($container, $(this).attr('href'));
		});

		if ($filters) {
			$filters.find('button.submit').hide();

			$filters.on('submit', function (e) {
				e.preventDefault();
				self.load($container, $filters.attr('action'), $filters.serialize());
			});

			$filters.find('select').on('change', function () {
				$filters.submit();
			});
		}

		return self;
	},

	select_option: function ($form, name, text) {
		return $form.find('select[name=' + name + '] option').filter(function() {
			return ($(this).text() == text);
		}).prop('selected', true);
	},

	option_text: function ($form, name) {
		return $form.find('select[name=' + name + '] option:selected').text();
	},

	google: function (lib, ver, callback) {
		var self = this;

		if (self.google_libs.length > 0) {
			if ($.inArray(lib, self.google_libs) == -1) {
				google.load(lib, ver, { 'callback': callback });
				self.google_libs.push(lib);
			} else {
				callback();
			}
			return self;
		} else {
			$.getScript('//www.google.com/jsapi', function () {
				google.load(lib, ver, { 'callback': callback });
				self.google_libs.push(lib);
			});
		}

		return self;
	},

	init_editor: function () {
		$.sceditor.plugins.bbcode.bbcode.set('heading', {
				tags: {
					h1: { 'data-size': null },
					h2: { 'data-size': null },
					h3: { 'data-size': null },
					h4: { 'data-size': null },
					h5: { 'data-size': null },
					h6: { 'data-size': null }
				},
				format: function(element, content) {
					var size = element.data('size');
					return '[heading=' + size + ']' + content + '[/heading]';
				},
				html: '<h{defaultattr} data-size="{defaultattr}">{0}</h{defaultattr}>',
				isInline: false,
				allowedChildren: ['#']
		});

		$.sceditor.command.set('heading', {
			_dropDown: function(editor, caller, callback) {
				content = $('<div />');
				for (var i = 1; i <= 6; ++i) {
					content.append(
						$('<a class="sceditor-heading-option" data-size="' + i + '" href="#">' +
							'<h' + i + '>Heading ' + i + '</h' + i + '>' +
						'</a>').click(function (e) { callback($(this).data('size')); editor.closeDropDown(true); e.preventDefault(); })
					);
				}

				editor.createDropDown(caller, 'heading-picker', content);
			},
			exec: function(caller) {
				var	editor = this;
				$.sceditor.command.get('heading')._dropDown(
					editor,
					caller,
					function(headingSize) {
						editor.insert('[heading=' + headingSize + ']', '[/heading]');
					}
				);
			},
			txtExec: function(caller) {
				var editor = this;
				$.sceditor.command.get('heading')._dropDown(
					editor,
					caller,
					function(headingSize) {
						editor.insertText('[heading=' + headingSize + ']', '[/heading]');
					}
				);
			},
			tooltip: 'Format Headings'
		});
	},

	load_editor: function ($container) {
		$container.sceditor({
			plugins: 'bbcode',
			style: this.base_url + '/components/sceditor/jquery.sceditor.default.min.css',
			emoticonsEnabled: false,
			resizeEnabled: false,
			toolbar: 'bold,italic,underline,strike,subscript,superscript|left,center,right|heading,color,removeformat|bulletlist,orderedlist|code,quote|image,link,unlink|date,time|source',
			autoExpand: true,
			parserOptions: { quoteType: $.sceditor.BBCodeParser.QuoteType.never }
		});
	},

	init_webshims: function (lang) {
		$.webshims.setOptions({
			'forms': {
				lazyCustomMessages: true,
				addValidators: true,
				iVal: {
					sel: '.ws-validate',
					handleBubble: 'hide', // hide error bubble

					//add bootstrap specific classes
					errorMessageClass: 'help-block',
					successWrapperClass: 'has-success',
					errorWrapperClass: 'has-error',

					//add config to find right wrapper
					fieldWrapper: '.form-group'
				}
			},
			'forms-ext': {
				widgets: { startView: 2, buttonOnly: true }
			}
		});

		if (!lang) lang = 'en';

		$.webshims.activeLang(lang);
		$.webshims.formcfg[lang].meridian = null;
	},

	load_webshims: function (shims) {
		$.webshims.polyfill(shims);
	}
};
