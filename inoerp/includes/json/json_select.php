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
    $_GET['per_page'] = !empty($per_page) ? $per_page : '10';

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
            foreach ($ppl as $search_key => $search_val) {
                $_GET[$search_key] = $search_val;
            }
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
                $pagination->setProperty('_path', 'select');
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
            $pagination->setProperty('_path', 'select');
            $pagination_statement = $pagination->show_pagination();
        }
    } else {
        // Execute secure queries
        $total_count = $searchBuilder->executeCount($count_sql, $params, $class);
        $total_count_all = $searchBuilder->executeCount(
            str_replace('SELECT COUNT(*)', 'SELECT COUNT(*)', $count_sql), 
            $params, 
            $class
        );
        
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
    $select_result_statement = $search->select_result_op();

    include_once(__DIR__ . '/../template/json_select_template.inc');
    echo '</div>';
}
?>