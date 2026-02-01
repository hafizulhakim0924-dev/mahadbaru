<!-- Begin Page Content -->
<div class="container-fluid">
    <!-- Page Heading -->
    <div class="d-sm-flex justify-content-between mb-4">
        <h1 class="h3 mb-0 text-gray-800"><?= $title; ?></h1>
        <div class="d-sm-flex align-items-center">
            <a href="<?= base_url('statements/opening_balance') ?>" class="d-none d-sm-inline-block btn btn-sm btn-primary shadow-sm mr-2"><i class="fas fa-plus fa-sm"></i> Buka Saldo</a>
            
            <button type="button" class="d-none d-sm-inline-block btn btn-sm btn-primary shadow-sm"  onclick="show_modal_page('<?php echo base_url().'accounts/popup/add_chart_of_accounts'; ?>')" ><i class="fas fa-plus fa-sm" aria-hidden="true">  </i>   Buat Akun
                </button>
        </div>    
    </div>

    <!-- TABEL DATA -->
    <div class="card shadow mb-4">
        <div class="card-body">
            <div class="table-responsive">
                <table class="table table-bordered" id="dataTable" width="100%" cellspacing="0">
                    <thead>
                        <tr>
                            <th>Nama Akun</th>
                            <th>Kelompok</th>
                            <th>Tipe</th>
                            <th>Tindakan</th>
                        </tr>
                    </thead>
                    <tbody>
                                <?php
                                    if($chart_list != NULL)
                                    {
                                    foreach ($chart_list as $single_account)
                                    {
                                ?>
                                    <tr>
                                        <td style="text-align: left;">
                                            <?php echo $single_account->name; ?>
                                        </td>
                                        <td>
                                            <?php echo $single_account->nature; ?>
                                        </td>
                                        <td>
                                            <?php echo $single_account->type; ?>
                                        </td>
                                        
                                        <td>
                                            
                                            <a onclick="show_modal_page('<?php echo base_url().'accounts/popup/edit_chart_of_accounts/'.$single_account->id; ?>')" class="btn btn-warning btn-icon-split pr-2 pl-2 mr-2">Edit</a>

                                            <a href="<?php echo base_url().'accounts/delete/'.$single_account->id; ?>" class="btn btn-danger btn-icon-split pr-2 pl-2 mr-2">Hapus</a>
                                        </td>
                                    </tr>
                                    <?php
                                            }
                                        }
                                     ?>
                                </tbody>
                </table>
            </div>
        </div>
    </div>

<!-- /.container-fluid -->

</div>
<!-- End of Main Content -->
 
