<!-- Begin Page Content -->
<div class="container-fluid">
<!-- Page Heading -->
  <div class="d-sm-flex justify-content-between mb-4">
    <h1 class="h3 mb-0 text-gray-800">Daftar Piutang</h1>
    <div class="d-sm-flex align-items-center">
            <a href="" class="d-none d-sm-inline-block btn btn-sm btn-primary shadow-sm mr-2" data-toggle="modal" data-target="#newPiutangModal"><i class="fas fa-plus fa-sm" data-toggle="modal" data-target="#newPiutangModal"></i> Tambah Piutang Baru</a>
            
            
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
                    <h5 style="text-align:center"><b>Daftar Piutang</b></h5>
                   <br>
                </div>
            <div class="col-md-3"></div>  
        </div>
            
            
                            <table class="table table-hover" id="dataTable">
                              <thead>
                                <tr>
                                  <th scope="col">No</th>
                                  <th scope="col">Nama</th>
                                  <th scope="col">Tanggal</th>
                                  <th scope="col">Debit</th>
                                  <th scope="col">Kredit</th>
                                </tr>
    
                              </thead>
                              <tbody>
                                <?php $i = 1; ?>
                                <?php foreach($id as $id) : ?>
                                <tr>
                                  <th scope="row"><?= $i; ?></th>
                                  <td><?= $id['nama_piutang']; ?></td>
                                  <td><?= $id['tanggal_piutang']; ?></td>
                                  <td><?= "Rp " . number_format($id['penambahan'],0,',','.'); ?></td>
                                  <td><?= "Rp " . number_format($id['pengurangan'],0,',','.'); ?></td>
                                </tr>
                                <?php $i++; ?>
                                <?php endforeach; ?>
                              </tbody>
                            </table>

                            <div class="nav-wrapper mx-3">
                <ul class="nav nav-pills nav-fill flex-column flex-md-row" id="tabs-icons-text" role="tablist">
                    
                    
                    
                </ul>
            </div>
            
            
        </div>
        
        
       
        
          
          </div>
        </div>
      </div>
</div></div>
        </div></div>
    <!-- /.container-fluid -->
  </div>
<!-- End of Main Content -->   


<!-- Modal -->
<div class="modal fade" id="newPiutangModal" tabindex="-1" aria-labelledby="newPiutangModalLabel" aria-hidden="true">
  <div class="modal-dialog">
    <div class="modal-content">
      <div class="modal-header">
        <h5 class="modal-title" id="newPiutangModalLabel">Tambah Piutang Baru</h5>
        <button type="button" class="close" data-dismiss="modal" aria-label="Close">
          <span aria-hidden="true">&times;</span>
        </button>
      </div>
      <form action="<?= base_url('data/tambahpiutang'); ?>" method="post" id="formD" name="formD" >
      <div class="modal-body">
          <div class="form-group">
            <label>Nama Piutang :</label>
            <input type="text" class="form-control" id="nama_piutang" name="nama_piutang">
          </div>
          
          <div class="form-group">
            <label>Tanggal Piutang :</label>
            <input type="date" class="form-control" id="tanggal_piutang" name="tanggal_piutang" onkeyup="OnChange(this.value)" onKeyPress="return isNumberKey(event)">
          </div>
          <div class="form-group">
            <label>Debit :</label>
            <input type="text" class="form-control" id="penambahan" name="penambahan" onkeyup="OnChange(this.value)" onKeyPress="return isNumberKey(event)">
          </div>
          <div class="form-group">
            <label>Kredit :</label>
            <input type="text" class="form-control" id="pengurangan" name="pengurangan" onkeyup="OnChange(this.value)" onKeyPress="return isNumberKey(event)">
          </div>
          
          
      </div>
      <div class="modal-footer">
        <button type="button" class="btn btn-secondary" data-dismiss="modal">Batal</button>
        <button type="submit" class="btn btn-primary">Tambah</button>
      </div>
      
      </form>


      
 

    </div>
  </div>
</div>