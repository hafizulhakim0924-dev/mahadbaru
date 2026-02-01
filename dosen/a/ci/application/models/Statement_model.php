<?php
/*

*/
class Statement_model extends CI_Model
{
	//USED TO FETCH TRANSACTIONS FOR GENERAL JOURNAL
    public function fetch_transasctions($date1,$date2)
    {

        $total_debit = 0;
        $total_credit = 0;
        $form_content = '';
    
        $this->db->select("mp_generalentry.id as transaction_id,mp_generalentry.date,mp_generalentry.naration");
        $this->db->from('mp_generalentry');
        $this->db->where('date >=', $date1);
        $this->db->where('date <=', $date2);
        $this->db->order_by('mp_generalentry.id','DESC');

        $query = $this->db->get();
        if ($query->num_rows() > 0)
        {
            $transaction_records =  $query->result();

            if($transaction_records  != NULL)
            {

            foreach ($transaction_records as $transaction_record) 
            {
                 $debit_amt = NULL;
                 $credit_amt = NULL;

                 $this->db->select("mp_sub_entry.*,mp_head.name");
                 $this->db->from('mp_sub_entry');
                 $this->db->join('mp_head', 'mp_head.id = mp_sub_entry.accounthead');
                 $this->db->where('mp_sub_entry.parent_id =',$transaction_record->transaction_id);
                 $sub_query = $this->db->get();
                 if ($sub_query->num_rows() > 0)
                {
                 $sub_query =  $sub_query->result();
                 if($sub_query != NULL)
                 {
                     foreach ($sub_query as $single_trans) 
                     {
                         
                         if($single_trans->type == 0)
                         {
                             $form_content .= '<tr>
                            <td>'.$transaction_record->date.'</td><td><a href="#">'. $single_trans->name.'</a></td><td>
                                <a href="#">Rp. '.number_format($single_trans->amount,0,',','.').'</a>
                            </td>
                            <td>
                                <a href="#"></a>
                            </td>          
                            </tr>';
                             
                         } 
                         else if($single_trans->type == 1)
                         {
                             $form_content .= '<tr>
                            <td >'.$transaction_record->date.'</td><td ><a class="general-journal-credit" href="#">'. $single_trans->name.'</a>
                            </td>
                            <td>
                                <a href="#"></a>
                            </td>
                            <td>
                                <a href="#">Rp. '.number_format($single_trans->amount,0,',','.').'</a>
                                
                            </td>           
                            </tr>';
                             
                         }
                    }
                }
            }
                $form_content .= '<tr class="narration" ><td class="border-bottom-journal" colspan="4"><small> <i> - '.$transaction_record->naration.'</i>
                        </small></td></tr>';
            }
        }
    }
        return $form_content;
    }  

    //USED TO GET THE LEDGER USING NATURE 
    public function get_ledger_transactions($head_id,$date1,$date2)
    {
        $this->db->select("mp_generalentry.id as transaction_id,mp_generalentry.date,mp_generalentry.naration,mp_head.name,mp_head.nature,mp_sub_entry.*");
        $this->db->from('mp_sub_entry');
        $this->db->join('mp_head', "mp_head.id = mp_sub_entry.accounthead");
        $this->db->join('mp_generalentry', 'mp_generalentry.id = mp_sub_entry.parent_id');
        $this->db->where('mp_head.id', $head_id);
        $this->db->where('mp_generalentry.date >=', $date1);
        $this->db->where('mp_generalentry.date <=', $date2);

        $query = $this->db->get();
        if ($query->num_rows() > 0)
        {
            return $query->result();
        }
        else
        {
            return NULL;
        }
    }   

    //USED TO GET THE LEDGER USING NATURE 
    public function the_ledger($date1,$date2)
    {
        $accounts_types = array('Assets','Liability','Equity','Revenue','Expense');
        $form_content = '';
        for ($i =0; $i  < count($accounts_types); $i++) 
        { 
               
            $this->db->select('mp_head.*');
            $this->db->from('mp_head');
            $this->db->where(['mp_head.nature' => $accounts_types[$i]]);
            $query = $this->db->get();
            if ($query->num_rows() > 0)
            {
                $heads_record =  $query->result();
                foreach ($heads_record as $single_head) 
                {
                    if ($this->get_ledger_transactions($single_head->id,$date1,$date2) != NULL)
                    {
                       $total_ledger = 0;
                       $ledger_query  = array();
                       $form_content .= '<hr />                                       
                    
                        <table class="table table-hover">
                        <thead>
                            <th colspan="2" class="table-active col-md-2">'.$single_head->name.'</th>
                             
                             <th class="col-md-2 table-active"></th>                
                             <th class="col-md-2 table-active"></th>
                             <th class="col-md-2 table-active"></th>
                        <thead>
                        <thead class="ledger-table-head">
                             <th class="col-md-2">TANGGAL</th>
                             <th class="col-md-4">TRANSAKSI</th>
                             <th class="col-md-2">DEBIT</th>                
                             <th class="col-md-2">KREDIT</th>
                             <th class="col-md-2">SALDO</th>
                        </thead>
                        <tbody>';
                
                    foreach ($this->get_ledger_transactions($single_head->id,$date1,$date2) as $single_ledger) 
                    {
                        $debitamount = '';
                        $creditamount = '';
                        
                        if($single_ledger->type == 0)
                        {
                            $debitamount = $single_ledger->amount;        
                            $total_ledger = $total_ledger+$debitamount;
                        }
                        else if($single_ledger->type == 1)
                        {
                            $creditamount = $single_ledger->amount;        
                            $total_ledger = $total_ledger-$creditamount;
                        }
                        else
                        {

                        }

                        


                        $form_content .= '<tr>
                        <td>'.$single_ledger->date.'</td><td><a href="#">'. $single_ledger->naration.'</a></td><td>
                            <a href="#">Rp. '.$debitamount.'</a>
                        </td>
                        <td>
                            <a href="#">Rp. '.$creditamount.'</a>
                        </td>
                        <td>Rp. '.($total_ledger < 0 ? '('.-$total_ledger.')' : $total_ledger ).'</td>            
                    </tr>';
                        }
                    }
                    $form_content .= '</tbody></table>';
                }
            }
        }
        return $form_content;
    }

    //USED TO COUNT SINGLE HEAD 
    public function count_head_amount($head_id,$date1,$date2)
    {
        $count_total_amt = 0;
        $this->db->select("mp_generalentry.id as transaction_id,mp_generalentry.date,mp_generalentry.naration,mp_sub_entry.*");
        $this->db->from('mp_sub_entry');
        $this->db->join('mp_generalentry', 'mp_generalentry.id = mp_sub_entry.parent_id');
        $this->db->where('mp_sub_entry.accounthead', $head_id);
        $this->db->where('mp_generalentry.date >=', $date1);
        $this->db->where('mp_generalentry.date <=', $date2);

        $query = $this->db->get();
        if ($query->num_rows() > 0)
        {
            $ledger_data =  $query->result();
            $count_total_amt = 0;
            if($ledger_data != NULL)
            {
                foreach ($ledger_data as $single_ledger) 
                {
                    if($single_ledger->type == 0)
                    {
                       $count_total_amt = $count_total_amt + $single_ledger->amount;
                    }
                    else 
                    {
                        $count_total_amt = $count_total_amt - $single_ledger->amount;   
                    }   
                }
            }
            
        }

        if($count_total_amt == 0)
        {
            $count_total_amt  = NULL;
        }
        else
        {
            $count_total_amt = number_format($count_total_amt,'0','.','');
        }
        
        return $count_total_amt;
        
    }
    public function count_head_amount_first($head_id,$date1,$date2)
    {
        $count_total_amt = 0;
        $this->db->select("mp_generalentry.id as transaction_id,mp_generalentry.date,mp_generalentry.naration,mp_sub_entry.*");
        $this->db->from('mp_sub_entry');
        $this->db->join('mp_generalentry', 'mp_generalentry.id = mp_sub_entry.parent_id');
        $this->db->where('mp_sub_entry.accounthead', $head_id);
        $this->db->where('mp_generalentry.date >=', $date1);
        $this->db->where('mp_generalentry.date <=', $date2);
        $this->db->where(['mp_sub_entry.journal_type' => '0']);

        $query = $this->db->get();
        if ($query->num_rows() > 0)
        {
            $ledger_data =  $query->result();
            $count_total_amt = 0;
            if($ledger_data != NULL)
            {
                foreach ($ledger_data as $single_ledger) 
                {
                    if($single_ledger->type == 0)
                    {
                       $count_total_amt = $count_total_amt + $single_ledger->amount;
                    }
                    else 
                    {
                        $count_total_amt = $count_total_amt - $single_ledger->amount;   
                    }   
                }
            }
            
        }

        if($count_total_amt == 0)
        {
            $count_total_amt  = NULL;
        }
        else
        {
            $count_total_amt = number_format($count_total_amt,'0','.','');
        }
        
        return $count_total_amt;
        
    }

    //USED TO GENERATE TRAIL BALANCE 
    public function trail_balance($current_date)
    {
        //ACCOUNTING START DATE
        $date1 = '2019-01-01';

        $date2 = $current_date;

        $total_debit  = 0;
        $total_credit = 0;
        $from_creator = '';

        $this->db->select("*");
        $this->db->from('mp_head');
        $query = $this->db->get();
        if($query->num_rows() > 0)
        {
            $ledger_data =  $query->result();
            if($ledger_data != NULL)
            {
                foreach ($ledger_data as $single_head) 
                {
                    $debitamt  = 0;
                    $creditamt = 0;
                    $amount =  $this->count_head_amount($single_head->id,$date1,$date2);

                    if($amount != NULL)
                    {
                        if($amount > 0)
                        {
                            $debitamt    = $amount;
                            $total_debit = $total_debit+$amount;
                        }
                        else
                        {
                            $creditamt    = ($amount == 0 ? $amount : -$amount);
                            $total_credit = $total_credit+$creditamt ;
                        }

                       $from_creator .= '<tr><td style=text-align:left;>'.$single_head->name.'</h4></td><td>Rp. '.$debitamt.'</td><td>Rp. '.$creditamt.'</td></tr>';

                   }
                }

                    $from_creator .= '<tr class="balancesheet-row"><td><center><b>Total</b></center></td><td>Rp. '.$total_debit.'</td><td>Rp. '.$total_credit.'</td></tr>';
            }
        }

        return  $from_creator;
    }
    public function trail_balance_first($current_date)
    {
        //ACCOUNTING START DATE
        $date1 = '2019-01-01';

        $date2 = $current_date;

        $total_debit  = 0;
        $total_credit = 0;
        $from_creator = '';

        
        
        $this->db->select('
          mp_head.*, mp_sub_entry.accounthead
        ');
        $this->db->join('mp_sub_entry', 'mp_head.id = mp_sub_entry.accounthead');
        $this->db->from('mp_head');
        $this->db->where(['mp_sub_entry.journal_type' => '0']);
        $this->db->group_by('id');
        $query = $this->db->get();

        if($query->num_rows() > 0)
        {
            $ledger_data =  $query->result();
            if($ledger_data != NULL)
            {
                foreach ($ledger_data as $single_head) 
                {
                    $debitamt  = 0;
                    $creditamt = 0;
                    $amount =  $this->count_head_amount_first($single_head->id,$date1,$date2);

                    if($amount != NULL)
                    {
                        if($amount > 0)
                        {
                            $debitamt    = $amount;
                            $total_debit = $total_debit+$amount;
                        }
                        else
                        {
                            $creditamt    = ($amount == 0 ? $amount : -$amount);
                            $total_credit = $total_credit+$creditamt ;
                        }

                       $from_creator .= '<tr><td style=text-align:left;>'.$single_head->name.'</h4></td><td>Rp. '.$debitamt.'</td><td>Rp. '.$creditamt.'</td></tr>';
                   }
                }

                    $from_creator .= '<tr class="balancesheet-row"><td><center><b>Total</b></center></td><td>Rp. '.$total_debit.'</td><td>Rp. '.$total_credit.'</td></tr>';
            }
        }

        return  $from_creator;
    }

    public function income_statement($date1,$date2)
    {
        $total_revenue = 0;
        $total_hpp = 0;
        $total_jasa = 0;
        $total_expense  = 0;
        $from_creator = '';

        $this->db->select("*");
        $this->db->from('mp_head');
        $this->db->where(['mp_head.id' => '66']);
        $query = $this->db->get();
        if($query->num_rows() > 0)
        {
            $record_data =  $query->result();
            if($record_data != NULL)
            {

                $from_creator .= '<h5 class="income-style"><b>Pendapatan</b></h5>';
                

                foreach ($record_data as $single_head) 
                {
                   
                    $amount =  $this->count_head_amount($single_head->id,$date1,$date2);
                    if( $amount != 0)
                    {

                        $amount = ($amount < 0 ? -$amount  : $amount );
                        $total_revenue = $total_revenue+$amount;
                        $from_creator .= '<tr><td>'.$single_head->name.'</td><td class="pull-right">Rp. '.number_format($amount,0,',','.').'</td></tr>'; 
                        
                    }
                }

                    
            }
        }
        $this->db->select("*");
        $this->db->from('mp_head');
        $this->db->where(['mp_head.id' => '82']);
        $query = $this->db->get();
        if($query->num_rows() > 0)
        {
            $record_data =  $query->result();
            if($record_data != NULL)
            {

                foreach ($record_data as $single_head) 
                {
                   
                    $amount =  $this->count_head_amount($single_head->id,$date1,$date2);
                    if( $amount != 0)
                    {

                        $amount = ($amount < 0 ? -$amount  : $amount );
                        $total_hpp = $total_hpp+$amount;
                        $from_creator .= '<tr><td>Harga Pokok Penjualan</td><td class="pull-right">(Rp. '.number_format($amount,0,',','.').')</td></tr>'; 
                        
                    }
                }

                    
            }
        }

        $from_creator .= '<tr><td>Total Pendapatan Penjualan </td><td class="pull-right"><b>Rp. '.number_format($total_revenue-$total_hpp,0,',','.').'</b></td></tr>';

        $this->db->select("*");
        $this->db->from('mp_head');
        $this->db->where(['mp_head.id' => '67']);
        $query = $this->db->get();
        if($query->num_rows() > 0)
        {
            $record_data =  $query->result();
            if($record_data != NULL)
            {

                foreach ($record_data as $single_head) 
                {
                   
                    $amount =  $this->count_head_amount($single_head->id,$date1,$date2);
                    if( $amount != 0)
                    {

                        $amount = ($amount < 0 ? -$amount  : $amount );
                        $total_jasa = $total_jasa+$amount;
                        $from_creator .= '<tr><td>'.$single_head->name.'</td><td class="pull-right">Rp. '.number_format($amount,0,',','.').'</td></tr>'; 
                        
                    }
                }

                    
            }
        }
        $from_creator .= '<tr><td>Total Pendapatan Bersih</td><td class="pull-right"><b>Rp. '.number_format($total_revenue-$total_hpp+$total_jasa,0,',','.').'</b></td></tr>';


        $this->db->select("*");
        $this->db->from('mp_head');
        $this->db->where(['mp_head.nature' => 'Expense']);
        $query = $this->db->get();
        if($query->num_rows() > 0)
        {
            $record_data =  $query->result();
            if($record_data != NULL)
            {
                
                $from_creator .= '<tr><td colspan="2"><h5 class="income-style"><b>Pengeluaran</b></h5></tr>';
                

                foreach ($record_data as $single_head) 
                {
                    $amount =  $this->count_head_amount($single_head->id,$date1,$date2);
                    if( $amount != 0)
                    {
                        $total_expense = $total_expense+$amount;
                        $from_creator .= '<tr><td>'.$single_head->name.'</td><td class="pull-right">Rp. '.number_format($amount,0,',','.').'</td></tr>';
                    }

                }
                    $from_creator .= '<tr><td> Total Beban </td><td class="pull-right">Rp. '.number_format($total_expense,0,',','.').'</td></tr>'; 

                    $from_creator .= '<tr class=" total-income"><td><b> Total Laba/Rugi </b></td><td class="pull-right"><b>Rp. '.number_format(($total_revenue-$total_hpp+$total_jasa)-$total_expense,0,',','.').'</b></td></tr>';
            }
        }

        return  $from_creator;
    }
    public function zakat_statement($date1,$date2)
    {
        $total_revenue = 0;
        $total_hpp = 0;
        $total_jasa = 0;
        $total_expense  = 0;
        $from_creator = '';

        $this->db->select("*");
        $this->db->from('mp_head');
        $this->db->where(['mp_head.revenue_type' => 'Penerimaan Zakat']);
        $query = $this->db->get();
        if($query->num_rows() > 0)
        {
            $record_data =  $query->result();
            if($record_data != NULL)
            {

                $from_creator .= '<h5 class="income-style"><b>Penerimaan</b></h5>';
                

                foreach ($record_data as $single_head) 
                {
                   
                    $amount =  $this->count_head_amount($single_head->id,$date1,$date2);
                    if( $amount != 0)
                    {

                        $amount = ($amount < 0 ? -$amount  : $amount );
                        $total_revenue = $total_revenue+$amount;
                        $from_creator .= '<tr><td>'.$single_head->name.'</td><td class="pull-right">Rp. '.number_format($amount,0,',','.').'</td></tr>'; 
                        
                    }
                }

                    
            }
        }
        


        $this->db->select("*");
        $this->db->from('mp_head');
        $this->db->where(['mp_head.expense_type' => 'Pengeluaran Zakat']);
        $query = $this->db->get();
        if($query->num_rows() > 0)
        {
            $record_data =  $query->result();
            if($record_data != NULL)
            {
                
                $from_creator .= '<tr><td colspan="2"><h5 class="income-style"><b>Penyaluran</b></h5></tr>';
                

                foreach ($record_data as $single_head) 
                {
                    $amount =  $this->count_head_amount($single_head->id,$date1,$date2);
                    if( $amount != 0)
                    {
                        $total_expense = $total_expense+$amount;
                        $from_creator .= '<tr><td>'.$single_head->name.'</td><td class="pull-right">Rp. '.number_format($amount,0,',','.').'</td></tr>';
                    }

                }
                    $from_creator .= '<tr><td> Total Beban </td><td class="pull-right">Rp. '.number_format($total_expense,0,',','.').'</td></tr>'; 

                    $from_creator .= '<tr class=" total-income"><td><b> Total Lebih/Kurang </b></td><td class="pull-right"><b>Rp. '.number_format(($total_revenue-$total_expense),0,',','.').'</b></td></tr>';
            }
        }

        return  $from_creator;
    }

    public function infak_statement($date1,$date2)
    {
        $total_revenue = 0;
        $total_hpp = 0;
        $total_jasa = 0;
        $total_expense  = 0;
        $from_creator = '';

        $this->db->select("*");
        $this->db->from('mp_head');
        $this->db->where(['mp_head.revenue_type' => 'Penerimaan Infak']);
        $query = $this->db->get();
        if($query->num_rows() > 0)
        {
            $record_data =  $query->result();
            if($record_data != NULL)
            {

                $from_creator .= '<h5 class="income-style"><b>Penerimaan</b></h5>';
                

                foreach ($record_data as $single_head) 
                {
                   
                    $amount =  $this->count_head_amount($single_head->id,$date1,$date2);
                    if( $amount != 0)
                    {

                        $amount = ($amount < 0 ? -$amount  : $amount );
                        $total_revenue = $total_revenue+$amount;
                        $from_creator .= '<tr><td>'.$single_head->name.'</td><td class="pull-right">Rp. '.number_format($amount,0,',','.').'</td></tr>'; 
                        
                    }
                }

                    
            }
        }
        


        $this->db->select("*");
        $this->db->from('mp_head');
        $this->db->where(['mp_head.expense_type' => 'Pengeluaran Infak']);
        $query = $this->db->get();
        if($query->num_rows() > 0)
        {
            $record_data =  $query->result();
            if($record_data != NULL)
            {
                
                $from_creator .= '<tr><td colspan="2"><h5 class="income-style"><b>Penyaluran</b></h5></tr>';
                

                foreach ($record_data as $single_head) 
                {
                    $amount =  $this->count_head_amount($single_head->id,$date1,$date2);
                    if( $amount != 0)
                    {
                        $total_expense = $total_expense+$amount;
                        $from_creator .= '<tr><td>'.$single_head->name.'</td><td class="pull-right">Rp. '.number_format($amount,0,',','.').'</td></tr>';
                    }

                }
                    $from_creator .= '<tr><td> Total Beban </td><td class="pull-right">Rp. '.number_format($total_expense,0,',','.').'</td></tr>'; 

                    $from_creator .= '<tr class=" total-income"><td><b> Total Lebih/Kurang </b></td><td class="pull-right"><b>Rp. '.number_format(($total_revenue-$total_expense),0,',','.').'</b></td></tr>';
            }
        }

        return  $from_creator;
    }

    public function amil_statement($date1,$date2)
    {
        $total_revenue = 0;
        $total_hpp = 0;
        $total_jasa = 0;
        $total_expense  = 0;
        $from_creator = '';

        $this->db->select("*");
        $this->db->from('mp_head');
        $this->db->where(['mp_head.revenue_type' => 'Penerimaan Amil']);
        $query = $this->db->get();
        if($query->num_rows() > 0)
        {
            $record_data =  $query->result();
            if($record_data != NULL)
            {

                $from_creator .= '<h5 class="income-style"><b>Penerimaan</b></h5>';
                

                foreach ($record_data as $single_head) 
                {
                   
                    $amount =  $this->count_head_amount($single_head->id,$date1,$date2);
                    if( $amount != 0)
                    {

                        $amount = ($amount < 0 ? -$amount  : $amount );
                        $total_revenue = $total_revenue+$amount;
                        $from_creator .= '<tr><td>'.$single_head->name.'</td><td class="pull-right">Rp. '.number_format($amount,0,',','.').'</td></tr>'; 
                        
                    }
                }

                    
            }
        }
        


        $this->db->select("*");
        $this->db->from('mp_head');
        $this->db->where(['mp_head.expense_type' => 'Pengeluaran Amil']);
        $query = $this->db->get();
        if($query->num_rows() > 0)
        {
            $record_data =  $query->result();
            if($record_data != NULL)
            {
                
                $from_creator .= '<tr><td colspan="2"><h5 class="income-style"><b>Penyaluran</b></h5></tr>';
                

                foreach ($record_data as $single_head) 
                {
                    $amount =  $this->count_head_amount($single_head->id,$date1,$date2);
                    if( $amount != 0)
                    {
                        $total_expense = $total_expense+$amount;
                        $from_creator .= '<tr><td>'.$single_head->name.'</td><td class="pull-right">Rp. '.number_format($amount,0,',','.').'</td></tr>';
                    }

                }
                    $from_creator .= '<tr><td> Total Beban </td><td class="pull-right">Rp. '.number_format($total_expense,0,',','.').'</td></tr>'; 

                    $from_creator .= '<tr class=" total-income"><td><b> Total Lebih/Kurang </b></td><td class="pull-right"><b>Rp. '.number_format(($total_revenue-$total_expense),0,',','.').'</b></td></tr>';
            }
        }

        return  $from_creator;
    }

    //USED TO GENERATE BALANCESHEET 
    public function balancesheet($end_date)
    {
        //ACCOUNTING START DATE
        $date1 = '2019-01-01';

        $date2 = $end_date;

        //CURRENT ASSETS
        $total_current       = 0;
        $current_assets      = '';

        $this->db->select("*");
        $this->db->from('mp_head');
        $this->db->where(['mp_head.type' => 'Lancar']);
        $this->db->where(['mp_head.nature' => 'Assets']);
        $query = $this->db->get();
        if($query->num_rows() > 0)
        {
            $ledger_data =  $query->result();
            if($ledger_data != NULL)
            {
                foreach ($ledger_data as $single_head) 
                {
                    $amount =  $this->count_head_amount($single_head->id,$date1,$date2);

                    if($amount > 0)
                    {
                        $amt = $amount;
                    }
                    else
                    {
                        $amt    = -($amount);
                    }
                     $total_current = $total_current+$amt;

                    $current_assets .= '<tr><td style=text-align:left;>'.$single_head->name.'</td>
                                <td style="text-align:right" >Rp. '.number_format($amt,0,',','.').'</td></tr>';


                }
                    $current_assets .= '<tr class="balancesheet-row"><td ><i>Total Aktiva Lancar</i></td><td style="text-align:right;" ><i>Rp. '.number_format($total_current,0,',','.').'</i></td></tr>';
            }
        }

        //NON CURRENT ASSETS
        $total_current_nc    = '';
        $noncurrent_assets   = '';
        $total_noncurrent    = 0;
        $this->db->select("*");
        $this->db->from('mp_head');
        $this->db->where(['mp_head.type' => 'Tetap']);
        $this->db->where(['mp_head.nature' => 'Assets']);
        $query = $this->db->get();
        if($query->num_rows() > 0)
        {
            $ledger_data =  $query->result();
            if($ledger_data != NULL)
            {
                foreach ($ledger_data as $single_head) 
                {
                    $amount =  $this->count_head_amount($single_head->id,$date1,$date2);

                    if($amount > 0)
                    {
                        $amt = $amount;
                    }
                    else
                    {
                        $amt    = -($amount);
                       
                    }

                    $total_noncurrent = $total_noncurrent+$amt ;

                    $noncurrent_assets .= '<tr><td>'.$single_head->name.'</td>
                                <td style="text-align:right" >Rp. '.number_format($amt,0,',','.').'</td></tr>';

                }
                    $noncurrent_assets .= '<tr class="balancesheet-row"><td><i>Total Aktiva Tetap</i></td><td style=" text-align:right;" ><i>Rp. '.number_format($total_noncurrent,0,',','.').'</i></td></tr>';
            }
        }

        $total_current_nc .= '<tr class="balancesheet-row"><td><b><i>Total Aset / Aktiva</i></b></td><td style=" text-align:right;" ><b><i>Rp. '.number_format(($total_noncurrent+$total_current),0,',','.').'</i></b></td></tr>';
       
       //CURRENT LIBILTY
        $total_cur_libility       = 0;
        $current_libility      = '';

        $this->db->select("*");
        $this->db->from('mp_head');
        $this->db->where(['mp_head.type' => 'Lancar']);
        $this->db->where(['mp_head.nature' => 'Liability']);
        $query = $this->db->get();
        if($query->num_rows() > 0)
        {
            $ledger_data =  $query->result();
            if($ledger_data != NULL)
            {
                foreach ($ledger_data as $single_head) 
                {
                    $amount =  $this->count_head_amount($single_head->id,$date1,$date2);

                    if($amount > 0)
                    {
                        $amt = $amount; 
                    }
                    else
                    {
                        $amt    = -($amount);
                    }

                    $total_cur_libility = $total_cur_libility+$amt;

                    $current_libility .= '<tr><td>'.$single_head->name.'</td>
                                <td style="text-align:right" >Rp. '.number_format($amt,0,',','.').'</td></tr>';

                }
                    $current_libility .= '<tr class="balancesheet-row"><td><i>Total Kewajiban Lancar</i></td><td style=" text-align:right;" ><i>Rp. '.number_format($total_cur_libility,0,',','.').'</i></td></tr>';
            }
        }              

        //NON CURRENT LIABILITY
        $total_current_nc_libility    = '';
        $noncurrent_libility   = '';
        $total_noncurrent_libility    = 0;
        $this->db->select("*");
        $this->db->from('mp_head');
        $this->db->where(['mp_head.type' => 'Tetap']);
        $this->db->where(['mp_head.nature' => 'Liability']);
        $query = $this->db->get();
        if($query->num_rows() > 0)
        {
            $ledger_data =  $query->result();
            if($ledger_data != NULL)
            {
                foreach ($ledger_data as $single_head) 
                {
                    $amount =  $this->count_head_amount($single_head->id,$date1,$date2);

                    if($amount > 0)
                    {
                        $amt = $amount;
                    }
                    else
                    {
                        $amt    = -($amount);
                       
                    }

                    $total_noncurrent_libility = $total_noncurrent_libility+$amt ;

                    $noncurrent_libility .= '<tr><td>'.$single_head->name.'</td>
                                <td style="text-align:right" ><i>Rp. '.number_format($amt,0,',','.').'</i></td></tr>';

                }
                    $noncurrent_libility .= '<tr class="balancesheet-row"><td>Total Kewajiban Jangka Panjang</td><td style="text-align:right;" ><i>Rp. '.number_format($total_noncurrent_libility,0,',','.').'</i></td></tr>';
            }
        }

        $total_current_nc_libility .= '<tr class="balancesheet-row"><td><i>Total Liability / Kewajiban </i></td><td style="text-align:right;" ><i>Rp. '.number_format(($total_cur_libility+$total_noncurrent_libility),0,',','.').'</i></td></tr>';
        

        //EQUITY
        $total_equity              = 0;
        $equity                    = '';
        $total_libility_and_equity = '';
        $this->db->select("*");
        $this->db->from('mp_head');
        $this->db->where(['mp_head.nature' => 'Equity']);
        $query = $this->db->get();
        if($query->num_rows() > 0)
        {
            $ledger_data =  $query->result();
            if($ledger_data != NULL)
            {
                foreach ($ledger_data as $single_head) 
                {
                    $amount =  $this->count_head_amount($single_head->id,$date1,$date2);

                    if($amount > 0)
                    {
                        $amt = $amount; 
                    }
                    else
                    {
                        $amt    = -($amount);
                    }

                    $total_equity = $total_equity+$amt;

                    $equity .= '<tr><td><i>'.$single_head->name.'</i></td>
                                <td style="text-align:right" ><i>Rp. '.number_format($amt,0,',','.').'</i></td></tr>';
                }   
            }
        }

        $retained_earnings = $this->retained_earnings($date1,$date2);
        $total_libility_equity_retained = $total_equity+$total_cur_libility+$total_noncurrent_libility+$retained_earnings;

         $equity .= '<tr><td> Dana Ditahan </td>
                                <td style="text-align:right" ><i>Rp. '.number_format($retained_earnings,0,',','.').'</i></td></tr>';
         
         $total_libility_and_equity .= '<tr class="balancesheet-row"><td ><b><i>Total Kewajiban and Modal</i></b></td><td style=" text-align:right;" ><b><i>Rp. '.number_format($total_libility_equity_retained,0,',','.').'</i></b></td></tr>';

         return  array('current_assets'=>$current_assets,'noncurrent_assets'=>$noncurrent_assets,'total_assets'=>$total_current_nc,'current_libility'=>$current_libility,'noncurrent_libility'=>$noncurrent_libility,'total_currentnoncurrent_libility'=>$total_current_nc_libility,'equity'=>$equity,'total_libility_equity'=>$total_libility_and_equity);
    }


    //USED TO CREATE A CHART OF ACCOUNTS LIST 
    public function chart_list()
    {
        $accounts_list = '';
        $accounts_nature  = array('Assets','Liability','Equity','Revenue','Expense','HPP');
        for ($i = 0; $i < count($accounts_nature); $i++) 
        {
            $accounts_list .= '<option value="0">-------------</option>';  
            $accounts_list .='<optgroup label="'.$accounts_nature[$i].'">';

            $this->db->select("*");
            $this->db->from('mp_head');
            $this->db->where(['mp_head.nature' => $accounts_nature[$i]]);
            $query = $this->db->get();
            if($query->num_rows() > 0)
            {
                $result =  $query->result();
                if($result != NULL)
                {
                    foreach ($result as $single_head) 
                    {
                        $accounts_list .= '<option value="'.$single_head->id.'">'.$single_head->name.'</option>';   
                    }            
                }
            }

            $accounts_list .= ' </optgroup>';      
        }
        return $accounts_list;
    }

    //USED TO CALCULATE RETAINED EARNINGS 
    public function retained_earning($start,$end)
    {
        $total_expense = 0;
        $total_revenue = 0;

        $this->db->select("*");
        $this->db->from('mp_head');
        $this->db->where(['mp_head.nature' => 'Revenue']);
        $query = $this->db->get();
        if($query->num_rows() > 0)
        {
            $result =  $query->result();
            if($result != NULL)
            {
                foreach ($result as $single_head) 
                {
                   
                    $amount =  $this->count_head_amount($single_head->id,$start,$end);

                    $total_revenue = $total_revenue + $amount;
                }       
            }
        }

       $total_revenue = ($total_revenue < 0 ? -$total_revenue: $total_revenue);

       return $total_revenue;  
    }
    public function retained_earnings($start,$end)
    {
        $total_expense = 0;
        $total_revenue = 0;

        $this->db->select("*");
        $this->db->from('mp_head');
        $this->db->where(['mp_head.nature' => 'Revenue']);
        $query = $this->db->get();
        if($query->num_rows() > 0)
        {
            $result =  $query->result();
            if($result != NULL)
            {
                foreach ($result as $single_head) 
                {
                   
                    $amount =  $this->count_head_amount($single_head->id,$start,$end);

                    $total_revenue = $total_revenue + $amount;
                }       
            }
        }

       $total_revenue = ($total_revenue < 0 ? -$total_revenue: $total_revenue);

        $this->db->select("*");
        $this->db->from('mp_head');
        $this->db->where(['mp_head.nature' => 'Expense']);
        $query = $this->db->get();
        if($query->num_rows() > 0)
        {
            $result =  $query->result();
            if($result != NULL)
            {
                foreach ($result as $single_head) 
                {
                   
                    $amount =  $this->count_head_amount($single_head->id,$start,$end);

                    $total_expense = $total_expense + $amount;
                }       
            }
        }  

         $total_expense = ($total_expense < 0 ? -$total_expense: $total_expense);

       return $total_revenue-$total_expense;  
    } 

    //COUNT HEAD AMOUNT USING HEAD ID 
    function count_head_amount_by_id($head_id)
    {
        $count = 0; 
        $this->db->select("*");
        $this->db->from('mp_sub_entry');
        $this->db->where(['mp_sub_entry.accounthead' => $head_id]); 

        $query = $this->db->get();
        if($query->num_rows() > 0)
        {
            $result =  $query->result();
            if($result != NULL)
            {
                foreach ($result as $single_head) 
                { 
                    if($single_head->type == 0)
                    {
                        $count = $count + $single_head->amount;
                    }
                    else if($single_head->type == 1)
                    {
                        $count = $count - $single_head->amount;
                    }
                    else
                    {

                    }
                }
            }
        }
        return  $count;          
    }
}