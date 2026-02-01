<!-- Begin Page Content -->
<div class="container-fluid">
<!-- Page Heading -->
  <div class="d-sm-flex justify-content-between mb-4">
    <h1 class="h3 mb-0 text-gray-800">Pembukaan Saldo</h1>
    
  </div>
<!-- Page content -->
<div class="row mb-3">
  <div class="col">
    <div class="card shadow">
      <div class="card-body">
        <div class="row">
          <div class="col my-3">
            <div class="box-body box-bg ">
            <div class="make-container-center">
    <div class="box" id="print-section">
        <div class="box-body">
            <div class="">
            <?php
                $attributes = array('id'=>'open_balance_accounts','method'=>'post','class'=>'');
            ?>
            <?php echo form_open('statements/add_new_balance',$attributes); ?>
            <div class="">
                <div class="">
                    <div class="col-md-12 ">
                        
                        <div class="form-group">
                            <?php echo form_label('Nama Akun'); ?>
                              <select name="account_head" class="form-control select2 input-lg">
                                    <?php 
                                      foreach ($heads_record as $single_head) {
                                    ?>
                                         <option value="<?php echo $single_head->id ?>">
                                          <?php echo $single_head->name ?>
                                          </option>
                                    <?php   
                                      }
                                    ?>   
                              </select>
                        </div>  
                        
                        <div class="form-group">
                            <?php echo form_label('Jenis Transaksi Akun'); ?>
                              <select name="account_nature" class="form-control input-lg">
                                  <option value="0"> Debit </option> 
                                  <option value="1"> Kredit </option> 
                              </select>
                        </div>    
                        <div class="form-group">
                            <?php echo form_label('Jumlah'); ?>
                             <?php
                                $data = array('class'=>'form-control input-lg','type'=>'number','name'=>'amount','reqiured'=>'','step'=>'.01');
                                echo form_input($data);
                            ?>
                        </div>
                                     
                        <div class="form-group">
                            <?php echo form_label('Tanggal'); ?>
                             <?php
                                $data = array('class'=>'form-control input-lg','type'=>'date','name'=>'date','reqiured'=>'','value'=>Date('d/m/Y'));
                                echo form_input($data);
                            ?>
                        </div>                    
                        <div class="form-group">
                            <?php echo form_label('Keterangan'); ?>
                             <?php
                                $data = array('class'=>'form-control input-lg','type'=>'text','name'=>'description','reqiured'=>'');
                                echo form_input($data);
                            ?>
                        </div>         
                    </div>
                        <div class="form-group">
                          <?php
                              $data = array('class'=>'btn btn-info  margin btn-lg pull-right ','type' => 'submit','name'=>'btn_submit_balance','value'=>'true','id'=>'btn_save_transaction','content' => '<i class="fa fa-floppy-o" aria-hidden="true"></i> 
                                  Simpan ');
                              echo form_button($data);
                           ?>  
                        </div>    
                  </div>
                </div>
                <?php form_close(); ?>
            </div>
        </div>
    </div>

        </div>
                
        
       
        
          </div>
          </div>
        </div>
      </div>

        </div></div></div></div>
    <!-- /.container-fluid -->
  </div>
<!-- End of Main Content -->