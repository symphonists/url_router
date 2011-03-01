<?php

	Class Extension_Router extends Extension{

		public function about() {
			return array('name' => 'URL Router',
						 'version' => '0.5',
						 'release-date' => '2010-04-07',
						 'author' => array('name' => 'Robert Philp',
										   'website' => 'http://robertphilp.com',
										   'email' => ''),
							'description'   => 'Allows URL routing for vanity URLs etc'
				 		);
		}

		public function install() {
            Symphony::Database()->query("
                    CREATE TABLE IF NOT EXISTS `tbl_router` (
                        `id` int(11) NOT NULL auto_increment,
                        `from` varchar(255) NOT NULL,
                        `to` varchar(255) NOT NULL,
                        PRIMARY KEY (`id`)
                    )
            ");
        }

        public function uninstall() {
            Symphony::Database()->query("DROP TABLE `tbl_router`");
        }


		public function getSubscribedDelegates() {
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
			);
		}

		public function getRoutes() {
			$routes = Symphony::Database()->fetch("SELECT * FROM tbl_router");
			return $routes;
        }

		public function save($context) {
			//var_dump($context['settings']['router']['routes']);die;
			$routes = array();

			if ($context['settings']['router']['routes']) {
				$route = array();
				foreach($context['settings']['router']['routes'] as $item) {
					if(isset($item['from']) && !empty($item['from'])) {
						$route['from'] = $item['from'];
					}
					if(isset($item['to']) && !empty($item['to'])) {
						$route['to'] = $item['to'];
						$routes[] = $route;
						$route = array();
					}
				}
			}

			Symphony::Database()->query("DELETE FROM tbl_router");

			if (count($routes) != 0) {
				Symphony::Database()->insert($routes, "tbl_router");
				unset($context['settings']['router']['routes']);
			}

			if(!is_array($context['settings'])) $context['settings'] = array('router' => array('redirect' => 'no'));

			elseif(!isset($context['settings']['router']['redirect'])){
				$context['settings']['router'] = array('redirect' => 'no');
			}
		}

		public function addCustomPreferenceFieldsets($context){
			$fieldset = new XMLElement('fieldset');
			$fieldset->setAttribute('class', 'settings');
			$fieldset->appendChild(new XMLElement('legend', 'Regex URL re-routing'));

			$p = new XMLElement('p', 'Define regex rules for URL re-routing');
			$p->setAttribute('class', 'help');
			$fieldset->appendChild($p);
/*
			$p = new XMLElement('p', 'Example');
			$fieldset->appendChild($p);
			$p = new XMLElement('p', 'From: /\/vanity\/(.*?)/i to: /article/my-long-url/$1');
			$fieldset->appendChild($p);
			$p = new XMLElement('p', 'Will map: /vanity/page/2 to: /article/my-long-url/page/2');
			$fieldset->appendChild($p);
*/
			$group = new XMLElement('div');
			$title = new XMLElement('p', __('URL Schema Rules'));
			$title->setAttribute('class', 'label');
			$group->appendChild($title);

			$ol = new XMLElement('ol');
			$ol->setAttribute('id', 'duplicator');
			$ol->setAttribute('class', 'duplicator');

			$li = new XMLElement('li');
			$li->setAttribute('class', 'instance');
			$h4 = new XMLElement('h3', 'Route');
			$h4->setAttribute('class', 'header');
			$li->appendChild($h4);

			$divgroup = new XMLElement('div');
			$divgroup->setAttribute('class', 'group');
			$labelfrom = Widget::Label(__('From'));
           	$labelfrom->appendChild(Widget::Input("settings[router][routes][][from]"));
            $labelto = Widget::Label(__('To'));
            $labelto->appendChild(Widget::Input("settings[router][routes][][to]"));
			$divgroup->appendChild($labelfrom);
			$divgroup->appendChild($labelto);

			$divcontent = new XMLElement('div');
			$divcontent->setAttribute('class', 'content');
			$divcontent->appendChild($divgroup);

			$li->appendChild($divcontent);
			$ol->appendChild($li);
			if($routes = $this->getRoutes()) {
				if(is_array($routes)) {
					$i = 1;
					foreach($routes as $route) {
						$li = new XMLElement('li');
						$li->setAttribute('class', 'instance');
						$h4 = new XMLElement('h4', 'Route');
						$h4->setAttribute('class', 'header');
						$li->appendChild($h4);

						$divcontent = new XMLElement('div');
						$divcontent->setAttribute('class', 'content');
						$divgroup = new XMLElement('div');
						$divgroup->setAttribute('class', 'group');
						$labelfrom = Widget::Label(__('From'));
						$labelfrom->appendChild(Widget::Input("settings[router][routes][][from]", General::sanitize($route['from'])));
						$labelto = Widget::Label(__('To'));
						$labelto->appendChild(Widget::Input("settings[router][routes][][to]", General::sanitize($route['to'])));
						$divgroup->appendChild($labelfrom);
						$divgroup->appendChild($labelto);

						$divcontent->appendChild($divgroup);
						$li->appendChild($divcontent);
						$ol->appendChild($li);

						$i++;
					}
				}
			}
			$group->appendChild($ol);

			$label = Widget::Label();
			$input = Widget::Input('settings[router][redirect]', 'yes', 'checkbox');
			if($this->_Parent->Configuration->get('redirect', 'router') == 'yes') $input->setAttribute('checked', 'checked');
			$label->setValue($input->generate() . ' ' . __('Redirect legacy URLs to new destination'));
			$fieldset->appendChild($label);

			$fieldset->appendChild(new XMLElement('p', __('Redirects requests to the new destination instead of just displaying the content under the legacy URL.'), array('class' => 'help')));

			$fieldset->appendChild($group);
			$context['wrapper']->appendChild($fieldset);
		}

		public function frontendPrePageResolve($context) {
			$routes = $this->getRoutes();
			$url = $context['page'];
			foreach($routes as $route) {
				if(preg_match($route['from'], $url, $matches) == 1) {
					$new_url = preg_replace($route['from'], $route['to'], $url);
					if(Symphony::Configuration()->get('redirect', 'router') == 'yes') {
						header("Location:" . $new_url);
						die();
					}
					break;
				}
			}
			if($new_url) $context['page'] = $new_url;
		}

	}
