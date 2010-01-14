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
				)
			);
		}

		public function getRoutes() {
            return $this->_Parent->Configuration->get('router');
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
			if($router = $this->_Parent->Configuration->get('router')) {
				if(isset($router['routes']) && !empty($router['routes'])) {
					$i = 1;
					foreach($router['routes'] as $route) {
						$li = new XMLElement('li');
						$li->setAttribute('class', 'instance expanded');
						$h4 = new XMLElement('h4');
						$h4->appendChild(new XMLElement('span', 'Route'));
						$li->appendChild($h4);
						$divcontent = new XMLElement('div');
						$divcontent->setAttribute('class', 'content');
						$divgroup = new XMLElement('div');
						$divgroup->setAttribute('class', 'group');
						$labelfrom = Widget::Label(__('From'));
						$labelfrom->appendChild(Widget::Input("fields[route][{$i}][from]", General::sanitize($route['from'])));
						$labelto = Widget::Label(__('To'));
						$labelto->appendChild(Widget::Input("fields[route][{$i}][to]", General::sanitize($route['to'])));
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
