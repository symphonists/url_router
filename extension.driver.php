<?php

	Class Extension_Url_Router extends Extension{

		public function about()
		{
			return array('name' => 'URL Router',
				'version' => '1.1.0',
				'release-date' => '2011-07-08',
				'author' => array(
					'name' => 'Symphony Team',
					'website' => 'http://symphony-cms.com',
				),
				'description'   => 'Allows Regular Expression URL Routing in Symphony.'
			);
		}

		public function install()
		{
			Symphony::Database()->query("
					CREATE TABLE IF NOT EXISTS `tbl_url_router` (
						`id` int(11) NOT NULL auto_increment,
						`from` varchar(255) NOT NULL,
						`to` varchar(255) NOT NULL,
						`type` enum('route','redirect') DEFAULT 'route',
						PRIMARY KEY (`id`)
					)
			");
        }

		public function update($previousVersion)
		{
			if(version_compare($previousVersion, '1.1.0', '<'))
			{
				Symphony::Configuration()->remove('url-router');

				$type = Symphony::Database()->fetchVar('Field', 0, "SHOW COLUMNS FROM `tbl_url_router` LIKE 'type'");

				if(!$type)
				{
					return Symphony::Database()->query(
						"ALTER TABLE `tbl_url_router` ADD `type` ENUM('route','redirect') DEFAULT 'route'"
					);
				}

				return false;
			}
		}

        public function uninstall()
		{
            Symphony::Database()->query("DROP TABLE `tbl_url_router`");
        }

		public function getSubscribedDelegates()
		{
			return array(
				array(
					'page'		=> '/frontend/',
					'delegate'	=> 'FrontendPrePageResolve',
					'callback'	=> 'frontendPrePageResolve'
				),
				array(
					'page'		=> '/system/preferences/',
					'delegate'	=> 'AddCustomPreferenceFieldsets',
					'callback'	=> 'addCustomPreferenceFieldsets'
				),
				array(
					'page'      => '/system/preferences/',
					'delegate'  => 'Save',
					'callback'  => 'save'
				),
				array(
					'page' => '/backend/',
					'delegate' => 'InitaliseAdminPageHead',
					'callback' => 'initaliseAdminPageHead'
				)
			);
		}

		public function getRoutes()
		{
			return Symphony::Database()->fetch("SELECT * FROM tbl_url_router");
        }

		public function save($context)
		{
			$routes = array();

			if(!empty($context['settings']['url-router']['routes']))
			{
				$route = array();

				foreach($context['settings']['url-router']['routes'] as $item)
				{
					if(isset($item['type']) && !empty($item['type']))
					{
						$route['type'] = $item['type'];
					}
					if(isset($item['from']) && !empty($item['from']))
					{
						$route['from'] = $item['from'];
					}
					if(isset($item['to']) && !empty($item['to']))
					{
						$route['to'] = $item['to'];
						$routes[] = $route;
						$route = array();
					}
				}
			}

			Symphony::Database()->query("DELETE FROM tbl_url_router");

			if(count($routes) > 0) {
				Symphony::Database()->insert($routes, "tbl_url_router");
				unset($context['settings']['url-router']['routes']);
			}
		}

		public function addCustomPreferenceFieldsets($context)
		{
			$allow = $this->allow();

			$fieldset = new XMLElement('fieldset');
			$fieldset->setAttribute('class', 'settings');
			$fieldset->appendChild(new XMLElement('legend', 'URL Router'));

			$p = new XMLElement('p', 'Define regular expression rules for URL routing', array('class' => 'help'));
			$fieldset->appendChild($p);

			if($allow)
			{
				$p = new XMLElement('p', 'Choose between a <strong>Route</strong>, which silently redirects the content under the original URL, or a <strong>Redirect</strong> which will physically redirect to the new URL.');
				$fieldset->appendChild($p);

				$group = new XMLElement('div');
				$group->setAttribute('class', 'subsection');
				$group->appendChild(new XMLElement('p', __('Rules'), array('class' => 'label')));

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

				$divgroup = new XMLElement('div');
				$divgroup->setAttribute('class', 'group');

				$labelfrom = Widget::Label(__('From'));
				$labelfrom->appendChild(Widget::Input("settings[url-router][routes][][from]"));
				$labelto = Widget::Label(__('To'));
				$labelto->appendChild(Widget::Input("settings[url-router][routes][][to]"));
				$divgroup->appendChild($labelfrom);
				$divgroup->appendChild($labelto);

				$divcontent = new XMLElement('div');
				$divcontent->setAttribute('class', 'content');
				$divcontent->appendChild($divgroup);

				$li_re->appendChild(clone $divcontent);
				$li_ro->appendChild($divcontent);

				$ol->appendChild($li_ro);
				$ol->appendChild($li_re);

				if($routes = $this->getRoutes())
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

							$labelfrom = Widget::Label(__('From'));
							$labelfrom->appendChild(Widget::Input("settings[url-router][routes][][from]", General::sanitize($route['from'])));
							$labelto = Widget::Label(__('To'));
							$labelto->appendChild(Widget::Input("settings[url-router][routes][][to]", General::sanitize($route['to'])));
							$divgroup->appendChild($labelfrom);
							$divgroup->appendChild($labelto);

							$divcontent->appendChild($divgroup);
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
			$context['wrapper']->appendChild($fieldset);
		}

		public function allow()
		{
			$installed = Symphony::Database()->fetchVar('version', 0, "SELECT `version` FROM `tbl_extensions` WHERE `name` = 'url_router'");

			return version_compare($installed, $this->about['version'], '!=');
		}

		public function frontendPrePageResolve($context)
		{
			$allow = $this->allow();

			if($allow)
			{
				$routes = $this->getRoutes();

				foreach($routes as $route)
				{
					if(preg_match($route['from'], $context['page'], $matches) == 1)
					{
						$context['page'] = preg_replace($route['from'], $route['to'], $context['page']);

						if($route['type'] == 'redirect')
						{
							header("Location:" . $context['page']);
							die();
						}
						break;
					}
				}
			}
		}

		public function initaliseAdminPageHead($context)
		{
			$page = $context['parent']->Page;
			if($page instanceof contentSystemPreferences)
			{
				$page->addScriptToHead(URL . '/extensions/url_router/assets/urlrouter.preferences.js', 400, false);
			}
		}
	}
