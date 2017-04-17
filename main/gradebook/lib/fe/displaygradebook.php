<?php
/* For licensing terms, see /license.txt */

/**
 * Class DisplayGradebook
 * @package chamilo.gradebook
 */
class DisplayGradebook
{
    /**
     * Displays the header for the result page containing the navigation tree and links
     * @param $evalobj
     * @param $selectcat
     * @param $shownavbar 1=show navigation bar
     * @param $forpdf only output for pdf file
     */
    public static function display_header_result($evalobj, $selectcat, $page)
    {
        $header = null;
        if (api_is_allowed_to_edit(null, true)) {
            $header = '<div class="actions">';
            if ($page != 'statistics') {
                $header .= '<a href="'.Security::remove_XSS($_SESSION['gradebook_dest']).'?selectcat='.$selectcat.'&'.api_get_cidreq().'">'.
                    Display::return_icon(('back.png'), get_lang('FolderView'), '', ICON_SIZE_MEDIUM).'</a>';
                if ($evalobj->get_course_code() == null) {

                } elseif (!$evalobj->has_results()) {
                    $header .= '<a href="gradebook_add_result.php?'.api_get_cidreq().'&selectcat='.$selectcat.'&selecteval='.$evalobj->get_id().'">
    				' . Display::return_icon('evaluation_rate.png', get_lang('AddResult'), '', ICON_SIZE_MEDIUM).'</a>';
                }

                if (api_is_platform_admin() || $evalobj->is_locked() == false) {
                    $header .= '<a href="'.api_get_self().'?'.api_get_cidreq().'&selecteval='.$evalobj->get_id().'&import=">'.
                        Display::return_icon('import_evaluation.png', get_lang('ImportResult'), '', ICON_SIZE_MEDIUM).'</a>';
                }

                if ($evalobj->has_results()) {
                    $header .= '<a href="'.api_get_self().'?'.api_get_cidreq().'&selecteval='.$evalobj->get_id().'&export=">'.
                        Display::return_icon('export_evaluation.png', get_lang('ExportResult'), '', ICON_SIZE_MEDIUM).'</a>';

                    if (api_is_platform_admin() || $evalobj->is_locked() == false) {
                        $header .= '<a href="gradebook_edit_result.php?'.api_get_cidreq().'&selecteval='.$evalobj->get_id().'">'.
                            Display::return_icon('edit.png', get_lang('EditResult'), '', ICON_SIZE_MEDIUM).'</a>';
                        $header .= '<a href="'.api_get_self().'?'.api_get_cidreq().'&selecteval='.$evalobj->get_id().'&deleteall=" onclick="return confirmationall();">'.
                            Display::return_icon('delete.png', get_lang('DeleteResult'), '', ICON_SIZE_MEDIUM).'</a>';
                    }
                }

                $header .= '<a href="'.api_get_self().'?'.api_get_cidreq().'&print=&selecteval='.$evalobj->get_id().'" target="_blank">'.
                    Display::return_icon('printer.png', get_lang('Print'), '', ICON_SIZE_MEDIUM).'</a>';
            } else {
                $header .= '<a href="gradebook_view_result.php?'.api_get_cidreq().'&selecteval='.Security::remove_XSS($_GET['selecteval']).'"> '.
                    Display::return_icon(('back.png'), get_lang('FolderView'), '', ICON_SIZE_MEDIUM).'</a>';
            }
            $header .= '</div>';
        }

        if ($evalobj->is_visible() == '1') {
            $visible = get_lang('Yes');
        } else {
            $visible = get_lang('No');
        }

        $scoredisplay = ScoreDisplay::instance();
        $student_score = '';
        $average = '';
        if (($evalobj->has_results())) {
            // TODO this check needed ?
            $score = $evalobj->calc_score();
            if ($score != null) {
                $average = get_lang('Average').' :<b> '.$scoredisplay->display_score($score, SCORE_AVERAGE).'</b>';
                $student_score = $evalobj->calc_score(api_get_user_id());
                $student_score = Display::tag(
                    'h3',
                    get_lang('Score').': '.$scoredisplay->display_score($student_score, SCORE_DIV_PERCENT)
                );
            }
        }
        $description = "";
        if (!$evalobj->get_description() == '') {
            $description = get_lang('Description').' :<b> '.$evalobj->get_description().'</b><br>';
        }

        if ($evalobj->get_course_code() == null) {
            $course = get_lang('CourseIndependent');
        } else {
            $course = CourseManager::getCourseNameFromCode($evalobj->get_course_code());
        }

        $evalinfo = '<table width="100%" border="0"><tr><td>';
        $evalinfo .= '<h2>'.$evalobj->get_name().'</h2><hr>';
        $evalinfo .= $description;
        $evalinfo .= get_lang('Course').' :<b> '.$course.'</b><br />';
        $evalinfo .= get_lang('QualificationNumeric').' :<b> '.$evalobj->get_max().'</b><br>'.$average;

        if (!api_is_allowed_to_edit()) {
            $evalinfo .= $student_score;
        }

        if (!$evalobj->has_results()) {
            $evalinfo .= '<br /><i>'.get_lang('NoResultsInEvaluation').'</i>';
        } elseif ($scoredisplay->is_custom() && api_get_self() != '/main/gradebook/gradebook_statistics.php') {
            if (api_is_allowed_to_edit(null, true)) {
                if ($page != 'statistics') {
                    //$evalinfo .= '<br /><br /><a href="gradebook_view_result.php?selecteval='.Security::remove_XSS($_GET['selecteval']).'"> '.Display::return_icon(('evaluation_rate.png'),get_lang('ViewResult'),'',ICON_SIZE_MEDIUM) . '</a>';
                }
            }
        }
        if ($page != 'statistics') {
            if (api_is_allowed_to_edit(null, true)) {
                $evalinfo .= '<br /><a href="gradebook_statistics.php?'.api_get_cidreq().'&selecteval='.Security::remove_XSS($_GET['selecteval']).'"> '.
                    Display::return_icon('statistics.png', get_lang('ViewStatistics'), '', ICON_SIZE_MEDIUM).'</a>';
            }
        }
        $evalinfo .= '</td><td>'.Display::return_icon('tutorial.gif', '', ['style' => 'float:right; position:relative;']).'</td></table>';
        echo $evalinfo;
        echo $header;
    }


    /**
     * Displays the header for the flatview page containing filters
     * @param $catobj
     * @param $showeval
     * @param $showlink
     */
    public static function display_header_reduce_flatview($catobj, $showeval, $showlink, $simple_search_form)
    {
        $header = '<div class="actions">';
        if ($catobj->get_parent_id() == 0) {
            $select_cat = $catobj->get_id();
            $url = Security::remove_XSS($_SESSION['gradebook_dest']);
        } else {
            $select_cat = $catobj->get_parent_id();
            $url = 'gradebook_flatview.php';
        }
        $header .= '<a href="'.$url.'?'.api_get_cidreq().'&selectcat='.$select_cat.'">'.
            Display::return_icon('back.png', get_lang('FolderView'), '', ICON_SIZE_MEDIUM).'</a>';

        $pageNum = isset($_GET['flatviewlist_page_nr']) ? intval($_GET['flatviewlist_page_nr']) : null;
        $perPage = isset($_GET['flatviewlist_per_page']) ? intval($_GET['flatviewlist_per_page']) : null;
        $offset = isset($_GET['offset']) ? $_GET['offset'] : '0';

        $exportCsvUrl = api_get_self().'?'.api_get_cidreq().'&'.http_build_query([
            'export_format' => 'csv',
            'export_report' => 'export_report',
            'selectcat' => $catobj->get_id()
        ]);

        $header .= Display::url(
            Display::return_icon('export_csv.png', get_lang('ExportAsCSV'), '', ICON_SIZE_MEDIUM),
            $exportCsvUrl
        );

        $exportXlsUrl = api_get_self().'?'.api_get_cidreq().'&'.http_build_query([
            'export_format' => 'xls',
            'export_report' => 'export_report',
            'selectcat' => $catobj->get_id()
        ]);

        $header .= Display::url(
            Display::return_icon('export_excel.png', get_lang('ExportAsXLS'), '', ICON_SIZE_MEDIUM),
            $exportXlsUrl
        );

        $exportDocUrl = api_get_self().'?'.api_get_cidreq().'&'.http_build_query([
            'export_format' => 'doc',
            'export_report' => 'export_report',
            'selectcat' => $catobj->get_id()
        ]);

        $header .= Display::url(
            Display::return_icon('export_doc.png', get_lang('ExportAsDOC'), '', ICON_SIZE_MEDIUM),
            $exportDocUrl
        );

        $exportPrintUrl = api_get_self().'?'.api_get_cidreq().'&'.http_build_query([
                'print' => '',
                'selectcat' => $catobj->get_id(),
            ]);

        $header .= Display::url(
            Display::return_icon('printer.png', get_lang('Print'), '', ICON_SIZE_MEDIUM),
            $exportPrintUrl,
            ['target' => '_blank']
        );

        $exportPdfUrl = api_get_self().'?'.api_get_cidreq().'&'.http_build_query([
            'exportpdf' => '',
            'selectcat' => $catobj->get_id(),
            'offset' => $offset,
            'flatviewlist_page_nr' => $pageNum,
            'flatviewlist_per_page' => $perPage
        ]);

        $header .= Display::url(
            Display::return_icon('pdf.png', get_lang('ExportToPDF'), '', ICON_SIZE_MEDIUM),
            $exportPdfUrl
        );

        $header .= '</div>';
        echo $header;
    }

    /**
     * Displays the header for the gradebook containing the navigation tree and links
     * @param Category $catobj
     * @param int $showtree '1' will show the browse tree and naviation buttons
     * @param boolean $is_course_admin
     * @param boolean $is_platform_admin
     * @param boolean Whether to show or not the link to add a new qualification
     * (we hide it in case of the course-embedded tool where we have only one
     * calification per course or session)
     * @param boolean Whether to show or not the link to add a new item inside
     * the qualification (we hide it in case of the course-embedded tool
     * where we have only one calification per course or session)
     * @return void Everything is printed on screen upon closing
     */
    public static function header(
        $catobj,
        $showtree,
        $selectcat,
        $is_course_admin,
        $is_platform_admin,
        $simple_search_form,
        $show_add_qualification = true,
        $show_add_link = true,
        $certificateLinkInfo = null
    ) {
        $userId = api_get_user_id();
        $courseId = api_get_course_int_id();
        $sessionId = api_get_session_id();

        // Student.
        $status = CourseManager::getUserInCourseStatus($userId, $courseId);
        $sessionStatus = 0;

        if (!empty($sessionId)) {
            $sessionStatus = SessionManager::get_user_status_in_course_session(
                $userId,
                $courseId,
                $sessionId
            );
        }

        $objcat = new Category();
        $course_id = CourseManager::get_course_by_category($selectcat);
        $message_resource = $objcat->show_message_resource_delete($course_id);
        $grade_model_id = $catobj->get_grade_model_id();
        $header = null;

        if (isset($catobj) && !empty($catobj)) {
            $categories = Category::load(
                null,
                null,
                null,
                $catobj->get_id(),
                null,
                $sessionId
            );
        }

        if (!$is_course_admin && ($status <> 1 || $sessionStatus == 0) && $selectcat <> 0) {
            $catcourse = Category::load($catobj->get_id());
            /** @var Category $category */
            $category = $catcourse[0];
            $main_weight = $category->get_weight();
            $scoredisplay = ScoreDisplay::instance();
            $allevals = $category->get_evaluations($userId, true);
            $alllinks = $category->get_links($userId, true);
            $allEvalsLinks = array_merge($allevals, $alllinks);
            $item_value_total = 0;
            $scoreinfo = null;

            for ($count = 0; $count < count($allEvalsLinks); $count++) {
                $item = $allEvalsLinks[$count];
                $score = $item->calc_score($userId);
                if (!empty($score)) {
                    $divide = $score[1] == 0 ? 1 : $score[1];
                    $item_value = $score[0] / $divide * $item->get_weight();
                    $item_value_total += $item_value;
                }
            }

            $item_total = $main_weight;
            $total_score = array($item_value_total, $item_total);
            $scorecourse_display = $scoredisplay->display_score($total_score, SCORE_DIV_PERCENT);
            if ((!$catobj->get_id() == '0') && (!isset($_GET['studentoverview'])) && (!isset($_GET['search']))) {
                $aditionalButtons = null;
                if (!empty($certificateLinkInfo)) {
                    $aditionalButtons .= '<div class="btn-group pull-right">';
                    $aditionalButtons .= isset($certificateLinkInfo['certificate_link']) ? $certificateLinkInfo['certificate_link'] : '';
                    $aditionalButtons .= isset($certificateLinkInfo['badge_link']) ? $certificateLinkInfo['badge_link'] : '';
                    $aditionalButtons .= '</div>';
                }
                $scoreinfo .= '<strong>'.sprintf(get_lang('TotalX'), $scorecourse_display.$aditionalButtons).'</strong>';

            }
            Display::display_normal_message($scoreinfo, false);
        }

        // show navigation tree and buttons?
        if ($showtree == '1' || isset($_GET['studentoverview'])) {
            $header = '<div class="actions"><table>';
            $header .= '<tr>';
            if (!$selectcat == '0') {
                $header .= '<td><a href="'.api_get_self().'?selectcat='.$catobj->get_parent_id().'">'.
                    Display::return_icon('back.png', get_lang('BackTo').' '.get_lang('RootCat'), '', ICON_SIZE_MEDIUM).'</a></td>';
            }
            $header .= '<td>'.get_lang('CurrentCategory').'</td>'.
                    '<td><form name="selector"><select name="selectcat" onchange="document.selector.submit()">';
            $cats = Category::load();

            $tree = $cats[0]->get_tree();
            unset($cats);
            $line = null;
            foreach ($tree as $cat) {
                for ($i = 0; $i < $cat[2]; $i++) {
                    $line .= '&mdash;';
                }
                $line = isset($line) ? $line : '';
                if (isset($_GET['selectcat']) && $_GET['selectcat'] == $cat[0]) {
                    $header .= '<option selected value='.$cat[0].'>'.$line.' '.$cat[1].'</option>';
                } else {
                    $header .= '<option value='.$cat[0].'>'.$line.' '.$cat[1].'</option>';
                }
                $line = '';
            }
            $header .= '</select></form></td>';
            if (!empty($simple_search_form) && $message_resource === false) {
                $header .= '<td style="vertical-align: top;">'.$simple_search_form->toHtml().'</td>';
            } else {
                $header .= '<td></td>';
            }
            if ($is_course_admin &&
                $message_resource === false &&
                isset($_GET['selectcat']) && $_GET['selectcat'] != 0
            ) {
            } elseif (!(isset($_GET['studentoverview']))) {

            } else {
                $header .= '<td style="vertical-align: top;"><a href="'.api_get_self().'?'.api_get_cidreq().'&studentoverview=&exportpdf=&selectcat='.$catobj->get_id().'" target="_blank">
							 '.Display::return_icon('pdf.png', get_lang('ExportPDF'), [], ICON_SIZE_MEDIUM).'
							' . get_lang('ExportPDF').'</a>';
            }
            $header .= '</td></tr>';
            $header .= '</table></div>';
        }

        // for course admin & platform admin add item buttons are added to the header

        $actionsLeft = '';
        $my_api_cidreq = api_get_cidreq();

        if (api_is_allowed_to_edit(null, true)) {
            if (empty($grade_model_id) || $grade_model_id == -1) {
                $actionsLeft .= '<a href="gradebook_add_cat.php?'.api_get_cidreq().'&selectcat='.$catobj->get_id().'">'.
                    Display::return_icon('new_folder.png', get_lang('AddGradebook'), array(), ICON_SIZE_MEDIUM).'</a></td>';
            }
            if ($selectcat == '0') {

            } else {
                $my_category = $catobj->shows_all_information_an_category($catobj->get_id());
                if ($my_api_cidreq == '') {
                    $my_api_cidreq = 'cidReq='.$my_category['course_code'];
                }
                if ($show_add_link && !$message_resource) {
                    $actionsLeft .= '<a href="gradebook_add_eval.php?'.$my_api_cidreq.'&selectcat='.$catobj->get_id().'" >'.
                        Display::return_icon('new_evaluation.png', get_lang('NewEvaluation'), '', ICON_SIZE_MEDIUM).'</a>';
                    $cats = Category::load($selectcat);

                    if ($cats[0]->get_course_code() != null && !$message_resource) {
                        $actionsLeft .= '<a href="gradebook_add_link.php?'.$my_api_cidreq.'&selectcat='.$catobj->get_id().'">'.
                            Display::return_icon('new_online_evaluation.png', get_lang('MakeLink'), '', ICON_SIZE_MEDIUM).'</a>';
                    } else {
                        $actionsLeft .= '<a href="gradebook_add_link_select_course.php?'.$my_api_cidreq.'&selectcat='.$catobj->get_id().'">'.
                            Display::return_icon('new_online_evaluation.png', get_lang('MakeLink'), '', ICON_SIZE_MEDIUM).'</a>';
                    }
                }

                if (!$message_resource) {
                    $actionsLeft .= '<a href="gradebook_flatview.php?'.$my_api_cidreq.'&selectcat='.$catobj->get_id().'">'.
                        Display::return_icon('stats.png', get_lang('FlatView'), '', ICON_SIZE_MEDIUM).'</a>';

                    if ($my_category['generate_certificates'] == 1) {
                        $actionsLeft .= Display::url(
                            Display::return_icon(
                                'certificate_list.png',
                                get_lang('GradebookSeeListOfStudentsCertificates'),
                                '',
                                ICON_SIZE_MEDIUM
                            ),
                            "gradebook_display_certificate.php?$my_api_cidreq&cat_id=".$selectcat
                        );
                    }

                    $actionsLeft .= Display::url(
                        Display::return_icon(
                            'user.png',
                            get_lang('GradebookListOfStudentsReports'),
                            '',
                            ICON_SIZE_MEDIUM
                        ),
                        "gradebook_display_summary.php?$my_api_cidreq&selectcat=".$selectcat
                    );

                    // Right icons
                    $actionsRight = '<a href="gradebook_edit_cat.php?editcat='.$catobj->get_id().'&amp;cidReq='.$catobj->get_course_code().'&id_session='.$catobj->get_session_id().'">'.
                        Display::return_icon('edit.png', get_lang('Edit'), '', ICON_SIZE_MEDIUM).'</a>';
                    $actionsRight .= '<a href="../document/document.php?curdirpath=/certificates&'.$my_api_cidreq.'&origin=gradebook&selectcat='.$catobj->get_id().'">'.
                            Display::return_icon('certificate.png', get_lang('AttachCertificate'), '', ICON_SIZE_MEDIUM).'</a>';

                    if (empty($categories)) {
                        $actionsRight .= '<a href="gradebook_edit_all.php?id_session='.api_get_session_id().'&amp;'.$my_api_cidreq.'&selectcat='.$catobj->get_id().'">'.
                            Display::return_icon('percentage.png', get_lang('EditAllWeights'), '', ICON_SIZE_MEDIUM).'</a>';
                    }
                    $score_display_custom = api_get_setting('gradebook_score_display_custom');
                    if (api_get_setting('teachers_can_change_score_settings') == 'true' && $score_display_custom['my_display_custom'] == 'true') {
                        $actionsRight .= '<a href="gradebook_scoring_system.php?'.$my_api_cidreq.'&selectcat='.$catobj->get_id().'">'.
                            Display::return_icon('ranking.png', get_lang('ScoreEdit'), '', ICON_SIZE_MEDIUM).'</a>';
                    }

                }
            }
        } elseif (isset($_GET['search'])) {
            echo $header = '<b>'.get_lang('SearchResults').' :</b>';
        }

        $isDrhOfCourse = CourseManager::isUserSubscribedInCourseAsDrh(
            api_get_user_id(),
            api_get_course_info()
        );

        if ($isDrhOfCourse) {
            $$actionsLeft .= '<a href="gradebook_flatview.php?'.$my_api_cidreq.'&selectcat='.$catobj->get_id().'">'.
                Display::return_icon('stats.png', get_lang('FlatView'), '', ICON_SIZE_MEDIUM).'</a>';
        }

        if (api_is_allowed_to_edit(null, true)) {
            echo $toolbar = Display::toolbarAction('gradebook-actions', array($actionsLeft, $actionsRight));
        }

        if (api_is_allowed_to_edit(null, true)) {
            $weight = intval($catobj->get_weight()) > 0 ? $catobj->get_weight() : 0;
            $weight = '<strong>'.get_lang('TotalWeight').' : </strong>'.$weight;

            $min_certification = (intval($catobj->get_certificate_min_score() > 0) ? $catobj->get_certificate_min_score() : 0);
            $min_certification = get_lang('CertificateMinScore').' : '.$min_certification;
            $edit_icon = '<a href="gradebook_edit_cat.php?editcat='.$catobj->get_id().'&amp;cidReq='.$catobj->get_course_code().'&id_session='.$catobj->get_session_id().'">'.
                Display::return_icon('edit.png', get_lang('Edit'), array(), ICON_SIZE_SMALL).'</a>';
            //$msg = Display::tag('h3', $weight.' - '.$min_certification);
            $msg = $weight.' - '.$min_certification.$edit_icon;
            //@todo show description
            $description = (($catobj->get_description() == "" || is_null($catobj->get_description())) ? '' : '<strong>'.get_lang('GradebookDescriptionLog').'</strong>'.': '.$catobj->get_description());
            Display::display_normal_message($msg, false);
            if (!empty($description)) {
                echo Display::div($description, array());
            }
        }
    }

    /**
     * @param Category $catobj
     * @param $is_course_admin
     * @param $is_platform_admin
     * @param $simple_search_form
     * @param bool $show_add_qualification
     * @param bool $show_add_link
     */
    public function display_reduce_header_gradebook(
        $catobj,
        $is_course_admin,
        $is_platform_admin,
        $simple_search_form,
        $show_add_qualification = true,
        $show_add_link = true
    ) {
        //student
        if (!$is_course_admin) {
            $user = api_get_user_info(api_get_user_id());
            $catcourse = Category::load($catobj->get_id());
            $scoredisplay = ScoreDisplay::instance();
            $scorecourse = $catcourse[0]->calc_score(api_get_user_id());
            $scorecourse_display = (isset($scorecourse) ? $scoredisplay->display_score($scorecourse, SCORE_AVERAGE) : get_lang('NoResultsAvailable'));
            $cattotal = Category::load(0);
            $scoretotal = $cattotal[0]->calc_score(api_get_user_id());
            $scoretotal_display = (isset($scoretotal) ? $scoredisplay->display_score($scoretotal, SCORE_PERCENT) : get_lang('NoResultsAvailable'));
            $scoreinfo = get_lang('StatsStudent').' :<b> '.$user['complete_name'].'</b><br />';
            if ((!$catobj->get_id() == '0') && (!isset($_GET['studentoverview'])) && (!isset($_GET['search'])))
                $scoreinfo .= '<br />'.get_lang('TotalForThisCategory').' : <b>'.$scorecourse_display.'</b>';
            $scoreinfo .= '<br />'.get_lang('Total').' : <b>'.$scoretotal_display.'</b>';
            Display::display_normal_message($scoreinfo, false);
        }
        // show navigation tree and buttons?
        $header = '<div class="actions">';

        if ($is_course_admin) {
            $header .= '<a href="gradebook_flatview.php?'.api_get_cidreq().'&selectcat='.$catobj->get_id().'">'.Display::return_icon('stats.png', get_lang('FlatView'), '', ICON_SIZE_MEDIUM).'</a>';
            $header .= '<a href="gradebook_scoring_system.php?'.api_get_cidreq().'&selectcat='.$catobj->get_id().'">'.Display::return_icon('settings.png', get_lang('ScoreEdit'), '', ICON_SIZE_MEDIUM).'</a>';
        } elseif (!(isset($_GET['studentoverview']))) {
            $header .= '<a href="'.api_get_self().'?'.api_get_cidreq().'&studentoverview=&selectcat='.$catobj->get_id().'">'.Display::return_icon('view_list.gif', get_lang('FlatView')).' '.get_lang('FlatView').'</a>';
        } else {
            $header .= '<a href="'.api_get_self().'?'.api_get_cidreq().'&studentoverview=&exportpdf=&selectcat='.$catobj->get_id().'" target="_blank">'.Display::return_icon('pdf.png', get_lang('ExportPDF'), '', ICON_SIZE_MEDIUM).'</a>';
        }
        $header .= '</div>';
        echo $header;
    }

    /**
     * @param $userId
     * @param $categoryId
     * @return string
     */
    public static function display_header_user($userId, $categoryId)
    {
        $user = api_get_user_info($userId);
        if (empty($user)) {
            return '';
        }

        $catcourse = Category::load($categoryId);
        $scoredisplay = ScoreDisplay::instance();

        // generating the total score for a course
        $allevals = $catcourse[0]->get_evaluations($userId, true, api_get_course_id());
        $alllinks = $catcourse[0]->get_links($userId, true, api_get_course_id());
        $evals_links = array_merge($allevals, $alllinks);
        $item_value = 0;
        $item_total = 0;
        for ($count = 0; $count < count($evals_links); $count++) {
            $item = $evals_links[$count];
            $score = $item->calc_score($userId);
            $my_score_denom = ($score[1] == 0) ? 1 : $score[1];
            $item_value += $score[0] / $my_score_denom * $item->get_weight();
            $item_total += $item->get_weight();
        }
        $item_value = api_number_format($item_value, 2);
        $total_score = array($item_value, $item_total);
        $scorecourse_display = $scoredisplay->display_score($total_score, SCORE_DIV_PERCENT);

        $info = '<div class="row"><div class="col-md-3">';
        $info .= '<div class="thumbnail"><img src="'.$user['avatar'].'" /></div>';
        $info .= '</div>';
        $info .= '<div class="col-md-6">';
        $info .= get_lang('Name').' :  <a target="_blank" href="'.api_get_path(WEB_CODE_PATH).'social/profile.php?u='.$userId.'"> '.
            $user['complete_name'].'</a><br />';

        if (api_get_setting('show_email_addresses') == 'true') {
            $info .= get_lang('Email').' : <a href="mailto:'.$user['email'].'">'.$user['email'].'</a><br />';
        }

        $info .= get_lang('TotalUser').' : <b>'.$scorecourse_display.'</b>';
        $info .= '</div>';
        $info .= '</div>';
        echo $info;
    }
}
