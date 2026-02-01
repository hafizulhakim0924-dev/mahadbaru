<!-- Modal -->
<div class="modal fade" id="newAsetModal" tabindex="-1" aria-labelledby="newAsetModalLabel" aria-hidden="true">
  <div class="modal-dialog">
    <div class="modal-content">
      <div class="modal-header">
        <h5 class="modal-title" id="newAsetModalLabel">Tambah Aset Baru</h5>
        <button type="button" class="close" data-dismiss="modal" aria-label="Close">
          <span aria-hidden="true">&times;</span>
        </button>
      </div>
      <form action="<?= base_url('data'); ?>" method="post" id="formD" name="formD" >
      <div class="modal-body">
        <div class="form-group">
          
            <input type="hidden" class="form-control" id="id" name="id">
          </div>
          <div class="form-group">
            <label>Nama Aset :</label>
            <input type="text" class="form-control" id="nama_aset" name="nama_aset">
          </div>
          <div class="form-group">
            <label>Tanggal Perolehan :</label>
            <input type="date" class="form-control" id="tanggal_perolehan" name="tanggal_perolehan">
          </div>
          <div class="form-group">
            <label>Jumlah Unit :</label>
            <input type="text" class="form-control" id="jumlah_unit" name="jumlah_unit" onkeyup="OnChange(this.value)" onKeyPress="return isNumberKey(event)">
          </div>
          <div class="form-group">
            <label>Umur Manfaat (Bulan) :</label>
            <input type="text" class="form-control" id="umur_manfaat" name="umur_manfaat" onkeyup="OnChange(this.value)" onKeyPress="return isNumberKey(event)">
          </div>
          <div class="form-group">
            <label>Harga Perolehan :</label>
            <input type="text" class="form-control" id="harga_perolehan" name="harga_perolehan" onkeyup="OnChange(this.value)" onKeyPress="return isNumberKey(event)">
          </div>
          <div class="form-group">
            <label>Akumulasi Penyusutan (Perbulan) :</label>
            <input type="text" class="form-control" id="akumulasi_penyusutan" name="txtDisplay" value="" readonly="readonly">
          </div>
          
      </div>
      <div class="modal-footer">
        <button type="button" class="btn btn-secondary" data-dismiss="modal">Batal</button>
        <button type="submit" class="btn btn-primary">Tambah</button>
      </div>
      <script type="text/javascript" language="Javascript">
   harga_perolehan = document.formD.harga_perolehan.value;
   document.formD.txtDisplay.value = harga_perolehan;
   jumlah_unit = document.formD.jumlah_unit.value;
   document.formD.txtDisplay.value = jumlah_unit;
   umur_manfaat = document.formD.umur_manfaat.value;
   document.formD.txtDisplay.value = umur_manfaat;

   function OnChange(value){
     harga_perolehan = document.formD.harga_perolehan.value;
     jumlah_unit = document.formD.jumlah_unit.value;
     umur_manfaat = document.formD.umur_manfaat.value;

     akumulasi_penyusutan = harga_perolehan * jumlah_unit / umur_manfaat;
     document.formD.txtDisplay.value = akumulasi_penyusutan;
   }
 </script>
      </form>


      
 

    </div>
  </div>
</div>