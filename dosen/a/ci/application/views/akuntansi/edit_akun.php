                <!-- Begin Page Content -->
                <div class="container-fluid">

                    <!-- Page Heading -->
                    <h1 class="h3 mb-4 text-gray-800"><?= $title; ?></h1>
                    <div class="card-body">
                    <form action="" method="post">
                        <input type="hidden" name="id" value="<?= $akun['id']; ?>">
                        <div class="form-group">
                            <label for="nama">No. Akun</label>
                            <input type="text" name="no_akun" class="form-control" id="no_akun" value="<?= $akun['no_akun']; ?>">
                            <small class="form-text text-danger"><?= form_error('no_akun'); ?></small>
                        </div>
                        <div class="form-group">
                            <label for="nrp">Nama Akun</label>
                            <input type="text" name="nama_akun" class="form-control" id="nama_akun" value="<?= $akun['nama_akun']; ?>">
                            <small class="form-text text-danger"><?= form_error('nama_akun'); ?></small>
                        </div>
                        <div class="form-group">
                            <label for="jurusan">Kelompok Akun</label>
                            <select class="form-control" id="kel_akun" name="kel_akun">
                                <?php foreach( $kel_akun as $k ) : ?>
                                    <?php if( $k == $akun['kel_akun'] ) : ?>
                                        <option value="<?= $k; ?>" selected><?= $k; ?></option>
                                    <?php else : ?>
                                        <option value="<?= $k; ?>"><?= $k; ?></option>
                                    <?php endif; ?>
                                <?php endforeach; ?>
                            </select>
                        </div>
                        <button type="submit" name="ubah" class="btn btn-primary float-right">Ubah Data</button>
                    </form>
                </div>
                    

                </div>
                <!-- /.container-fluid -->

            </div>
            <!-- End of Main Content -->

