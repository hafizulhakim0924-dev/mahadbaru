<?php
defined('BASEPATH') or exit('No direct script access allowed');

class Auth extends CI_Controller
{
	public function __construct()
	{
		parent::__construct();
		$this->load->library('form_validation');
	}
	public function index()
	{
		if($this->session->userdata('email'))
		{
			redirect('dashboard');
		}
		$this->form_validation->set_rules('email', 'Email', 'trim|required|valid_email');
		$this->form_validation->set_rules('password', 'Password', 'trim|required');
		if($this->form_validation->run() == false) 
		{
			$data['title'] = 'Login';
			$this->load->view('templates/auth_header', $data);
			$this->load->view('auth/login');
			$this->load->view('templates/auth_footer');
		} else
		{
			//validasi sukses
			$this->_login();
		}
		
	}
	//====================login====================//
	private function _login()
	{
		//input data ke database
		$email 		= $this->input->post('email');
		$password 	= $this->input->post('password');

		//get data dari database
		$user 		= $this->db->get_where('user', ['email' => $email])->row_array();
		
		//jika usernya ada
		if($user) 
		{
			//jika usernya aktif
			if($user['is_active'] == 1)
			{
				//cek password
				if(password_verify($password, $user['password']))
				{
					$data = [
						'email'		=> $user['email'],
						'role_id'	=> $user['role_id']
					];
					$this->session->set_userdata($data);
					//cek role admin atau bukan
					if($user['role_id'] == 1)
					{
						redirect('admin');
					}
					else
					{
						redirect('dashboard');
					}
					
				}
				else
				{
					$this->session->set_flashdata('message', '<div class="alert alert-danger" role="alert">Password salah</div>');
					redirect('auth');
				}
			}
			else
			{
			$this->session->set_flashdata('message', '<div class="alert alert-danger" role="alert">Email belum diaktivasi</div>');
			redirect('auth');
			}

		} 
		else 
		{
			//gagal
			$this->session->set_flashdata('message', '<div class="alert alert-danger" role="alert">Email belum terdaftar</div>');
			redirect('auth');
		}
	}

	//====================daftar====================//
	public function registration()
	{
		if($this->session->userdata('email'))
		{
			redirect('user');
		}
		$this->form_validation->set_rules('name', 'Name', 'required|trim');
		$this->form_validation->set_rules('email', 'Email', 'required|trim|valid_email|is_unique[user.email]', [
			'is_unique' => 'Email sudah terdaftar!'
		]);
		$this->form_validation->set_rules('password1', 'Password', 'required|trim|min_length[3]|matches[password2]', [
			'matches' => 'Password tidak sama!',
			'min_length' => 'Password terlalu pendek'
		]);
		$this->form_validation->set_rules('password2', 'Password', 'required|trim|matches[password1]');

		if($this->form_validation->run() == false) 
		{
			$data['title'] = 'Daftar';
			$this->load->view('templates/auth_header', $data);
			$this->load->view('auth/registration');
			$this->load->view('templates/auth_footer');
		} else 
		{
			$data =
			[
				'name' 			=> htmlspecialchars($this->input->post('name', true)),
				'email' 		=> htmlspecialchars($this->input->post('email', true)),
				'image' 		=> 'default.jpg',
				'password' 		=> password_hash($this->input->post('password1'), PASSWORD_DEFAULT),
				'role_id'		=> 2,
				'is_active'		=> 1,
				'date_created'	=> time()
			];

			$this->db->insert('user', $data);
			$this->session->set_flashdata('message', '<div class="alert alert-success" role="alert">Selamat, akun telah dibuat. Silahkan login</div>');
			redirect('auth');
		}
		
	}

	//====================logout====================//
	public function logout()
	{
		$this->session->unset_userdata('email');
		$this->session->unset_userdata('role_id');

		$this->session->set_flashdata('message', '<div class="alert alert-success" role="alert">Logout berhasil</div>');
		redirect('auth');
	}

	//====================block====================//
	public function blocked()
	{
		$this->load->view('auth/blocked');
	}

}