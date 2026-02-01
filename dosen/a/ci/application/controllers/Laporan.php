<?php
defined('BASEPATH') or exit('No direct script access allowed');

class Laporan extends CI_Controller
{
	//menghalangi masuk tanpa login
	public function __construct()
	{
		parent::__construct();
		is_logged_in();
	}
	//================================ posisi keuangan ================================//
	public function posisi_keuangan()
	{
		$data['title'] = 'Laporan Posisi Keuangan';
		$data['user'] = $this->db->get_where('user', ['email' => $this->session->userdata('email')])->row_array();
		
		//tampilkan view
		$this->load->view('templates/header', $data);
		$this->load->view('templates/sidebar', $data);
		$this->load->view('templates/topbar', $data);
		$this->load->view('laporan/posisi_keuangan', $data);
		$this->load->view('templates/footer');
	}

	//================================ jurnal umum ================================//
	public function laba_rugi()
	{
		$data['title'] = 'Laporan Laba Rugi';
		$data['user'] = $this->db->get_where('user', ['email' => $this->session->userdata('email')])->row_array();
		
		//tampilkan view
		$this->load->view('templates/header', $data);
		$this->load->view('templates/sidebar', $data);
		$this->load->view('templates/topbar', $data);
		$this->load->view('laporan/laba_rugi', $data);
		$this->load->view('templates/footer');
	}
}