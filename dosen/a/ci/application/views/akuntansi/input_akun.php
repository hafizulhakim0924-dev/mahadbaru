                <!-- Begin Page Content -->
                <div class="container-fluid">

                    <!-- Page Heading -->
                    <h1 class="h3 mb-4 text-gray-800"><?= $title; ?></h1>
                    <div class="row">
                        <div class="col-lg-12">
                            <?= form_open_multipart('akuntansi/input_akun'); ?>
                            <div class="form-group row">
                                <label for="email" class="col-sm-2 col-form-label">No. Akun</label>
                                <div class="col-sm-10">
                                  <input type="text" class="form-control" id="no_akun" name="no_akun">
                                </div>
                            </div>
                            <div class="form-group row">
                                <label for="name" class="col-sm-2 col-form-label">Nama Akun</label>
                                <div class="col-sm-10">
                                  <input type="text" class="form-control" id="nama_akun" name="nama_akun">
                                  <?= form_error('name', '<small class="text-danger pl-3">', '</small>'); ?>
                                </div>
                            </div>
                            <div class="form-group row">
                                <label for="name" class="col-sm-2 col-form-label">Kelompok Akun</label>
                                <div class="col-sm-10">
                                  <select class="form-control select2 input-lg" name="kel_akun" id="kel_akun" >
                                    <option value="Aset" >Aset</option>
                                    <option value="Liabilitas" >Liabilitas</option>
                                    <option value="Ekuitas" >Ekuitas</option>
                                    <option value="Pendapatan" >Pendapatan</option>
                                    <option value="Beban" >Beban</option>
                                </select>
                                </div>
                            </div>
                        </div>
                    </div>
                    <div class="form-group row">
                                <div class="col-sm-2"></div>
                                <div class="col-sm-10">
                                    <a href="<?= base_url('akuntansi/daftar_akun'); ?>" type="submit" class="btn btn-danger">Batal</a>
                                    <button type="submit" class="btn btn-primary">Tambah Akun</button>
                                </div>
                            </div>
                    </form>

                    

                </div>
                <!-- /.container-fluid -->

            </div>
            <!-- End of Main Content -->

