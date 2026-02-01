<?php
defined('BASEPATH') or exit('No direct script access allowed');

class Data extends CI_Controller
{
	// Homepage
	public function __construct()
	{
		parent::__construct();
		is_logged_in();
	}
	public function index()
	{
		$data['title'] = 'Daftar Aset Tetap';
		$data['user'] = $this->db->get_where('user', ['email' => 
		$this->session->userdata('email')])->row_array();
		
		//query data
		$data['id']	= $this->db->get('mp_aset')->result_array();
		
		if($this->form_validation->run() == false)
		{
			$this->load->view('templates/header', $data);
			$this->load->view('templates/sidebar', $data);
			$this->load->view('templates/topbar', $data);
			$this->load->view('data/daftaraset', $data);
			$this->load->view('templates/footer');
		}
		else
		{
		
			redirect('data');
		}

		
	}
	public function tambahaset()
	{
		$data['title'] = 'Daftar Aset Tetap';
		$data['user'] = $this->db->get_where('user', ['email' => 
		$this->session->userdata('email')])->row_array();
		$this->db->insert('mp_aset', [
				'id' 					=> $this->input->post('id'),
				'nama_aset' 			=> $this->input->post('nama_aset'),
				'tanggal_perolehan' 	=> $this->input->post('tanggal_perolehan'),
				'jumlah_unit' 			=> $this->input->post('jumlah_unit'),
				'umur_manfaat' 			=> $this->input->post('umur_manfaat'),
				'harga_perolehan' 		=> $this->input->post('harga_perolehan'),
				'akumulasi_penyusutan' 	=> $this->input->post('akumulasi_penyusutan')
			]);
		redirect('data');
	}

	public function dataPiutang(){
        $content = 'data/';
        $titleTag = 'Data Akun';
        $dataAkun = $this->akun->getAkun();
        $this->load->view('template',compact('content','dataAkun','titleTag'));
    }

	public function piutang()
	{
		$data['title'] = 'Daftar Piutang';
		$data['user'] = $this->db->get_where('user', ['email' => 
		$this->session->userdata('email')])->row_array();
		
		//query data
		$data['id']	= $this->db->get('mp_piutang')->result_array();
		
		if($this->form_validation->run() == false)
		{
			$this->load->view('templates/header', $data);
			$this->load->view('templates/sidebar', $data);
			$this->load->view('templates/topbar', $data);
			$this->load->view('data/daftarpiutang', $data);
			$this->load->view('templates/footer');
		}
		else
		{
		
			redirect('data/piutang');
		}
	}
	public function tambahpiutang()
	{
		$data['title'] = 'Daftar Piutang';
		$data['user'] = $this->db->get_where('user', ['email' => 
		$this->session->userdata('email')])->row_array();
		$this->db->insert('mp_piutang', [
				'id' 					=> $this->input->post('id'),
				'nama_piutang' 			=> $this->input->post('nama_piutang'),
				'tanggal_piutang' 		=> $this->input->post('tanggal_piutang'),
				'penambahan' 			=> $this->input->post('penambahan'),
				'pengurangan'	 		=> $this->input->post('pengurangan')
			]);
		redirect('data/piutang');
	}

	public function dataHutang(){
        $content = 'data/';
        $titleTag = 'Data Akun';
        $dataAkun = $this->akun->getAkun();
        $this->load->view('template',compact('content','dataAkun','titleTag'));
    }

	public function Hutang()
	{
		$data['title'] = 'Daftar Hutang';
		$data['user'] = $this->db->get_where('user', ['email' => 
		$this->session->userdata('email')])->row_array();
		
		//query data
		$data['id']	= $this->db->get('mp_hutang')->result_array();
		
		if($this->form_validation->run() == false)
		{
			$this->load->view('templates/header', $data);
			$this->load->view('templates/sidebar', $data);
			$this->load->view('templates/topbar', $data);
			$this->load->view('data/daftarhutang', $data);
			$this->load->view('templates/footer');
		}
		else
		{
		
			redirect('data/hutang');
		}
	}
	public function tambahhutang()
	{
		$data['title'] = 'Daftar Hutang';
		$data['user'] = $this->db->get_where('user', ['email' => 
		$this->session->userdata('email')])->row_array();
		$this->db->insert('mp_hutang', [
				'id' 					=> $this->input->post('id'),
				'nama_hutang' 			=> $this->input->post('nama_hutang'),
				'tanggal_hutang' 		=> $this->input->post('tanggal_hutang'),
				'penambahan' 			=> $this->input->post('penambahan'),
				'pengurangan'	 		=> $this->input->post('pengurangan')
			]);
		redirect('data/hutang');
	}

		
	
	
}