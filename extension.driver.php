<?php

	Class Extension_Router extends Extension{
	
		public function about() {
			return array('name' => 'URL Router',
						 'version' => '0.1',
						 'release-date' => '2010-01-12',
						 'author' => array('name' => 'Robert Philp',
										   'website' => 'http://robertphilp.com',
										   'email' => ''),
							'description'   => 'Allows URL routing for vanity URLs etc'
				 		);
		}

		
		public function getSubscribedDelegates() {
			return array(
				array(
					'page'		=> '/frontend/',
					'delegate'	=> 'FrontendPrePageResolve',
					'callback'	=> 'frontendPrePageResolve'
				),
				array(
					'page'		=> '/backend/',
					'delegate'	=> 'InitaliseAdminPageHead',
					'callback'	=> 'initaliseAdminPageHead'
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
			#phpinfo();
           	$router = $this->_Parent->Configuration->get('router');
			#var_dump($router);
			#var_dump($router);	
			#var_dump(json_decode($router['routes'], true));
			return json_decode(stripslashes($router['routes']), true);
			#return $router['routes'];
        }

		public function save($context) {
			#echo "<pre>";
			#print_r($context['settings']);
			#echo "</pre>";

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
			$routedata = json_encode($routes);
			#echo "<pre>";
			#print_r($routes);
			#echo "</pre>";
	
			#$this->_Parent->Configuration->setArray($router);
			$context['settings']['router']['routes'] = $routedata;
			#echo "<pre>";
            #print_r($context['settings']);
            #echo "</pre>";
		}	

		public function initaliseAdminPageHead($context) {
			$page = $context['parent']->Page;
			$page->addScriptToHead(URL . '/extensions/router/assets/router.js', 200);
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
			$rules = $routes['routes'];
			$url = $context['page'];
			$matches = array();
			foreach($rules as $rule) {
				if(preg_match($rule['from'], $url, &$matches) == 1) {
					$new_url = preg_replace($rule['from'], $rule['to'], $url);
				}
			}
			if($new_url) $context['page'] = $new_url;
		}
			
	}

?>
