<!-- Begin Page Content -->
<div class="container-fluid">
<!-- Page Heading -->
  <div class="d-sm-flex justify-content-between mb-4">
    <h1 class="h3 mb-0 text-gray-800">LPD Zakat</h1>
    
    <div class="pull pull-right">
                <button onclick="printDiv('print-section')" class="d-none d-sm-inline-block btn btn-sm btn-primary shadow-sm"><i class="fas fa-print fa-sm"></i> Cetak</button>
            </div>
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
            <?php echo form_open_multipart('statements/zakat_statement',$attributes); ?>
            <div class="row no-print">
                <div class="col-md-3 ">
                    <div class="form-group">
                        <?php echo form_label('Pilih Tahun'); ?>
                        <select class="form-control input-lg" name="year">
                            <option  value="2019"> 2019</option>
                            <option  value="2020"> 2020</option>
                            <option  value="2021"> 2021</option>
                            <option  value="2022"> 2022</option>
                            <option  value="2023"> 2023</option>
                            <option  value="2024"> 2024</option>
                            <option  value="2025"> 2025</option>
                            <option  value="2026"> 2026</option>
                        </select>
                    </div>
                </div>                    
                <div class="col-md-3 ">
                    <div class="form-group" style="margin-top:16px;">               
                        <?php
                            $data = array('class'=>'d-none d-sm-inline-block btn btn-danger shadow-sm mt-3','type' => 'submit','name'=>'btn_submit_customer','value'=>'true', 'content' => '<i class="fa fa-floppy-o" aria-hidden="true"></i> 
                                Buat Statement');
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

        <div class="box" id="print-section">
        
        <div class="row">
            <div class="col-md-3"></div>
                <div class="col-md-6">
                  <h5 style="text-align:center"><b>LPD ZAKAT</b></h5>
                  <h5 style="text-align:center"><b>Nama Yayasan</b></h5>
                   </h5>
                   <h5 style="text-align:center"> Periode: <?php echo $from.' s/d '.$to; ?>
                   </h5>                   
                
                </div>
                <div class="col-md-3"></div>  
        </div>
        <div class="row">
            <div class="col-md-12">
                 <table class="table table-hover">
                     <tbody>
                        <?php echo $income_records; ?>
                     </tbody>
                 </table>
            </div>
           
        </div>
            </div>
        </div>
    </div>
</section>
          </div>
        
         
      </div>

        </div></div></div></div>
    <!-- /.container-fluid -->
  </div>
<!-- End of Main Content -->
