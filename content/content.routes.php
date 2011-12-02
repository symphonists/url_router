<?php

	require_once(TOOLKIT . '/class.administrationpage.php');

	class contentExtensionUrl_routerRoutes extends AdministrationPage {

		public function __construct(&$parent){
			parent::__construct($parent);

			$this->_driver = Symphony::ExtensionManager()->create('url_router');
		}

		public function __actionIndex() {
			$this->_driver->save();
		}

		public function __viewIndex() {
			$this->setPageType('form');
			$this->addScriptToHead(URL . '/extensions/url_router/assets/urlrouter.preferences.js', 400, false);

			$this->setTitle(__('%1$s &ndash; %2$s', array(__('Symphony'), __('URL Router'))));
			$this->appendSubheading(__('URL Router'));

			$allow = $this->_driver->allow();

			$fieldset = new XMLElement('fieldset');
			$fieldset->setAttribute('class', 'settings');
			$fieldset->appendChild(new XMLElement('legend', 'Routes'));

			$fieldset->appendChild(new XMLElement('p', __('Choose between a <strong>Route</strong>, which silently shows the content under the original URL, or a <strong>Redirect</strong> which will actually redirect the user to the new URL.'), array('class' => 'help')));

			if($allow)
			{

				$group = new XMLElement('div');
				$group->setAttribute('class', 'subsection');

				$ol = new XMLElement('ol');
				$ol->setAttribute('id', 'url-router-duplicator');
				$ol->setAttribute('class', 'orderable duplicator collapsible');

			//	Redirect Template
				$li_re = new XMLElement('li');
				$li_re->setAttribute('class', 'template');
				$h4_re = new XMLElement('h4', 'Redirect');
				$h4_re->setAttribute('class', 'header');
				$hidden_re = Widget::Input("settings[url-router][routes][][type]", 'redirect', 'hidden');
				$li_re->appendChild($h4_re);
				$li_re->appendChild($hidden_re);

			//	Route Template
				$li_ro = new XMLElement('li');
				$li_ro->setAttribute('class', 'template');
				$h4_ro = new XMLElement('h4', 'Route');
				$h4_ro->setAttribute('class', 'header');
				$hidden_ro = Widget::Input("settings[url-router][routes][][type]", 'route', 'hidden');
				$li_ro->appendChild($h4_ro);
				$li_ro->appendChild($hidden_ro);

			//	From To boxes
				$divgroup = new XMLElement('div');
				$divgroup->setAttribute('class', 'group');
				$labelfrom = Widget::Label(__('From'));
				$labelfrom->appendChild(Widget::Input("settings[url-router][routes][][from]"));
				$labelfrom->appendChild(new XMLElement('p', 'Simplified: <code>page-name/:user/projects/:project</code>', array('class' => 'help', 'style' => 'margin: 0.5em 0 -0.5em;
')));
				$labelfrom->appendChild(new XMLElement('p', 'Regular expression: <code>/\/page-name\/(.+\/)/</code> Wrap in <code>/</code> and ensure to escape metacharacters with <code>\\</code>', array('class' => 'help', 'style' => 'margin: 0.5em 0 -0.5em;
')));

				$labelto = Widget::Label(__('To'));
				$labelto->appendChild(Widget::Input("settings[url-router][routes][][to]"));
				$labelto->appendChild(new XMLElement('p', 'Simplified: <code>/new-page-name/:user/:project</code>', array('class' => 'help', 'style' => 'margin: 0.5em 0 -0.5em;
')));
				$labelto->appendChild(new XMLElement('p', 'Regular expression: <code>/new-page-name/$1/</code>', array('class' => 'help', 'style' => 'margin: 0.5em 0 -0.5em;
')));


				$divgroup->appendChild($labelfrom);
				$divgroup->appendChild($labelto);

				$divcontent = new XMLElement('div');
				$divcontent->setAttribute('class', 'content');
				$divcontent->appendChild($divgroup);

				$recontent = clone $divcontent;

				$regroup = new XMLElement('div');
				$regroup->setAttribute('class', 'group');
				$label = Widget::Label();
				$input = Widget::Input('settings[url-router][routes][][http301]', 'yes', 'checkbox');
				$label->setValue($input->generate() . ' Send an HTTP 301 Redirect');
				$regroup->appendChild($label);
				$recontent->appendChild($regroup);

				$divgroup = new XMLElement('div');
				$divgroup->setAttribute('class', 'group');
				$label = Widget::Label();
				$input = Widget::Input('settings[url-router][routes][][http301]', 'yes', 'checkbox');
				$label->setValue($input->generate() . ' Force re-route if page exists');
				$divgroup->appendChild($label);
				$divcontent->appendChild($divgroup);

				$li_re->appendChild($recontent);
				$li_ro->appendChild($divcontent);

				$ol->appendChild($li_ro);
				$ol->appendChild($li_re);

				if($routes = $this->_driver->getRoutes())
				{
					if(is_array($routes))
					{
						foreach($routes as $route)
						{
							if($route['type'] == 'redirect')
							{
								$h4 = new XMLElement('h4', 'Redirect');
							}
							else
							{
								$h4 = new XMLElement('h4', 'Route');
							}

							$hidden = Widget::Input("settings[url-router][routes][][type]", $route['type'], 'hidden');

							$li = new XMLElement('li');
							$li->setAttribute('class', 'instance expanded');
							$h4->setAttribute('class', 'header');
							$li->appendChild($h4);
							$li->appendChild($hidden);

							$divcontent = new XMLElement('div');
							$divcontent->setAttribute('class', 'content');

							$divgroup = new XMLElement('div');
							$divgroup->setAttribute('class', 'group');

							$from = $route['from'];
							if (isset($route['from-clean'])) $from = $route['from-clean'];
							
							$labelfrom = Widget::Label(__('From'));
							$labelfrom->appendChild(Widget::Input("settings[url-router][routes][][from]", General::sanitize($from)));
							$labelfrom->appendChild(new XMLElement('p', 'Simplified: <code>page-name/:user/projects/:project</code>', array('class' => 'help', 'style' => 'margin: 0.5em 0 -0.5em;
			')));
							$labelfrom->appendChild(new XMLElement('p', 'Regular expression: <code>/\/page-name\/(.+\/)/</code> Wrap in <code>/</code> and ensure to escape metacharacters with <code>\\</code>', array('class' => 'help', 'style' => 'margin: 0.5em 0 -0.5em;
			')));

							$to = $route['to'];
							if (isset($route['to-clean'])) $to = $route['to-clean'];
							
							$labelto = Widget::Label(__('To'));
							$labelto->appendChild(Widget::Input("settings[url-router][routes][][to]", General::sanitize($to)));
							$labelto->appendChild(new XMLElement('p', 'Simplified: <code>/new-page-name/:user/:project</code>', array('class' => 'help', 'style' => 'margin: 0.5em 0 -0.5em;
			')));
							$labelto->appendChild(new XMLElement('p', 'Regular expression: <code>/new-page-name/$1/</code>', array('class' => 'help', 'style' => 'margin: 0.5em 0 -0.5em;
			')));

							$divgroup->appendChild($labelfrom);
							$divgroup->appendChild($labelto);

							$divcontent->appendChild($divgroup);
							if($route['type'] == 'redirect')
							{
								$regroup = new XMLElement('div');
								$regroup->setAttribute('class', 'group');

								$label = Widget::Label();
								$input = Widget::Input('settings[url-router][routes][][http301]', 'yes', 'checkbox');
								if($route['http301'] == 'yes')
								{
									$input->setAttribute('checked', 'checked');
								}
								$label->setValue($input->generate() . ' Send an HTTP 301 Redirect');
								$regroup->appendChild($label);
								$divcontent->appendChild($regroup);
							}
							else
							{
								$divgroup = new XMLElement('div');
								$divgroup->setAttribute('class', 'group');

								$label = Widget::Label();
								$input = Widget::Input('settings[url-router][routes][][http301]', 'yes', 'checkbox');
								if($route['http301'] == 'yes')
								{
									$input->setAttribute('checked', 'checked');
								}
								$label->setValue($input->generate() . ' Force re-route if page exists');
								$divgroup->appendChild($label);
								$divcontent->appendChild($divgroup);
							}
							$li->appendChild($divcontent);
							$ol->appendChild($li);
						}
					}
				}

				$group->appendChild($ol);

				$fieldset->appendChild($group);
			}
			else
			{
				$p = new XMLElement('p', 'This extension\'s code has been updated. For this extension to operate corrcectly, please update it on the Extensions page.');
				$fieldset->appendChild($p);
				$p = new XMLElement('p', '<strong>All current URL routing has been disabled due to changes to the code, and will not be re-enabled until the above step has been taken!</strong>');
				$fieldset->appendChild($p);
			}



			$this->Form->appendChild($fieldset);

			$div = new XMLElement('div');
			$div->setAttribute('class', 'actions');

			$div->appendChild(Widget::Input('action[save]', __('Save Changes'), 'submit', $attr));

			$this->Form->appendChild($div);

		}
	}

?>