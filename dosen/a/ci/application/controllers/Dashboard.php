<?php
/*

*/
defined('BASEPATH') OR exit('No direct script access allowed');
class Dashboard extends CI_Controller
{
	// Homepage
	public function index()
	{
		$data['user'] = $this->db->get_where('user', ['email' => $this->session->userdata('email')])->row_array();
		// DEFINES TO LOAD THE CATEGORY RECORD FROM DATABSE TABLE mp_Categoty
		$this->load->model('Crud_model');
		$this->load->model('Statement_model');
		$this->load->model('Accounts_model');

		// DEFINES PAGE TITLE
		$data['title'] = 'Dashboard';

		// DEFINES NAME OF TABLE HEADING
		$data['table_name'] = 'product Category List :';

		// DEFINES BUTTON NAME ON THE TOP OF THE TABLE
		$data['page_add_button_name'] = 'Add New Category';

		// DEFINES THE TITLE NAME OF THE POPUP
		$data['page_title_model'] = 'Add New Category';

		// DEFINES THE NAME OF THE BUTTON OF POPUP MODEL
		$data['page_title_model_button_save'] = 'Save Category Name';

		// DEFINES WHICH PAGE TO RENDER
		$data['main_view'] = 'dashboard';

		// DIFINES THE TABLE HEAD
		$data['table_heading_names_of_coloums'] = array(
			'No',
			'Category Name',
			'description',
			'Date',
			'Added By',
			'Status',
			'Actions'
		);



		//CASH IN HAND
		$data['cash_in_hand'] = $this->Statement_model->count_head_amount_by_id(96);		

		//ACCOUNT RECEIVABLE
		$data['account_recieveble'] = $this->Statement_model->count_head_amount_by_id(98);		

		//CASH IN BANK
		$data['cash_in_bank'] = $this->Statement_model->count_head_amount_by_id(16);		

		//PAYABLES
		$data['payables'] = $this->Statement_model->count_head_amount_by_id(97);		



		// DEFINES GO TO MAIN FOLDER FOND INDEX.PHP  AND PASS THE ARRAY OF DATA TO THIS PAGE
		
		$this->load->view('templates/header', $data);
    $this->load->view('templates/sidebar', $data);
    $this->load->view('templates/topbar', $data);
   $this->load->view('data/index.php', $data);
    $this->load->view('templates/footer');
	}

	// Homepage/sign_out
	public function sign_out()
	{
		$this->session->unset_userdata('user_id');
		redirect('/Login');
	}
}