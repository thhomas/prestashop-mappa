<?php
/**
 * 
 * 
 * 
 * 
 * 
 */


class AdminMappaController extends ModuleAdminController {
	public function __construct() {
		$this->context = Context::getContext();
		parent::__construct();
	}
	

	public function renderForm() {
	
		//some basics information, only used to include your own javascript
		/*$this->context->smarty->assign(array(
		'mymodule_controller_url' => $this->context->link->getAdminLink('Adminmymodule'),//give the url for ajax query
		));*/
		// cf tuto http://www.custommyself.com/prestashop-tutorial-create-a-module-with-an-admin-panel/
		$this->fields_form = array(
				'tinymce' => true,
				'legend' => array(
						'title' => $this->l('Configuration de la carte.')
				),
				'input' => array(
						array(
								'type' => 'text',
								'label' => $this->l("Description")." :",
								'name' => 'description',
								'size' => 40,
								'required' => true,
								'hint' => $this->l('The text that will be added to the attribute description.')
						)
				)
		);
		//add the save button
		$this->fields_form['submit'] = array(
				'title' => $this->l('   Save   '),
				'class' => 'button'
		);
	
		//what do we want to add to the default template
		$more = $this->module->display($path, 'view/mappa.tpl');
	
		return $more.parent::renderForm();//add you own information to the rendered template
	}
	

	
}