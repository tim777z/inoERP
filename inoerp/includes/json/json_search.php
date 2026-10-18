<?php include_once("../basics/basics.inc"); ?>
<?php

if (!empty($_GET['class_name'])) {

    $class = $class_names = $_GET['class_name'];
    $$class = new $class;
    $table_name = empty($table_name) ? $class::$table_name : $table_name;
    $primary_column = property_exists($$class, 'primary_column') ? $class::$primary_column : $table_name . '_id';

    if (!(empty($_GET['pageno']))) {
        $pageno = is_array($_GET['pageno']) ? (int) $_GET['pageno'][0] : (int) $_GET['pageno'];
    } else {
        $pageno = 1;
    }

    if (!(empty($_GET['per_page']))) {
        $per_page = is_array($_GET['per_page']) ? (int) $_GET['per_page'][0] : (int) $_GET['per_page'];
    } else {
        $per_page = null;
    }

    if (!empty($_GET['search_parameters'])) {
        $_GET = get_postArray_From_jqSearializedArray($_GET['search_parameters']);
    }

    $_GET['pageno'] = $pageno;
    $_GET['class_name'] = $class;
    $_GET['per_page'] = !empty($per_page) ? $per_page : $_GET['per_page'];

    if (!empty($_GET['per_page'])) {
        if (!empty($_GET['per_page']) && ($_GET['per_page'] == "all" || $_GET['per_page'][0] == "all")) {
            $per_page = 50000;
        } else if (!empty($_GET['per_page'])) {
            $per_page = (is_array($_GET['per_page'])) ? $_GET['per_page'][0] : $_GET['per_page'];
        }
        $per_page = empty($per_page) ? 10 : $per_page;
    }

    $search_order_by = !(empty($_GET['search_order_by'])) ? $_GET['search_order_by'][0] : '';
    $search_asc_desc = !(empty($_GET['search_asc_desc'])) ? $_GET['search_asc_desc'][0] : '';
    echo '<div id="json_search_result">';

    //start search
    $search_array = $$class->field_a;
    global $search_result_statement;

    //pre populate
    if (method_exists($$class, 'search_pre_populate')) {
        $ppl = call_user_func(array($$class, 'search_pre_populate'));
        if (!empty($ppl)) {
            $search_pre_populate_a = [];
            foreach ($ppl as $search_key => $search_val) {
                array_push($search_pre_populate_a, $search_val);
            }
            $_GET[$search_key] = $search_pre_populate_a;
        }
    }

    //column details
    if (empty($_GET['column_array'][0])) {
        if (property_exists($class, 'column')) {
            $column_array = $$class->column;
        } else {
            $column_array = $$class->field_a;
        }
    } else {
        $column_array = unserialize(base64_decode($_GET['column_array'][0]));
    }
    if (!empty($_GET['new_column'][0])) {
        $new_column = $_GET['new_column'][0];
        if (!empty($new_column)) {
            foreach ($new_column as $key => $value) {
                if ((!(is_array($value))) && (!empty($value))) {
                    array_push($column_array, $value);
                }
            }
        }
    }

    // Build secure WHERE clause using parameterized queries
    $params = [];
    $searchBuilder = new SecureSearchBuilder();
    $whereResult = $searchBuilder->buildWhereClause($search_array, $_GET, $params, $table_name);
    $whereClause = $whereResult['where_clause'];
    $params = $whereResult['params'];
    $noof_criteria = $whereResult['count'];

    // Organization access validation
    $orgWhereClause = '';
    if (property_exists($$class, 'bu_org_id') && in_array('bu_org_id', $$class->field_a)) {
        if (empty($_SESSION['user_org_access'])) {
            $no_organization_access = true;
            return;
        }
        $orgIds = array_keys($_SESSION['user_org_access']);
        if (!empty($orgIds)) {
            $placeholders = [];
            foreach ($orgIds as $i => $orgId) {
                $paramName = ":org_{$i}";
                $placeholders[] = $paramName;
                $params[$paramName] = $orgId;
            }
            $orgWhereClause = "bu_org_id IN (" . implode(',', $placeholders) . ") OR bu_org_id IS NULL";
        }
    } else if (property_exists($$class, 'org_id') && in_array('org_id', $$class->field_a)) {
        if (empty($_SESSION['user_org_access'])) {
            $no_organization_access = true;
            return;
        }
        $orgIds = array_keys($_SESSION['user_org_access']);
        if (!empty($orgIds)) {
            $placeholders = [];
            foreach ($orgIds as $i => $orgId) {
                $paramName = ":org_{$i}";
                $placeholders[] = $paramName;
                $params[$paramName] = $orgId;
            }
            $orgWhereClause = "org_id IN (" . implode(',', $placeholders) . ") OR org_id IS NULL";
        }
    }

    // Combine WHERE clauses
    $combinedWhereClause = $whereClause;
    if (!empty($orgWhereClause)) {
        if (!empty($combinedWhereClause)) {
            $combinedWhereClause .= ' AND ' . $orgWhereClause;
        } else {
            $combinedWhereClause = ' WHERE ' . $orgWhereClause;
        }
    }

    // Handle group by
    $groupBy = !empty($_GET['group_by'][0]) ? $_GET['group_by'][0] : '';
    $sumElement = $$class->search_groupBy_sum ?? '';

    // Build secure queries
    $queryResult = $searchBuilder->buildSearchQuery(
        $table_name,
        $combinedWhereClause,
        $params,
        $search_order_by,
        $search_asc_desc,
        $per_page ?? 0,
        !empty($per_page) && !empty($pageno) ? (($pageno - 1) * $per_page) : 0,
        $groupBy,
        $sumElement
    );

    $sql = $queryResult['sql'];
    $count_sql = $queryResult['count_sql'];
    $all_download_sql = $queryResult['download_sql'];
    $params = $queryResult['params'];

    // Handle special report/function cases
    if ((!empty($_GET['report_name'])) && method_exists($$class, $_GET['report_name'][0])) {
        $report_name = $_GET['report_name'][0];
        $search_result_statement = call_user_func(array($$class, $report_name), $_GET);
        echo '<div id="searchResult"><div id="search_result" class="search_report">';
        echo '<ul class="inline-block"> <li id="export_excel_searchResult" class="clickable" title="Export to Excel"><i class="fa fa-file-excel-o"></i></li>
                  <li id="print_searchResult" class=" print clickable" title="Print"><i class="fa fa-print"></i></li>
             </ul>';
        echo $search_result_statement;
        echo '</div></div></div>';
        return;
    } else if ((!empty($_GET['function_name']))) {
        $function_name = is_array($_GET['function_name']) ? $_GET['function_name'][0] : $_GET['function_name'];
        if (method_exists($$class, $function_name)) {
            $search_details = call_user_func(array($$class, $function_name), $_GET);
            $search_counts = $function_name . '_search_counts';
            $search_records = $function_name . '_search_records';
            $search_downloads = $function_name . '_search_downloads';
            $_GET['whereClause'] = $combinedWhereClause;
            $total_count = call_user_func(array($$class, $search_counts), $_GET);
            $search_result = call_user_func(array($$class, $search_records), $_GET);
            $all_download_sql = call_user_func(array($$class, $search_downloads), $_GET);
            if (!empty($per_page) && !empty($total_count)) {
                $pagination = new pagination($pageno, $per_page, $total_count);
                $pagination_statement = $pagination->show_pagination();
            }
        }
    } else if (method_exists($$class, 'search_records')) {
        $_GET['whereClause'] = $combinedWhereClause;
        $total_count = call_user_func(array($$class, 'search_counts'), $_GET);
        $search_result = call_user_func(array($$class, 'search_records'), $_GET);
        $all_download_sql = call_user_func(array($$class, 'search_downloads'), $_GET);
        if (!empty($per_page) && !empty($total_count)) {
            $pagination = new pagination($pageno, $per_page, $total_count);
            $pagination_statement = $pagination->show_pagination();
        }
    } else {
        // Execute secure queries
        $total_count = $searchBuilder->executeCount($count_sql, $params, $class);
        $total_count_all = $searchBuilder->executeCount($count_sql, $params, $class);

        $search_result = $searchBuilder->executeSearch($sql, $params, $class);
    }

    if (method_exists($class, 'search_add_extra_fields')) {
        $class::search_add_extra_fields($search_result);
    }

    if (property_exists($class, 'search')) {
        foreach ($$class->search as $searchParaKey => $searchParaValue) {
            $s->setProperty($searchParaKey, $searchParaValue);
        }
    }
    $total_count_all = empty($total_count_all) ? $total_count : $total_count_all;
    $s->setProperty('result', $search_result);
    $s->setProperty('_searching_class', $class);
    $s->setProperty('_per_page', $per_page);
    $s->setProperty('primary_column_s', $primary_column);
    $s->setProperty('column_array_s', $column_array);
    $search_result_statement = $search->search_result_op();

    // Check for scheduling and schedule the report accordingly
    if (!empty($_GET['frequency_uom'][0]) && !empty($_GET['frequency_value'][0])) {
        $ps = new sys_program_schedule();
        $ps->program_class_name = $class;
        $program_name = !empty($function_name) ? $function_name : 'search_records';
        $ps->program_name = $program_name;
        $ps->frequency_uom = $_GET['frequency_uom'][0];
        $ps->frequency_value = $_GET['frequency_value'][0];
        $ps->start_date_time = $_GET['start_date_time'][0];
        $ps->op_email_address = $_GET['email_addresses'][0];
        $ps->op_email_format = $_GET['email_format'][0];
        $ps->status = 'ACTIVE';
        $ps->request_type = 'REPORT';
        $ps->parameter = serialize($_GET);
        $ps->report_query = base64_encode($all_download_sql);
        try {
            $ps->save();
            echo "<div class='message'>The program is Successfully scheduled. sys_program_schedule_id id  " . $ps->sys_program_schedule_id . '</div>';
        } catch (Exception $e) {
            echo "<br>Unable to schedule the program. Error @ class_sys_program @@ line " . __LINE__ . '<br>' . $e->getMessage();
        }
    }

    $search_class_obj_all = $$class->findBySql($all_download_sql, $params);
    $search_class_array_all = json_decode(json_encode($search_class_obj_all), true);

    if (!empty($_GET['email_addresses'])) {
        //send email
        $im = new inomail();
        $im->FromName = $si->site_name;
        $email_a = explode(',', $_GET['email_addresses'][0]);
        foreach ($email_a as $em_k => $email_v) {
            $im->addAddress($email_v);
        }
        $im->addReplyTo($si->email, 'Search Output');
        $im->Subject = 'Search Result';
        $im->Body = 'Please find attached the search result';

        switch ($_GET['email_format'][0]) {
            case 'text_format' :
                $report_op = array2text($search_class_array_all);
                $file_name = date("Y-m-d") . '_' . $class . '_report_output.txt';
                break;

            case 'pdf_format' :
                $report_op = array2pdf($search_class_array_all);
                $file_name = date("Y-m-d") . '_' . $class . '_report_output.pdf';
                break;

            case 'xml_format' :
                $report_op = array2xml($search_class_array_all);
                $file_name = date("Y-m-d") . '_' . $class . '_report_output.txt';
                break;

            case 'worddoc_format' :
                $report_op = array2worddoc($search_class_array_all);
                $file_name = date("Y-m-d") . '_' . $class . '_report_output.doc';
                break;

            default :
                $report_op = array2csv($search_class_array_all);
                $file_name = date("Y-m-d") . '_' . $class . '_report_output.csv';
                break;
        }

        $im->addStringAttachment($report_op, $file_name);
        $im->ino_sendMail();
    }

    include_once(__DIR__ . '/../template/json_search_template.inc');
    echo '</div>';

    $dbc->confirm();
}
?>