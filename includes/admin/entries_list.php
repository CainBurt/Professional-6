<style>
  #sr_no { width:5% !important}
  .block_btn
  {
    background: red;
    color: white;
    padding: 5px 10px;
    font-size: 11px;
    border-radius: 10px;
  }
  .block_btn:hover, .unblock_btn:hover{cursor:pointer; color:black;}
  .unblock_btn
  {
    background: green;
    color: white;
    padding: 5px 4px;
    border-radius: 10px;
    font-size: 11px;
  } 
  .fixed .column-date
  {
    width:auto !important;
  }
</style>
<?php
class Entries_list extends WP_List_Table {
    
    /** ************************************************************************
     * Normally we would be querying data from a database and manipulating that
     * for use in your list table. For this example, we're going to simplify it
     * slightly and create a pre-built array. Think of this as the data that might
     * be returned by $wpdb->query()
     * 
     * In a real-world scenario, you would make your own custom query inside
     * this class' prepare_items() method.
     * 
     * @var array 
     **************************************************************************/
    var $example_data = array();
    public $result_array = array();
    public $counter = 0;
    /** ************************************************************************
     * REQUIRED. Set up a constructor that references the parent constructor. We 
     * use the parent reference to set some default configs.
     ***************************************************************************/
    function __construct(){
        global $status, $page;
                
        //Set parent defaults
        parent::__construct( array(
            'singular'  => 'entry',     //singular name of the listed records
            'plural'    => 'entries',    //plural name of the listed records
            'ajax'      => false        //does this table support ajax?
        ) );
        $this->example_data = $this->get_table_data();
    }

    // get data from db
	public function get_table_data()
	{
		global $wpdb; 
		$table = $wpdb->prefix.'posts';
        $condition = array();
        $condition_eventDate = array();
        $condition2 = array();
        $condition_sub_event = array();
        $date_range = array();
        $s_event_date = '';
        if(isset($_GET['search_by_event']) && $_GET['search_by_event'] != '')
        {
            //$event_title_array = explode("_",$_GET['search_by_event']);
            //$event_title = $event_title_array[0];
            //$s_event_date = $event_title_array[1];
            //$where = " AND (pm.meta_key = 'activity' and pm.meta_value = '".$_POST['search_by_event']."')"; //echo $_POST['search_by_event'];
            $condition = array(
                'key' => 'main_event_type',
                'value' => $_GET['search_by_event'],
            );
        }
        if(isset($_GET['search_by_sub_event']) && $_GET['search_by_sub_event'] != '')
        {
            $condition_sub_event = array(
                'key' => 'activity',
                'value' => $_GET['search_by_sub_event'],
            );
        }
        if(isset($_GET['search_by_event_date']) && $_GET['search_by_event_date'] != '')
        {
            $condition_eventDate = array(
                'key' => 'date',    // event_date
                'value' => $_GET['search_by_event_date'],
            );
        }
        if(isset($s_event_date) && $s_event_date != '')
        {
            $condition_eventDate = array(
                'key' => 'event_date',
                'value' => $s_event_date,
            );
        }
        //if(isset($_GET['search_by_date']) && $_GET['search_by_date'] != '')
        if(isset($_GET['search_start_date']) && $_GET['search_start_date'] != '' && isset($_GET['search_end_date']) && $_GET['search_end_date'] != '')
        {
            $month = date("m", strtotime($_GET['search_by_date'])); //$_POST['search_by_date'];
            $start_date = $_GET['search_start_date']; //date('Y'.$month.'01'); // First day of the month
            $end_date = $_GET['search_end_date']; //date('Y'.$month.'t'); // 't' gets the last day of the month
            $date_range = array(
                                'after'     => date('F d, Y 00:00:00', strtotime($start_date)),
                                'before'    => date('F d, Y 23:59:00', strtotime($end_date)),
                                'inclusive' => true,
                            );
            
        }
        $condition3 = array();
        if(isset($_GET['search_text']) && $_GET['search_text'] != '')
        {
            
            $search_condition =   array(
                                    'relation' =>'OR',
                                    array(
                                        'key' => 'customer-name',     // customer-email
                                        'value' => $_GET['search_text'],
                                        'compare' => 'LIKE'
                                    ), 
                                    array(
                                        'key' => 'customer-email',     // customer-email
                                        'value' => $_GET['search_text'],
                                        'compare' => 'LIKE'
                                    ),
                                );
            
        }
		
        $meta_query = array(
                $condition,
                'relation' =>'AND',
                $condition_sub_event,
                'relation' =>'AND',
                $search_condition,
                'relation' =>'AND',
                $condition_eventDate
              );   
        $args = array(  
            'post_type' => 'wpcf7_customData',
            'suppress_filters' => true,
            'numberposts'   => -1,
            'order'         => 'DESC',
            'orderby' => 'post_date',
            'date_query' => array($date_range),
            'meta_query' =>  $meta_query
        );
        $posts = new WP_Query( $args );
        //echo '<pre>'; print_r($args); echo '</pre>'; //   print_r($posts->posts); exit;
       
        $this->result_array = $posts->posts;
       return $posts->posts; //$results;
	}
    /** ************************************************************************
     * Recommended. This method is called when the parent class can't find a method
     * specifically build for a given column. Generally, it's recommended to include
     * one method for each column you want to render, keeping your package class
     * neat and organized. For example, if the class needs to process a column
     * named 'title', it would first see if a method named $this->column_title() 
     * exists - if it does, that method will be used. If it doesn't, this one will
     * be used. Generally, you should try to use custom column methods as much as 
     * possible. 
     * 
     * Since we have defined a column_title() method later on, this method doesn't
     * need to concern itself with any column with a name of 'title'. Instead, it
     * needs to handle everything else.
     * 
     * For more detailed insight into how columns are handled, take a look at 
     * WP_List_Table::single_row_columns()
     * 
     * @param array $item A singular item (one full row's worth of data)
     * @param array $column_name The name/slug of the column to be processed
     * @return string Text or HTML to be placed inside the column <td>
     **************************************************************************/
    function column_default($item, $column_name){
                 
		switch($column_name){           
            case 'sr_no':
			    return '<input name="delete_columns[]" type="checkbox" value="'.$item->ID.'" />';
			case 'name':
                $from_name = get_post_meta($item->ID, 'customer-name', true);
				return $from_name;
			case 'email':
                $from_email = get_post_meta($item->ID, 'customer-email', true);
				return $from_email;
            /*case 'number';
                $number = get_post_meta($item->ID, 'customer-number', true);  
                return $number;  */
            case 'main_event_type':  
                $main_event_type = get_post_meta($item->ID, 'main_event_type', true);
                return  $main_event_type;        
			case 'activity':  
                $activity = get_post_meta($item->ID, 'activity', true);
                return  $activity;   
            case 'event_date':  
                $event_date = get_post_meta($item->ID, 'date', true);
                if($event_date != '')
                    return  date('d/m/Y', strtotime($event_date));
                else
                    return '';      
            case 'event_time':  
                $event_time = get_post_meta($item->ID, 'time', true);
                return  $event_time;              
            case 'web_address':  
                $web_address = get_post_meta($item->ID, 'web-address', true);
                 return  $web_address != '' ? $web_address : '-';
            case 'linkedin':  
                $linkedin = get_post_meta($item->ID, 'linkedin', true);
                return  $linkedin; //(isset($age[0]) ? $age[0] : $age);    
            case 'shirt_size':  
                $shirt = get_post_meta($item->ID, 'shirt-size', true);
                return  $shirt != '' ? $shirt : '-';
            case 'address':  
                $address = get_post_meta($item->ID, 'address', true);
                return  $address;  
            case 'sequence_generator':  
                $address = get_post_meta($item->ID, 'sequence-generator', true);
                return  $address;
            case 'choice':
                $choice = get_post_meta($item->ID, 'tshirt_choice', true);
                return $choice;
            case 'played':  
                $is_user_played = get_post_meta($item->ID, 'is_user_played', true);
                return  '<select id="cd_user_played_'.$item->ID.'" onChange="update_user_played(this.value, '.$item->ID.')">
                            <option value="0" '.($is_user_played == 0 ? 'selected' : '').'>No</option>            
                            <option value="1" '.($is_user_played == 1 ? 'selected' : '').'>Yes</option>
                          </select>';      
            case 'date':
				return date('d/m/Y g:i A', strtotime($item->post_date));	
			/*case 'status':
                $status = get_post_meta($item->ID, 'user_status', true);
				return $status;	
            case 'block':
                // check user blocked or not
                $is_user_block = get_post_meta($item->ID, 'is_user_block', true);
                $user_email = get_post_meta($item->ID, 'customer-email', true);
                $event = get_post_meta($item->ID, 'activity', true);
                if($is_user_block == 1)
                $button = '<a href="'.admin_url( '/admin.php?page=wpcf7_entries&block=0&email='.$user_email.'&event='.$event).'" class="btn unblock_btn"> Unblock </a>';
                else
                    $button = '<a href="'.admin_url( '/admin.php?page=wpcf7_entries&block=1&email='.$user_email.'&event='.$event).'" class="btn block_btn"> Block </a>'; //'<button onclick="location.href=\''.admin_url( '/admin.php?page=wpcf7_entries&block=1&email='.$user_email.'&event='.$event).'\'" class="btn block_btn">Block</button>';   
                return $button;  */	
			default:
                return '';//Show the whole array for troubleshooting purposes
        }
    }


    /** ************************************************************************
     * Recommended. This is a custom column method and is responsible for what
     * is rendered in any column with a name/slug of 'title'. Every time the class
     * needs to render a column, it first looks for a method named 
     * column_{$column_title} - if it exists, that method is run. If it doesn't
     * exist, column_default() is called instead.
     * 
     * This example also illustrates how to implement rollover actions. Actions
     * should be an associative array formatted as 'slug'=>'link html' - and you
     * will need to generate the URLs yourself. You could even ensure the links
     * 
     * 
     * @see WP_List_Table::::single_row_columns()
     * @param array $item A singular item (one full row's worth of data)
     * @return string Text to be placed inside the column <td> (movie title only)
     **************************************************************************/
    


    /** ************************************************************************
     * REQUIRED if displaying checkboxes or using bulk actions! The 'cb' column
     * is given special treatment when columns are processed. It ALWAYS needs to
     * have it's own method.
     * 
     * @see WP_List_Table::::single_row_columns()
     * @param array $item A singular item (one full row's worth of data)
     * @return string Text to be placed inside the column <td> (movie title only)
     **************************************************************************/
    function column_cb($item){
        return sprintf(
           //'<input type="checkbox" name="%1$s[]" value="%2$s" />',
            /*$1%s*/ $this->_args['singular'],  //Let's simply repurpose the table's singular label ("movie")
            /*$2%s*/ $item['ID']                //The value of the checkbox should be the record's id
        );
    }


    /** ************************************************************************
     * REQUIRED! This method dictates the table's columns and titles. This should
     * return an array where the key is the column slug (and class) and the value 
     * is the column's title text. If you need a checkbox for bulk actions, refer
     * to the $columns array below.
     * 
     * The 'cb' column is treated differently than the rest. If including a checkbox
     * column in your table you must create a column_cb() method. If you don't need
     * bulk actions or checkboxes, simply leave the 'cb' entry out of your array.
     * 
     * @see WP_List_Table::::single_row_columns()
     * @return array An associative array containing column information: 'slugs'=>'Visible Titles'
     **************************************************************************/
    function get_columns(){ 
        
	    $columns = array(
            'sr_no' => '<input  type="checkbox" id="checkAll"/>', //Render a checkbox instead of text
            'name'  => 'Name',
			'email'  => 'Email',
			//'number'  => 'Number',
            'main_event_type'  => 'Event',
            'activity'  => 'Sub Event',
            'event_date'  => 'Event Date',
            'event_time'  => 'Event Time',
            
            // 'gender'  => 'Gender',
            // 'age' => 'Age',
            'web_address' => 'Website Address',
            'linkedin' => 'Linkedin Profile',
            'shirt_size' => 'Shirt Size',
            'address' => 'Delivery Address',
            'sequence_generator' => 'Code',
            'choice' => 'Print/Plant',
            'played' => 'Attended',
            'date'     => 'Submission Date',
            //'status' => 'Status',
           // 'block' => 'Block / Unblock'
        );   
        return $columns;
    }


    /** ************************************************************************
     * Optional. If you want one or more columns to be sortable (ASC/DESC toggle), 
     * you will need to register it here. This should return an array where the 
     * key is the column that needs to be sortable, and the value is db column to 
     * sort by. Often, the key and value will be the same, but this is not always
     * the case (as the value is a column name from the database, not the list table).
     * 
     * This method merely defines which columns should be sortable and makes them
     * clickable - it does not handle the actual sorting. You still need to detect
     * the ORDERBY and ORDER querystring variables within prepare_items() and sort
     * your data accordingly (usually by modifying your query).
     * 
     * @return array An associative array containing all the columns that should be sortable: 'slugs'=>array('data_values',bool)
     **************************************************************************/
    function get_sortable_columns() {
        $sortable_columns = array(
            'name'     => array('name',true),     //true means it's already sorted
            'email'    => array('email',true),
            'number'  => array('number',true),
            'status'  => array('status',true),
           
		);
        return $sortable_columns;
    }


    /** ************************************************************************
     * Optional. If you need to include bulk actions in your list table, this is
     * the place to define them. Bulk actions are an associative array in the format
     * 'slug'=>'Visible Title'
     * 
     * If this method returns an empty value, no bulk action will be rendered. If
     * you specify any bulk actions, the bulk actions box will be rendered with
     * the table automatically on display().
     * 
     * Also note that list tables are not automatically wrapped in <form> elements,
     * so you will need to create those manually in order for bulk actions to function.
     * 
     * @return array An associative array containing all the bulk actions: 'slugs'=>'Visible Titles'
     **************************************************************************/
    function get_bulk_actions() {
        $actions = array(
            'delete'    => 'Delete'
        );
        return $actions;
    }


    /** ************************************************************************
     * Optional. You can handle your bulk actions anywhere or anyhow you prefer.
     * For this example package, we will handle it in the class to keep things
     * clean and organized.
     * 
     * @see $this->prepare_items()
     **************************************************************************/
    function process_bulk_action() {
        
        //Detect when a bulk action is being triggered...
        if( 'delete'=== $this->current_action() ) {
           
            if(!empty($_GET['delete_columns']))
            {
                foreach($_GET['delete_columns'] as $rec)
                {
                    global $wpdb; 
                    wp_delete_post( $rec, true);
                    $sql = "Delete from  $wpdb->postmeta where post_id = ".$rec." "; 
                    $wpdb->query($sql);
                     
                }
            }
            $_SESSION['success_msg'] = 'Records deleted.';
            echo "<script type='text/javascript'> location.href='".admin_url( '/admin.php?page=wpcf7_entries' )."';</script>";
           //wp_redirect( admin_url( '/admin.php?page=wpcf7_entries' ) );
        }
        
    }

    //Create CSV file
    function array_csv_download( $filename = "export.csv", $delimiter="," )
    {
        $array = $this->result_array;
        //ob_end_clean();
        $uploads = wp_upload_dir(); 
        $file_path = $uploads['basedir'].'/csv_files/';
        //$handle = fopen( 'php://output', 'w' );
        $file = 'Event_entries.csv';
        $handle = fopen( $file_path.$file, 'w' );
       // echo '<pre>'; print_r($array); exit;
        // use keys as column titles
        $array_keys = array('Name', 
                            'Email', 
                            'Event',
                            'Sub Event',
                            'Event Date',
                            'Event Time',
                            // 'Gender',
                            // 'Age',
                            'Website Address',
                            'Phone Number',
                            'Shirt Size',
                            'Address',
                            'Code',
                            'Print/Plant',
                            'Attended');
                            // 'Submission Date');
        
        fputcsv( $handle, $array_keys, $delimiter );

        foreach ( $array as $value ) 
        {
            $array_data = array();
            
           // echo '<pre>'; print_r($array_data); exit;
            $from_name = get_post_meta($value->ID, 'customer-name', true);  
            $from_email = get_post_meta($value->ID, 'customer-email', true);   
            //$number = get_post_meta($value->ID, 'customer-number', true);
            $main_event_type = get_post_meta($value->ID, 'main_event_type', true);
            $activity = get_post_meta($value->ID, 'activity', true);  
            $event_date = get_post_meta($value->ID, 'date', true); 
            $event_time = get_post_meta($value->ID, 'time', true); 
            
            // $gender = get_post_meta($value->ID, 'gender', true); 
            // $age = get_post_meta($value->ID, 'age', true);
            $web_address = get_post_meta($value->ID, 'web-address', true); 
            $linkedin = get_post_meta($value->ID, 'linkedin', true); 
            $shirt = get_post_meta($value->ID, 'shirt-size', true); 
            $address = get_post_meta($value->ID, 'address', true); 

            //$join = get_post_meta($value->ID, 'your-join', true);  
            //$status = get_post_meta($value->ID, 'user_status', true);   
            //$bra_size = get_post_meta($value->ID, 'bra-size', true);
            $sequence_generator = get_post_meta($value->ID, 'sequence-generator', true);  
            $choice = get_post_meta($value->ID, 'tshirt_choice', true);
            $is_user_played = get_post_meta($value->ID, 'is_user_played', true);
            
            $array_data[] = $from_name;
            $array_data[] = $from_email;
            $array_data[] = $main_event_type;
            $array_data[] = $activity;
            $array_data[] = ($event_date != '' ? date('d/m/Y', strtotime($event_date)) : '');
            $array_data[] = $event_time;
            
            //$array_data[] = $number; 
            //$array_data[] = $join;
            // $array_data[] = $gender;
            // $array_data[] = $age;
            $array_data[] = $web_address;
            $array_data[] = $linkedin;
            $array_data[] = $shirt;
            $array_data[] = $address;
            $array_data[] = $sequence_generator;
            $array_data[] = $choice;
            //$array_data[] = $bra_size;  
            $array_data[] = $is_user_played == 1 ? 'Yes' : 'No';
            // $array_data[] = date('d/m/Y g:i A', strtotime($value->post_date));
            fputcsv( $handle, $array_data, $delimiter);
        }
        fclose( $handle );
        // Download
        $url = $uploads['baseurl'].'/csv_files/'.$file;
        $url = set_url_scheme($url, 'https');
        
        return $url;
        
    }
    /** ************************************************************************
     * REQUIRED! This is where you prepare your data for display. This method will
     * usually be used to query the database, sort and filter the data, and generally
     * get it ready to be displayed. At a minimum, we should set $this->items and
     * $this->set_pagination_args(), although the following properties and methods
     * are frequently interacted with here...
     * 
     * @global WPDB $wpdb
     * @uses $this->_column_headers
     * @uses $this->items
     * @uses $this->get_columns()
     * @uses $this->get_sortable_columns()
     * @uses $this->get_pagenum()
     * @uses $this->set_pagination_args()
     **************************************************************************/
    function prepare_items() {
        global $wpdb; //This is used only if making any database queries
         /**
         * Optional. You can handle your bulk actions however you see fit. In this
         * case, we'll handle them within our package just to keep things clean.
         */
        $this->process_bulk_action();
        /**
         * First, lets decide how many records per page to show
         */
        $per_page = 20;
        
        
        /**
         * REQUIRED. Now we need to define our column headers. This includes a complete
         * array of columns to be displayed (slugs & titles), a list of columns
         * to keep hidden, and a list of columns that are sortable. Each of these
         * can be defined in another method (as we've done here) before being
         * used to build the value for our _column_headers property.
         */
        $columns = $this->get_columns();
        $hidden = array('ID');
        $sortable = $this->get_sortable_columns();
        
        
        /**
         * REQUIRED. Finally, we build an array to be used by the class for column 
         * headers. The $this->_column_headers property takes an array which contains
         * 3 other arrays. One for all columns, one for hidden columns, and one
         * for sortable columns.
         */
        $this->_column_headers = array($columns, $hidden, $sortable);
        
        
       
        
        
        /**
         * Instead of querying a database, we're going to fetch the example data
         * property we created for use in this plugin. This makes this example 
         * package slightly different than one you might build on your own. In 
         * this example, we'll be using array manipulation to sort and paginate 
         * our data. In a real-world implementation, you will probably want to 
         * use sort and pagination data to build a custom query instead, as you'll
         * be able to use your precisely-queried data immediately.
         */
        $data = $this->example_data;
                
        
        /**
         * This checks for sorting input and sorts the data in our array accordingly.
         * 
         * In a real-world situation involving a database, you would probably want 
         * to handle sorting by passing the 'orderby' and 'order' values directly 
         * to a custom query. The returned data will be pre-sorted, and this array
         * sorting technique would be unnecessary.
         */
        function usort_reorder($a,$b){
            $orderby = (!empty($_REQUEST['orderby'])) ? $_REQUEST['orderby'] : 'title'; //If no sort, default to title
            $order = (!empty($_REQUEST['order'])) ? $_REQUEST['order'] : 'asc'; //If no order, default to asc
            $result = strcmp($a[$orderby], $b[$orderby]); //Determine sort order
            return ($order==='asc') ? $result : -$result; //Send final sort direction to usort
        }
       // usort($data, 'usort_reorder');
        
        
        /***********************************************************************
         * ---------------------------------------------------------------------
         * vvvvvvvvvvvvvvvvvvvvvvvvvvvvvvvvvvvvvvvvvvvvvvvvvvvvvvvvvvvvvvvvvvvvv
         * 
         * In a real-world situation, this is where you would place your query.
         *
         * For information on making queries in WordPress, see this Codex entry:
         * http://codex.wordpress.org/Class_Reference/wpdb
         * 
         * ^^^^^^^^^^^^^^^^^^^^^^^^^^^^^^^^^^^^^^^^^^^^^^^^^^^^^^^^^^^^^^^^^^^^^
         * ---------------------------------------------------------------------
         **********************************************************************/
        
                
        /**
         * REQUIRED for pagination. Let's figure out what page the user is currently 
         * looking at. We'll need this later, so you should always include it in 
         * your own package classes.
         */
        $current_page = $this->get_pagenum();
        
        /**
         * REQUIRED for pagination. Let's check how many items are in our data array. 
         * In real-world use, this would be the total number of items in your database, 
         * without filtering. We'll need this later, so you should always include it 
         * in your own package classes.
         */
        $total_items = count($data);
        
        
        /**
         * The WP_List_Table class does not handle pagination for us, so we need
         * to ensure that the data is trimmed to only the current page. We can use
         * array_slice() to 
         */
        $data = array_slice($data,(($current_page-1)*$per_page),$per_page);
        
        
        
        /**
         * REQUIRED. Now we can add our *sorted* data to the items property, where 
         * it can be used by the rest of the class.
         */
        $this->items = $data;
        
        
        /**
         * REQUIRED. We also have to register our pagination options & calculations.
         */
        $this->set_pagination_args( array(
            'total_items' => $total_items,                  //WE have to calculate the total number of items
            'per_page'    => $per_page,                     //WE have to determine how many items to show on a page
            'total_pages' => ceil($total_items/$per_page)   //WE have to calculate the total number of pages
        ) );
    }


}
?>