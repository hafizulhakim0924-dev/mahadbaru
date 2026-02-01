<!-- Select2 -->

<div class="modal-header">
	
	<h4 class="modal-title"><i class="fa fa-plus-square" aria-hidden="true"></i> 
		Tambah Akun
	</h4>
	<button type="button" class="close" data-dismiss="modal">&times;</button>
</div>
<div class="modal-body">
	<div class="row-md-12">
		<div class="box box-danger">
			<div class="box-body">	
				<div class="col">
					<?php
						$attributes = array('id'=>'chart_of_accounts_form','method'=>'post','class'=>'form-horizontal');
					?>
					<?php echo form_open($link,$attributes); ?>	
					<div class="form-group">
						<label>Kelompok Akun:</label>				
						<select class="form-control select2 input-lg" onchange="visible_expense(this.value),visible_revenue(this.value)" name="nature" id="nature"  style="width: 100%;">
								<option value="Assets" >Assets</option>
								<option value="Liability" >Liability</option>
								<option value="Equity" >Equity</option>
								<option value="Expense" >Expense</option>
								<option value="Revenue" >Revenue</option>
								<option value="HPP" >HPP</option>
						</select>
					</div>		
					<div class="form-group">
						<?php echo form_label('Nama Akun:'); ?>
						<?php			
								$data = array('class'=>'form-control input-lg','type'=>'text','name'=>'name','placeholder'=>'Contoh Pety Cash','reqiured'=>'');
								echo form_input($data);			
						?>
					</div>			
									
					<div class="form-group">
						<label>Tipe Akun :</label>				
						<select class="form-control select2 input-lg" name="type" id="type"  style="width: 100%;">
								<option value="Lancar" >Lancar (Current)</option>
								<option value="Tetap" >Tetap (Fixed)</option>
						</select>
					</div>
					<div class="form-group" id="expense-type-id">
						<label>Jenis Beban :</label>		
						<select class="form-control select2 input-lg" name="expense_type" id="expense_type" >
							<option value="Pengeluaran Zakat" >Pengeluaran Zakat</option>
							<option value="Pengeluaran Infak" >Pengeluaran Infak</option>
							<option value="Pengeluaran Amil" >Pengeluaran Amil</option>
						</select>
					</div>
					<div class="form-group" id="revenue-type-id">
						<label>Jenis Pendapatan :</label>		
						<select class="form-control select2 input-lg" name="revenue_type" id="revenue_type" >
							<option value="Penerimaan Zakat" >Penerimaan Zakat</option>
							<option value="Penerimaan Infak" >Penerimaan Infak</option>
							<option value="Penerimaan Amil" >Penerimaan Amil</option>
						</select>
					</div>
					<div class="form-group">
						<?php
							$data = array('class'=>'d-none d-sm-inline-block btn btn-sm btn-primary shadow-sm','type' => 'submit','name'=>'btn_submit_medicine','value'=>'true', 'content' => '<i class="fa fa-floppy-o" aria-hidden="true"></i> Simpan Akun ');
							
							echo form_button($data);
						?>
					</div>
					<?php echo form_close(); ?>
				</div>
			</div>
		</div>
	</div>
</div>
 <!-- Form Validation -->
<script src="<?php echo base_url(); ?>assets/dist/js/custom.js"></script>
<script type="text/javascript">
	//USED TO VISIBLE EXPENSE TYPE 
	function visible_expense(value)
	{
		if(value == 'Expense')
		{
			$('#expense-type-id').css('display','block');
		}
		else
		{
			$('#expense-type-id').css('display','none');
		}
		
	}
	function visible_revenue(value)
	{
		if(value == 'Revenue')
		{
			$('#revenue-type-id').css('display','block');
		}
		else
		{
			$('#revenue-type-id').css('display','none');
		}
		
	}
</script>