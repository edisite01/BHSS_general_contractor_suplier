<?php

if (!defined('BASEPATH'))
    exit('No direct script access allowed');

class Project extends Admin_Controller
{
	public $tbl 	= 'mitra_project';
    function __construct()
    {
		parent::__construct();  
		$screen = array(
			'app-assets/css/pages/project.css',
			'app-assets/css/pages/timeline.css',
		);      
		$files = array(
			'app-assets/js/scripts/pages/timeline.js',
			'app-assets/vendors/js/charts/gmaps.min.js',
		);      
               // $this->add_script($files);  
                $this->add_stylesheet($screen);

    }

    public function index()
    {
        $this->mPageTitle = 'Project';
        $this->render('project/project_list');
    } 
    public function Create() {
        $this->mPageTitle = 'Project';
        $this->render('project/project_create');
	}
	public function Detail($kode_project = '', $project_id = '') {
		$this->mPageTitle = 'Project Monitoring';

		$this->mViewData['log_project'] = $this->project->Project_log_get($kode_project);
		$this->mViewData['project_id'] = $project_id;
		$this->mViewData['project_kode'] = $kode_project;
		$this->mViewData['rab_data'] = $this->loadRAB($kode_project);
		$this->mViewData['mitra_id'] =	$this->session->userdata('mitra_id');
        $this->render('project/project_detail');
	}
	public function loadRAB($kode_project = null){   

	$select = ' * ';		
	
	$where['data'][] = array(
		'column' => 'mitra_project_kode =',
		'param'	 => $kode_project
	);

	$query = $this->mod->select($select, 'v_rab', NULL, $where, NULL, NULL, null, null);
		$response = '';    
		$resfooter = '';    
		if ($query<>false) {
					$no = 1;
					foreach ($query->result() as $val) {                        
						$rab_saldo_awal     = (float)($val->rab_saldo_awal) ?: 0;
						$rab_saldo_akhir    = (float)($val->rab_saldo_akhir) ?: 0;
						
						if(empty($rab_saldo_awal)): $rab_saldo_awal = 0; endif;
						if(empty($rab_saldo_akhir)): $rab_saldo_akhir = 0; endif;
						$saldo = $rab_saldo_awal - $rab_saldo_akhir;

						if(empty($saldo)): $saldo = 0; endif;
						
						$persen = $this->HowPersen($saldo, $rab_saldo_akhir); 
						if($val->rab_induk == 0 || $val->rab_induk = ''){
							$resfooter .=  "
											<tr class='bg-yellow bg-lighten-4'>
												<td colspan='2' align='center'><strong>TOTAL</strong></td>
												<td align='right'><strong>".$this->negativeValue($rab_saldo_awal)."</strong></td>
												<td align='right'><strong>".$this->negativeValue($rab_saldo_akhir)."</strong></td>
												<td align='right'><strong>".$this->negativeValue($saldo)."</strong></td>
												<td align='center'><strong>".$persen."</strong></td>
											</tr>
							";		
						}else{
							$response .=  "
											<tr>
												<td>".$val->rab_id."</td>
												<td>".$val->rab_nama."</td>
												<td align='right'>".$this->negativeValue($rab_saldo_awal)."</td>
												<td align='right'>".$this->negativeValue($rab_saldo_akhir)."</td>
												<td align='right'>".$this->negativeValue($saldo)."</td>
												<td align='center'>".$persen."</td>
											</tr>
							";	
						}					
					}
					return $response.$resfooter;
		}
		$response = "<tr>
						<td colspan='6'>RAB Belum disusun</td>						
					</tr>";
		return $response;
}
    
    // Function Insert & Update
    public function postData(){
		$id = $this->input->post('kode');
		if (strlen($id)>0) {
			//UPDATE
			$data = $this->general_post_data(2, $id);
			$where['data'][] = array(
				'column' => 'barang_id',
				'param'	 => $id
			);
			$update = $this->mod->update_data_table($this->tbl, $where, $data);
			if($update->status) {
				$response['status'] = '200';
				$queryKonversi = $this->mod->select('*', 'm_konversi', null, $where);
				if($queryKonversi) {
					for($i = 0; $i < sizeof($this->input->post('konversi_akhir_satuan', TRUE)); $i++) {
						$dataKonversi = $this->general_post_data3(2, $val->konversi_id, $i, $id);
						if(@$where_det['data']) {
							unset($where_det['data']);
						}
						$where_det['data'][] = array(
							'column'	=> 'jenis_produksidet_id',
							'param'		=> $this->input->post('jenis_produksidet_id', TRUE)[$i]
						);
						$update_det = $this->mod->update_data_table('m_konversi', $where, $dataKonversi);
						if($update_det->status) {
							$response['status'] = '200';
						} else {
							$response['status'] = '204';
						}
					}
					foreach ($queryKonversi->result() as $val) {
						$whereKonversi['data'][] = array(
							'column' => 'konversi_id',
							'param'	 => $val->konversi_id
						);
						$updateKonversi = $this->mod->update_data_table('m_konversi', $whereKonversi, $dataKonversi);
					}
				}
				else
				{
					$dataKonversi = $this->general_post_data3(1, null, $id);
					$insert = $this->mod->insert_data_table('m_konversi', NULL, $dataKonversi);
				}
				if($data['barang_status_aktif'] == 'n')
				{
					$updateAttr = $this->nonaktif_atribut($id);
				}
			} else {
				$response['status'] = '204';
			}
		} 
			else {
			//INSERT
			$this->load->helper('string');
			$getproject_kode = $this->project->SelectByMitraID($this->session->userdata('mitra_id'));
			if($getproject_kode){
				foreach ($getproject_kode as $val) {
					$lastkodemitra = $val->project_kode;
				}
				$lastkodemitra  = increment_string($lastkodemitra);
			}else{
				$lastkodemitra	= "P_190001";
			}
			$data = array(
                            'project_mitra_id'  	=> $this->session->userdata('mitra_id'),
                            'project_kode'  		=> $lastkodemitra,
                            'project_nama'  		=> $this->input->post('project_nama'),
                            'project_detail'  		=> $this->input->post('project_detail'),
                            'project_create_date'  	=> date('Y-m-d H:i:s'),
                            'project_create_by'  	=> $this->session->userdata('identity'),
                            'project_status'  		=> '0',
                        );
                        
			$insert = $this->mod->insert_data_table('mitra_project', NULL, $data);
			$this->project->Project_log($lastkodemitra, '1_1','Mitra Open Project','Mitra mulai membuat project baru');

			if($insert->status) {
					$response['status'] = '200';
			} else {
					$response['status'] = '204';
			}
		}
		
		echo json_encode($response);
	}
    public function postData_rab(){
		$id = $this->input->post('kode');
		if (strlen($id)>0) {
			//UPDATE
			$data = $this->general_post_data(2, $id);
			$where['data'][] = array(
				'column' => 'barang_id',
				'param'	 => $id
			);
			$update = $this->mod->update_data_table($this->tbl, $where, $data);
			if($update->status) {
				$response['status'] = '200';
				$queryKonversi = $this->mod->select('*', 'm_konversi', null, $where);
				if($queryKonversi) {
					for($i = 0; $i < sizeof($this->input->post('konversi_akhir_satuan', TRUE)); $i++) {
						$dataKonversi = $this->general_post_data3(2, $val->konversi_id, $i, $id);
						if(@$where_det['data']) {
							unset($where_det['data']);
						}
						$where_det['data'][] = array(
							'column'	=> 'jenis_produksidet_id',
							'param'		=> $this->input->post('jenis_produksidet_id', TRUE)[$i]
						);
						$update_det = $this->mod->update_data_table('m_konversi', $where, $dataKonversi);
						if($update_det->status) {
							$response['status'] = '200';
						} else {
							$response['status'] = '204';
						}
					}
					foreach ($queryKonversi->result() as $val) {
						$whereKonversi['data'][] = array(
							'column' => 'konversi_id',
							'param'	 => $val->konversi_id
						);
						$updateKonversi = $this->mod->update_data_table('m_konversi', $whereKonversi, $dataKonversi);
					}
				}
				else
				{
					$dataKonversi = $this->general_post_data3(1, null, $id);
					$insert = $this->mod->insert_data_table('m_konversi', NULL, $dataKonversi);
				}
				if($data['barang_status_aktif'] == 'n')
				{
					$updateAttr = $this->nonaktif_atribut($id);
				}
			} else {
				$response['status'] = '204';
			}
		} 
		else {
			//INSERT
			$data = array(
				// 'mitra_id'				=> $this->session->userdata('mitra_id'), 
				'mitra_project_kode'	=> $this->input->post('project_kode'), 
				'rab_id'				=> $this->input->post('rab_kode'), 
				'rab_nama'				=> $this->input->post('nama_rab'), 
				'rab_induk'				=> $this->input->post('m_mitra_rab_select'), 
				'rab_saldo_awal'		=> $this->input->post('saldo_awal') ?: 0, 
				'rab_create_by'			=> $this->session->userdata('identity'), 
				'rab_create_date'		=> date('Y-m-d H:i:s'), 
			);
			$insert = $this->mod->insert_data_table('mitra_rab', NULL, $data);
			$response['status'] = '200';
			
		}
		echo json_encode($response);
	}
	public function loadData(){
		$select = '*';
		//LIMIT
		$limit = array(
			'start'  => $this->input->get('start') ?: 0,
			'finish' => $this->input->get('length') ?: 10
		);
		// $where['data'][] = array(
		// 	'column' => 'project_mitra_id',
		// 	'param'	 => $this->session->userdata('mitra_id')
		// );
		//WHERE LIKE
		$where_like['data'][] = array(
			'column' => 'project_kode, project_nama, project_status',
			'param'	 => $this->input->get('search[value]')
		);
		//ORDER
		$index_order = $this->input->get('order[0][column]');
		$order['data'][] = array(
			'column' => $this->input->get('columns['.$index_order.'][name]'),
			'type'	 => $this->input->get('order[0][dir]')
		);

		$query_total = $this->mod->select($select, $this->tbl);
		$query_filter = $this->mod->select($select, $this->tbl, NULL, null, NULL, $where_like, $order);
		$query = $this->mod->select($select, $this->tbl, NULL, null, NULL, $where_like, $order, $limit);

		$response['data'] = array();
		if ($query<>false) {
			$no = $limit['start']+1;
			
			foreach ($query->result() as $val) {
				$button = '';
				if ($val->project_aktifasi == 'AKTIVE') {
					$status = '<span class="text-success"> Aktif </span>';
					
						$button = $button.'<button class="btn mr-1 mb-1 btn-outline-primary btn-sm" type="button" onclick="openFormBarang('.$val->project_id.')" title="Edit" data-toggle="modal" href="#modaladd">
											<i class="icon-pencil text-center"></i>
										</button>';
								// <button class="btn blue-soft" type="button" onclick="openFormValueBarang('.$val->barang_id.')" title="Edit Value" data-toggle="modal" href="#modaladd">
								// 	<i class="icon-notebook text-center"></i>
								// </button>';
					
						$button = $button.'
									<button class="btn mr-1 mb-1 btn-outline-danger btn-sm" type="button" onclick="deleteData('.$val->project_id.')" title="Non Aktifkan">
							<i class="icon-power text-center"></i>
						</button>';
					
					
				} else {
					$status = '<span class="text-danger"> Non Aktif </span>';

						$button = $button.'<button class="btn mr-1 mb-1 btn-outline-primary btn-sm" type="button" onclick="openFormBarang('.$val->project_id.')" title="Edit" data-toggle="modal" href="#modaladd" disabled>
											<i class="icon-pencil text-center"></i>
										</button>';
								// <button class="btn blue-soft" type="button" onclick="openFormValueBarang('.$val->produk_kode.')" title="Edit Value" data-toggle="modal" href="#modaladd" disabled>
								// 	<i class="icon-notebook text-center"></i>
								// </button>';

						$button = $button.'<button class="btn mr-1 mb-1 btn-outline-success btn-sm" type="button" onclick="aktifData('.$val->project_id.')" title="Aktifkan">
						<i class="icon-power text-center"></i>
						</button>';
					
					
				}
				$projectkode = "
					<a href='".base_url()."mitra/project/detail/".$val->project_kode."/".$val->project_id."' class='text-bold-600'>#".$val->project_kode."</a>
					
				";
				$projectnama = "
					<a href='".base_url()."mitra/project/detail/".$val->project_kode."/".$val->project_id."' class='text-bold-600'>".$val->project_nama."</a>
					<p class='text-muted font-small-2'>".substr($val->project_detail,0,30)."...</p>
				";
				$response['data'][] = array(
					$no,
					$projectkode,
					$projectnama,
					$val->project_create_date,
					$status,
					$button
				);
				$no++;
			}
		}

		$response['recordsTotal'] = 0;
		if ($query_total<>false) {
			$response['recordsTotal'] = $query_total->num_rows();
		}
		$response['recordsFiltered'] = 0;
		if ($query_filter<>false) {
			$response['recordsFiltered'] = $query_filter->num_rows();
		}

		echo json_encode($response);
	}
	public function PostingProject(){
		$project		= $this->input->post('project');
		$mitra_id 		= $this->input->post('mitra_id');

		$this->project->Project_log($project, '1_2','Mitra Submit Project','');

			$response['status'] = '200';		
		$this->project->UpdateStatus($project,'2_1');
			echo json_encode($response);

	}
	public function Rab($project_kode = '', $project_id = '')
	{
		# code...
		$this->mViewData['project_kode']	= $project_kode;

		$this->mPageTitle = 'Rencana Anggaran Biaya Project';
        $this->render('project/V_rab');
	}
	public function Barang($project_kode = '', $project_id = '')
	{
		# code...
		$this->mViewData['project_kode']	= $project_kode;

		$this->mPageTitle = 'Rencana Anggaran Biaya Project';
        $this->render('project/V_rab');
	}
	public function loadData_rab(){
		$select = '*';
		//LIMIT
		$limit = array(
			'start'  => $this->input->get('start') ?: 0,
			'finish' => $this->input->get('length') ?: 10
		);
		//WHERE LIKE
		$where_like['data'][] = array('column' => 'rab_id, rab_nama, rab_induk, rab_saldo_awal, rab_saldo_akhir ',
			'param'	 => $this->input->get('search[value]')
		);
		//ORDER
		$index_order = $this->input->get('order[0][column]');
		$order['data'][] = array(
			'column' => $this->input->get('columns['.$index_order.'][name]'),
			'type'	 => $this->input->get('order[0][dir]')
		);

		$query_total = $this->mod->select($select, 'mitra_rab');
		$query_filter = $this->mod->select($select, 'mitra_rab', NULL, NULL, NULL, $where_like, $order);
		$query = $this->mod->select($select, 'mitra_rab', NULL, NULL, NULL, $where_like, $order, $limit);

		$response['data'] = array();
		if ($query<>false) {
			$no = $limit['start']+1;
			
			foreach ($query->result() as $val) {
				$button = '';
				$response['data'][] = array(
				
					$val->rab_id,
					$val->rab_nama,
					$val->rab_induk,
					number_format($val->rab_saldo_awal, 2, '.', ','),
					// number_format($val->rab_saldo_akhir, 2, '.', ','),
					$button
				);
				$no++;
			}
		}

		$response['recordsTotal'] = 0;
		if ($query_total<>false) {
			$response['recordsTotal'] = $query_total->num_rows();
		}
		$response['recordsFiltered'] = 0;
		if ($query_filter<>false) {
			$response['recordsFiltered'] = $query_filter->num_rows();
		}

		echo json_encode($response);
	}
}
