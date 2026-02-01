<?php
defined('BASEPATH') or exit('No direct script access allowed');

class User extends CI_Controller
{
	//menghalangi masuk tanpa login
	public function index()
	{
		$data['title'] = 'Profil Saya';
		$data['user'] = $this->db->get_where('user', ['email' => $this->session->userdata('email')])->row_array();
		
		//tampilkan view
		$this->load->view('templates/header', $data);
		$this->load->view('templates/sidebar', $data);
		$this->load->view('templates/topbar', $data);
		$this->load->view('user/index', $data);
		$this->load->view('templates/footer');
	}

	public function edit()
	{
		$data['title'] = 'Edit Profile';
		$data['user'] = $this->db->get_where('user', ['email' => $this->session->userdata('email')])->row_array();
		
		$this->form_validation->set_rules('name', 'Nama', 'required|trim');
		if($this->form_validation->run() == false)
		{
			//tampilkan view
			$this->load->view('templates/header', $data);
			$this->load->view('templates/sidebar', $data);
			$this->load->view('templates/topbar', $data);
			$this->load->view('user/edit', $data);
			$this->load->view('templates/footer');
		}
		else
		{
			$name = $this->input->post('name');
			$email = $this->input->post('email');

			//cek gambar
			$upload_image = $_FILES['image']['name'];

			if($upload_image)
			{
				$config['allowed_types']	= 'gif|jpg|png';
				$config['max_size']			= '2048';
				$config['upload_path']		= './assets/img/profile/';
				$this->load->library('upload', $config);

				if($this->upload->do_upload('image'))
				{
					$old_image = $data['user']['image'];
					if($old_image != 'default.jpg')
					{
						unlink(FCPATH. 'assets/img/profile/' . $old_image);
					}


					$new_image = $this->upload->data('file_name');
					$this->db->set('image', $new_image);
				}
				else
				{
					$this->session->set_flashdata('message', '<div class="alert alert-danger" role="alert">' . $this->upload->display_errors() . '</div>');
					redirect('user');
				}

			}

			$this->db->set('name', $name);
			$this->db->where('email', $email);
			$this->db->update('user');
			$this->session->set_flashdata('message', '<div class="alert alert-success" role="alert">Profil telah diubah</div>');
			redirect('auth/logout');
		}
		
	}
	public function edit2()
	{
		$data['title'] = 'Edit Email';
		$data['user'] = $this->db->get_where('user', ['email' => $this->session->userdata('email')])->row_array();
		$this->form_validation->set_rules('email', 'Email', 'required|trim');
		$this->form_validation->set_rules('name', 'Nama', 'required|trim');

		if($this->form_validation->run() == false)
		{
			//tampilkan view
			$this->load->view('templates/header', $data);
			$this->load->view('templates/sidebar', $data);
			$this->load->view('templates/topbar', $data);
			$this->load->view('user/editemail', $data);
			$this->load->view('templates/footer');
		}
		else
		{
			$name = $this->input->post('name');
			$email = $this->input->post('email');

			



			$this->db->set('email', $email);
			$this->db->where('name', $name);
			$this->db->update('user');
			redirect('auth/logout');
		}
		
	}

	public function edit3()
	{
		$data['title'] = 'Edit Nama Pengguna';
		$data['user'] = $this->db->get_where('user', ['email' => $this->session->userdata('email')])->row_array();
		$this->form_validation->set_rules('email', 'Email', 'required|trim');
		$this->form_validation->set_rules('name', 'Nama', 'required|trim');

		if($this->form_validation->run() == false)
		{
			//tampilkan view
			$this->load->view('templates/header', $data);
			$this->load->view('templates/sidebar', $data);
			$this->load->view('templates/topbar', $data);
			$this->load->view('user/editnama', $data);
			$this->load->view('templates/footer');
		}
		else
		{
			$name = $this->input->post('name');
			$email = $this->input->post('email');

			



			$this->db->set('name', $name);
			$this->db->where('email', $email);
			$this->db->update('user');
			redirect('dashboard');
		}
		
	}

	public function edit4()
	{
		$data['title'] = 'Edit Nama Usaha';
		$data['user'] = $this->db->get_where('user', ['email' => $this->session->userdata('email')])->row_array();
		$data['user_company'] = $this->db->get_where('user_company')->row_array();
		$this->form_validation->set_rules('id', 'Id', 'required|trim');
		$this->form_validation->set_rules('name', 'Name', 'required|trim');
$this->form_validation->set_rules('loc', 'Loc', 'required|trim');
		if($this->form_validation->run() == false)
		{
			//tampilkan view
			$this->load->view('templates/header', $data);
			$this->load->view('templates/sidebar', $data);
			$this->load->view('templates/topbar', $data);
			$this->load->view('user/editusaha', $data);
			$this->load->view('templates/footer');
		}
		else
		{
			$name = $this->input->post('name');
$loc = $this->input->post('loc');
			



			$this->db->set('name', $name);
			$this->db->where('loc', $loc);
			$this->db->update('user_company');
			redirect('dashboard');
		}
		
	}


	public function changePassword()
	{
		$data['title'] = 'Ubah Password';
		$data['user'] = $this->db->get_where('user', ['email' => $this->session->userdata('email')])->row_array();

		$this->form_validation->set_rules('current_password', 'Current Password', 'required|trim');
		$this->form_validation->set_rules('new_password1', 'New Password', 'required|trim|matches[new_password2]');
		$this->form_validation->set_rules('new_password2', 'Confirm New Password', 'required|trim|matches[new_password1]');

	if($this->form_validation->run() == false){
		//tampilkan view
		$this->load->view('templates/header', $data);
		$this->load->view('templates/sidebar', $data);
		$this->load->view('templates/topbar', $data);
		$this->load->view('user/changepassword', $data);
		$this->load->view('templates/footer');
	}
	else
	{
		$current_password = $this->input->post('current_password');
		$new_password = $this->input->post('new_password1');
		if (!password_verify
			($current_password, $data['user']['password'])
		)
		{
			$this->session->set_flashdata('message', '<div class="alert alert-danger" role="alert">Salah</div>');
			redirect('user/changepassword');
		}
		else
		{
			if($current_password == $new_password)
			{
				$this->session->set_flashdata('message', '<div class="alert alert-danger" role="alert">Tidak Boleh Sama</div>');
				redirect('user/changepassword');
			}
			else
			{
				$password_hash = password_hash($new_password, PASSWORD_DEFAULT);
				$this->db->set('password', $password_hash);
				$this->db->where('email', $this->session->userdata('email'));
				$this->db->update('user');
				$this->session->set_flashdata('message', '<div class="alert alert-success" role="alert">Berhasil</div>');
				redirect('auth/logout');
			}
		}
	}
	}
}