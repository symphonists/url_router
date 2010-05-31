<?php

	Class Extension_Router extends Extension{

		public function about() {
			return array('name' => 'URL Router',
						 'version' => '0.3',
						 'release-date' => '2010-04-07',
						 'author' => array('name' => 'Robert Philp',
										   'website' => 'http://robertphilp.com',
										   'email' => ''),
							'description'   => 'Allows URL routing for vanity URLs etc'
				 		);
		}

		public function install() {
            $this->_Parent->Database->query("
                    CREATE TABLE IF NOT EXISTS `tbl_router` (
                        `id` int(11) NOT NULL auto_increment,
                        `from` varchar(255) NOT NULL,
                        `to` varchar(255) NOT NULL,
                        PRIMARY KEY (`id`)
                    )
            ");
        }

        public function uninstall() {
            $this->_Parent->Database->query("DROP TABLE `tbl_router`");
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
			$routes = $this->_Parent->Database->fetch("SELECT * FROM tbl_router");
			return $routes;
        }

		public function save($context) {
			$routes = array();
			$route = array();
			foreach($context['settings']['router']['routes'] as $item) {
				if(isset($item['from'])) {
					$route['from'] = $item['from'];
				} else if(isset($item['to'])) {
					$route['to'] = $item['to'];
					$routes[] = $route;
					$route = array();
				}
			}

			$this->_Parent->Database->query("DELETE FROM tbl_router");
			$this->_Parent->Database->insert($routes, "tbl_router");
			unset($context['settings']['router']['routes']);
		}

		public function addCustomPreferenceFieldsets($context){
			$fieldset = new XMLElement('fieldset');
			$fieldset->setAttribute('class', 'settings');
			$fieldset->appendChild(new XMLElement('legend', 'Regex URL re-routing'));
 
			$p = new XMLElement('p', 'Define regex rules for URL re-routing');
			$p->setAttribute('class', 'help');
			$fieldset->appendChild($p);

			$group = new XMLElement('div');
			$group->setAttribute('class', 'subsection');
			$group->appendChild(new XMLElement('h3', __('URL Schema Rules')));
 
			$ol = new XMLElement('ol');
			$ol->setAttribute('id', 'router');
			$li = new XMLElement('li');
			$li->setAttribute('class', 'template');
			$labelfrom = Widget::Label(__('From'));
           	$labelfrom->appendChild(Widget::Input("settings[router][routes][][from]", "From"));
            $labelto = Widget::Label(__('To'));
            $labelto->appendChild(Widget::Input("settings[router][routes][][to]", "To"));
			$divgroup = new XMLElement('div');
			$divgroup->setAttribute('class', 'group');
			$divgroup->appendChild($labelfrom);
			$divgroup->appendChild($labelto);
	
			$divcontent = new XMLElement('div');
			$divcontent->setAttribute('class', 'content');
			$divcontent->appendChild($divgroup);			

			$li->appendChild(new XMLElement('h4', "Route"));
			$li->appendChild($divcontent);
			$ol->appendChild($li);
			if($routes = $this->getRoutes()) {
				if(is_array($routes)) {
					$i = 1;
					foreach($routes as $route) {
						$li = new XMLElement('li');
						$li->setAttribute('class', 'instance expanded');
						$h4 = new XMLElement('h4', 'Route');
						//$h4->appendChild(new XMLElement('span', 'Route'));
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
			$fieldset->appendChild($group);
			$context['wrapper']->appendChild($fieldset);	
		}

		public function frontendPrePageResolve($context) {
			$routes = $this->getRoutes();
			$url = $context['page'];
			$matches = array();
			foreach($routes as $route) {
				if(preg_match($route['from'], $url, &$matches) == 1) {
					$new_url = preg_replace($route['from'], $route['to'], $url);
					break;
				}
			}
			if($new_url) $context['page'] = $new_url;
		}
			
	}
