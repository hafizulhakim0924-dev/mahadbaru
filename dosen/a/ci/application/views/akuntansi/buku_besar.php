<!-- Begin Page Content -->
<div class="container-fluid">
<!-- Page Heading -->
  <div class="d-sm-flex justify-content-between mb-4">
    <h1 class="h3 mb-0 text-gray-800">Buku Besar</h1>
    
  </div>
<!-- Page content -->
<div class="row mb-3">
  <div class="col">
    <div class="card shadow">
        <div class="card-header py-3">
            <h6 class="m-0 font-weight-bold text-secondary">Cari Periode Waktu</h6>
        </div>
      <div class="card-body">
        <div class="row">
          <div class="col my-3">
            <div class="box-body box-bg ">
            <div class="make-container-center">
            <?php
                $attributes = array('id'=>'leadgerAccounst','method'=>'post','class'=>'');
            ?>
            <?php echo form_open_multipart('statements/leadgerAccounst',$attributes); ?>
            <div class="row no-print">
                <div class="col-md-3">
                    <div class="form-group">
                        <?php echo form_label('Dari Tanggal'); ?>
                        <?php
                            $data = array('class'=>'form-control input-lg','type'=>'date','name'=>'from','reqiured'=>'');
                            echo form_input($data);
                        ?>
                    </div>
                </div>                    
                <div class="col-md-3">
                    <div class="form-group">
                        <?php echo form_label('Sampai Tanggal'); ?>
                        <?php
                            $data = array('class'=>'form-control input-lg','type'=>'date','name'=>'to','reqiured'=>'');
                            echo form_input($data);
                        ?>
                    </div>
                </div>
                <div class="col-md-3">
                    <div class="form-group" style="margin-top:16px;">
                        <?php
                            $data = array('class'=>'d-none d-sm-inline-block btn btn-danger shadow-sm mt-3','type' => 'submit','name'=>'btn_submit_customer','value'=>'true', 'content' => '<i class="fa fa-floppy-o" aria-hidden="true"></i> 
                                Tampilkan Buku Besar');
                            echo form_button($data);
                         ?>  
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
<!-- Page content -->
<div class="row mt-3 mb-3">
  <div class="col">
    <div class="card shadow">
      <div class="card-body">
        <div class="row">
          <div class="col my-3">
        <div class="box-body box-bg ">
            <div class="row">
            <div class="col-md-3"></div>
                <div class="col-md-6">
                    <h5 style="text-align:center"><b>BUKU BESAR</b></h5>
                    <h5 style="text-align:center"><b>Nama Usaha</b></h5>
                   <h5 style="text-align:center"><b> Periode </b> <?php echo $from; ?> <b> s/d </b> <?php echo $to; ?></h5>
                </div>
            <div class="col-md-3"></div>  
        </div>
                <div class="row">
            <?php echo $ledger_records; ?>
        </div>
        
       
        
          </div>
          </div>
        </div>
      </div>
</div></div>
        </div></div></div></div>
    <!-- /.container-fluid -->
  </div>
<!-- End of Main Content -->                


