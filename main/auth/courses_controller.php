<?php
/* For licensing terms, see /license.txt */

use Chamilo\CoreBundle\Entity\SequenceResource;
use Chamilo\CoreBundle\Entity\SessionRelCourse;
use Chamilo\CoreBundle\Entity\Tag;
use Chamilo\CoreBundle\Repository\SequenceRepository;

/**
 * Class CoursesController.
 *
 * This file contains class used like controller,
 * it should be included inside a dispatcher file (e.g: index.php)
 *
 * @author Christian Fasanando <christian1827@gmail.com> - BeezNest
 *
 * @package chamilo.auth
 */
class CoursesController
{
    private $toolname;
    private $view;
    private $model;

    /**
     * Constructor.
     */
    public function __construct()
    {
        $this->toolname = 'auth';
        //$actived_theme_path = api_get_template();
        $this->view = new View($this->toolname);
        $this->model = new Auth();
    }

    /**
     * It's used for listing courses,
     * render to courses_list view.
     *
     * @param string $action
     * @param string $message confirmation message(optional)
     */
    public function courseList($action, $message = '')
    {
        $data = [];
        $data['user_courses'] = $this->model->get_courses_of_user(api_get_user_id());
        $data['user_course_categories'] = CourseManager::get_user_course_categories(api_get_user_id());
        $data['courses_in_category'] = $this->model->get_courses_in_category();
        $data['action'] = $action;
        $data['message'] = $message;

        // render to the view
        $this->view->set_data($data);
        $this->view->set_layout('catalog_layout');
        $this->view->set_template('courses_list');
        $this->view->render();
    }

    /**
     * It's used for listing categories, render to categories_list view.
     */
    public function categoryList()
    {
        api_block_anonymous_users();
        $stok = Security::get_token();
        $actions = Display::url(
            Display::return_icon('back.png', get_lang('Back'), '', '32'),
            api_get_path(WEB_CODE_PATH).'auth/courses.php?action=sortmycourses'
        );
        $actions = Display::toolbarAction('toolbar-forum', [$actions]);

        $form = new FormValidator(
            'create_course_category',
            'post',
            api_get_path(WEB_CODE_PATH).'auth/courses.php?action=createcoursecategory'
        );
        $form->addHidden('sec_token', $stok);
        $form->addText('title_course_category', get_lang('Name'));
        $form->addButtonSave(get_lang('AddCategory'), 'create_course_category');

        $tpl = new Template();
        $tpl->assign('content', $form->returnForm());
        $tpl->assign('actions', $actions);
        $tpl->display_one_col_template();
    }

    /**
     * It's used for listing courses with categories,
     * render to courses_categories view.
     *
     * @param string $action
     * @param string $category_code
     * @param string $message
     * @param string $error
     * @param string $content
     * @param array  $limit         will be used if $random_value is not set.
     *                              This array should contains 'start' and 'length' keys
     *
     * @internal param \action $string
     * @internal param \Category $string code (optional)
     */
    public function courses_categories(
        $action,
        $category_code = null,
        $message = '',
        $error = '',
        $content = null,
        $limit = []
    ) {
        $data = [];
        $browse_course_categories = CoursesAndSessionsCatalog::getCourseCategories();
        $data['countCoursesInCategory'] = CourseCategory::countCoursesInCategory($category_code);
        if ($action === 'display_random_courses') {
            // Random value is used instead limit filter
            $data['browse_courses_in_category'] = CoursesAndSessionsCatalog::getCoursesInCategory(
                null,
                12
            );
            $data['countCoursesInCategory'] = count($data['browse_courses_in_category']);
        } else {
            if (!isset($category_code)) {
                $category_code = $browse_course_categories[0][1]['code']; // by default first category
            }
            $limit = isset($limit) ? $limit : self::getLimitArray();
            $data['browse_courses_in_category'] = CoursesAndSessionsCatalog::getCoursesInCategory(
                $category_code,
                null,
                $limit
            );
        }

        $data['browse_course_categories'] = $browse_course_categories;
        $data['code'] = Security::remove_XSS($category_code);

        // getting all the courses to which the user is subscribed to
        $curr_user_id = api_get_user_id();
        $user_courses = $this->model->get_courses_of_user($curr_user_id);
        $user_coursecodes = [];

        // we need only the course codes as these will be used to match against the courses of the category
        if ($user_courses != '') {
            foreach ($user_courses as $key => $value) {
                $user_coursecodes[] = $value['code'];
            }
        }

        if (api_is_drh()) {
            $courses = CourseManager::get_courses_followed_by_drh(api_get_user_id());
            foreach ($courses as $course) {
                $user_coursecodes[] = $course['code'];
            }
        }

        $data['user_coursecodes'] = $user_coursecodes;
        $data['action'] = $action;
        $data['message'] = $message;
        $data['content'] = $content;
        $data['error'] = $error;
        $data['catalogShowCoursesSessions'] = 0;
        $showCoursesSessions = (int) api_get_setting('catalog_show_courses_sessions');
        if ($showCoursesSessions > 0) {
            $data['catalogShowCoursesSessions'] = $showCoursesSessions;
        }

        $this->view->set_data($data);
        $this->view->set_layout('layout');
        $this->view->set_template('courses_categories');
        $this->view->render();
    }

    /**
     * @param string $search_term
     * @param string $message
     * @param string $error
     * @param string $content
     * @param array  $limit
     * @param bool   $justVisible Whether to search only in courses visibles in the catalogue
     */
    public function search_courses(
        $search_term,
        $message = '',
        $error = '',
        $content = null,
        $limit = [],
        $justVisible = false
    ) {
        $data = [];
        $limit = !empty($limit) ? $limit : self::getLimitArray();
        $browse_course_categories = CoursesAndSessionsCatalog::getCourseCategories();
        $data['countCoursesInCategory'] = CourseCategory::countCoursesInCategory(
            'ALL',
            $search_term
        );
        $data['browse_courses_in_category'] = CoursesAndSessionsCatalog::search_courses(
            $search_term,
            $limit,
            $justVisible
        );
        $data['browse_course_categories'] = $browse_course_categories;
        $data['search_term'] = Security::remove_XSS($search_term); //filter before showing in template

        // getting all the courses to which the user is subscribed to
        $curr_user_id = api_get_user_id();
        $user_courses = $this->model->get_courses_of_user($curr_user_id);
        $user_coursecodes = [];

        // we need only the course codes as these will be used to match against the courses of the category
        if ($user_courses != '') {
            foreach ($user_courses as $value) {
                $user_coursecodes[] = $value['code'];
            }
        }

        $data['user_coursecodes'] = $user_coursecodes;
        $data['message'] = $message;
        $data['content'] = $content;
        $data['error'] = $error;
        $data['action'] = 'display_courses';

        // render to the view
        $this->view->set_data($data);
        $this->view->set_layout('catalog_layout');
        $this->view->set_template('courses_categories');
        $this->view->render();
    }

    /**
     * Create a category
     * render to listing view.
     *
     * @param string $title
     */
    public function addCourseCategory($title)
    {
        $result = $this->model->store_course_category($title);
        if ($result) {
            Display::addFlash(
                Display::return_message(get_lang('CourseCategoryStored'))
            );
        } else {
            Display::addFlash(
                Display::return_message(
                    get_lang('ACourseCategoryWithThisNameAlreadyExists'),
                    'error'
                )
            );
        }
        header('Location: '.api_get_path(WEB_CODE_PATH).'auth/courses.php?action=sortmycourses');
        exit;
    }

    /**
     * Change course category
     * render to listing view.
     *
     * @param string $course_code
     * @param int    $category_id
     */
    public function change_course_category($course_code, $category_id)
    {
        $courseInfo = api_get_course_info($course_code);
        $courseId = $courseInfo['real_id'];

        $result = $this->model->updateCourseCategory($courseId, $category_id);
        if ($result) {
            Display::addFlash(
                Display::return_message(get_lang('EditCourseCategorySucces'))
            );
        }
        $action = 'sortmycourses';
        $this->courseList($action);
    }

    /**
     * Move up/down courses inside a category
     * render to listing view.
     *
     * @param string $move        move to up or down
     * @param string $course_code
     * @param int    $category_id Category id
     */
    public function move_course($move, $course_code, $category_id)
    {
        $result = $this->model->move_course($move, $course_code, $category_id);
        if ($result) {
            Display::addFlash(
                Display::return_message(get_lang('CourseSortingDone'))
            );
        }
        $action = 'sortmycourses';
        $this->courseList($action);
    }

    /**
     * Move up/down categories
     * render to listing view.
     *
     * @param string $move        move to up or down
     * @param int    $category_id Category id
     */
    public function move_category($move, $category_id)
    {
        $result = $this->model->move_category($move, $category_id);
        if ($result) {
            Display::addFlash(
                Display::return_message(get_lang('CategorySortingDone'))
            );
        }
        $action = 'sortmycourses';
        $this->courseList($action);
    }

    /**
     * Edit course category
     * render to listing view.
     *
     * @param string $title    Category title
     * @param int    $category Category id
     */
    public function edit_course_category($title, $category)
    {
        $result = $this->model->store_edit_course_category($title, $category);
        if ($result) {
            Display::addFlash(
                Display::return_message(get_lang('CourseCategoryEditStored'))
            );
        }
        $action = 'sortmycourses';
        $this->courseList($action);
    }

    /**
     * Delete a course category
     * render to listing view.
     *
     * @param int    Category id
     */
    public function delete_course_category($category_id)
    {
        $result = $this->model->delete_course_category($category_id);
        if ($result) {
            Display::addFlash(
                Display::return_message(get_lang('CourseCategoryDeleted'))
            );
        }
        $action = 'sortmycourses';
        $this->courseList($action);
    }

    /**
     * Unsubscribe user from a course
     * render to listing view.
     *
     * @param string $course_code
     * @param string $search_term
     * @param string $category_code
     */
    public function unsubscribe_user_from_course(
        $course_code,
        $search_term = null,
        $category_code = null
    ) {
        $result = $this->model->remove_user_from_course($course_code);
        $message = '';
        $error = '';

        if ($result) {
            Display::addFlash(
                Display::return_message(get_lang('YouAreNowUnsubscribed'))
            );
        }

        if (!empty($search_term)) {
            CoursesAndSessionsCatalog::search_courses($search_term, $message, $error);
        } else {
            $this->courses_categories(
                'subcribe',
                $category_code,
                $message,
                $error
            );
        }
    }

    /**
     * Get the html block for courses categories.
     *
     * @param string $code        Current category code
     * @param bool   $hiddenLinks Whether hidden links
     * @param array  $limit
     *
     * @return string The HTML block
     */
    public function getCoursesCategoriesBlock(
        $code = null,
        $hiddenLinks = false,
        $limit = null
    ) {
        $categories = CoursesAndSessionsCatalog::getCourseCategories();
        $html = '';
        if (!empty($categories)) {
            $action = 'display_courses';
            foreach ($categories[0] as $category) {
                $categoryName = $category['name'];
                $categoryCode = $category['code'];
                $categoryCourses = $category['count_courses'];

                $html .= '<li>';

                $categoryLink = CourseCategory::getCourseCategoryUrl(
                    1,
                    $limit['length'],
                    $categoryCode,
                    $hiddenLinks,
                    $action
                );

                if ($code == $categoryCode) {
                    $html .= '<strong>';
                    $html .= "$categoryName ($categoryCourses)";
                    $html .= '</strong>';
                } else {
                    if (!empty($categoryCourses)) {
                        $html .= '<a href="'.$categoryLink.'">';
                        $html .= "$categoryName ($categoryCourses)";
                        $html .= '</a>';
                    } else {
                        $html .= "$categoryName ($categoryCourses)";
                    }
                }

                if (!empty($categories[$categoryCode])) {
                    $html .= '<ul class="nav nav-list">';

                    foreach ($categories[$categoryCode] as $subCategory1) {
                        $subCategory1Name = $subCategory1['name'];
                        $subCategory1Code = $subCategory1['code'];
                        $subCategory1Courses = $subCategory1['count_courses'];
                        $html .= '<li>';
                        if ($code == $subCategory1Code) {
                            $html .= "<strong>$subCategory1Name ($subCategory1Courses)</strong>";
                        } else {
                            $html .= '<a href="'.$categoryLink.'">';
                            $html .= "$subCategory1Name ($subCategory1Courses)";
                            $html .= '</a>';
                        }

                        if (!empty($categories[$subCategory1Code])) {
                            $html .= '<ul class="nav nav-list">';

                            foreach ($categories[$subCategory1Code] as $subCategory2) {
                                $subCategory2Name = $subCategory2['name'];
                                $subCategory2Code = $subCategory2['code'];
                                $subCategory2Courses = $subCategory2['count_courses'];

                                $html .= '<li>';

                                if ($code == $subCategory2Code) {
                                    $html .= "<strong>$subCategory2Name ($subCategory2Courses)</strong>";
                                } else {
                                    $html .= '<a href="'.$categoryLink.'">';
                                    $html .= "$subCategory2Name ($subCategory2Courses)";
                                    $html .= '</a>';
                                }

                                if (!empty($categories[$subCategory2Code])) {
                                    $html .= '<ul class="nav nav-list">';

                                    foreach ($categories[$subCategory2Code] as $subCategory3) {
                                        $subCategory3Name = $subCategory3['name'];
                                        $subCategory3Code = $subCategory3['code'];
                                        $subCategory3Courses = $subCategory3['count_courses'];

                                        $html .= '<li>';

                                        if ($code == $subCategory3Code) {
                                            $html .= "<strong>$subCategory3Name ($subCategory3Courses)</strong>";
                                        } else {
                                            $html .= '<a href="'.$categoryLink.'">';
                                            $html .= "$subCategory3Name ($subCategory3Courses)";
                                            $html .= '</a>';
                                        }
                                        $html .= '</li>';
                                    }
                                    $html .= '</ul>';
                                }
                                $html .= '</li>';
                            }
                            $html .= '</ul>';
                        }
                        $html .= '</li>';
                    }
                    $html .= '</ul>';
                }
                $html .= '</li>';
            }
        }

        return $html;
    }

    /**
     * Get a HTML button for subscribe to session.
     *
     * @param int    $sessionId         The session ID
     * @param string $sessionName       The session name
     * @param bool   $checkRequirements Optional.
     *                                  Whether the session has requirement. Default is false
     * @param bool   $includeText       Optional. Whether show the text in button
     * @param bool   $btnBing
     *
     * @return string The button HTML
     */
    public function getRegisteredInSessionButton(
        $sessionId,
        $sessionName,
        $checkRequirements = false,
        $includeText = false,
        $btnBing = false
    ) {
        if ($btnBing) {
            $btnBing = 'btn-lg btn-block';
        } else {
            $btnBing = 'btn-sm';
        }
        if ($checkRequirements) {
            $url = api_get_path(WEB_AJAX_PATH);
            $url .= 'sequence.ajax.php?';
            $url .= http_build_query([
                'a' => 'get_requirements',
                'id' => intval($sessionId),
                'type' => SequenceResource::SESSION_TYPE,
            ]);

            return Display::toolbarButton(
                get_lang('CheckRequirements'),
                $url,
                'shield',
                'info',
                [
                    'class' => $btnBing.' ajax',
                    'data-title' => get_lang('CheckRequirements'),
                    'data-size' => 'md',
                    'title' => get_lang('CheckRequirements'),
                ],
                $includeText
            );
        }

        $catalogSessionAutoSubscriptionAllowed = false;
        if (api_get_setting('catalog_allow_session_auto_subscription') === 'true') {
            $catalogSessionAutoSubscriptionAllowed = true;
        }

        $url = api_get_path(WEB_CODE_PATH);

        if ($catalogSessionAutoSubscriptionAllowed) {
            $url .= 'auth/courses.php?';
            $url .= http_build_query([
                'action' => 'subscribe_to_session',
                'session_id' => intval($sessionId),
            ]);

            $result = Display::toolbarButton(
                get_lang('Subscribe'),
                $url,
                'pencil',
                'primary',
                [
                    'class' => $btnBing.' ajax',
                    'data-title' => get_lang('AreYouSureToSubscribe'),
                    'data-size' => 'md',
                    'title' => get_lang('Subscribe'),
                ],
                $includeText
            );
        } else {
            $url .= 'inc/email_editor.php?';
            $url .= http_build_query([
                'action' => 'subscribe_me_to_session',
                'session' => Security::remove_XSS($sessionName),
            ]);

            $result = Display::toolbarButton(
                get_lang('SubscribeToSessionRequest'),
                $url,
                'pencil',
                'primary',
                ['class' => $btnBing],
                $includeText
            );
        }

        $hook = HookResubscribe::create();
        if (!empty($hook)) {
            $hook->setEventData([
                'session_id' => intval($sessionId),
            ]);
            try {
                $hook->notifyResubscribe(HOOK_EVENT_TYPE_PRE);
            } catch (Exception $exception) {
                $result = $exception->getMessage();
            }
        }

        return $result;
    }

    /**
     * Generate a label if the user has been  registered in session.
     *
     * @return string The label
     */
    public function getAlreadyRegisteredInSessionLabel()
    {
        $icon = '<em class="fa fa-graduation-cap"></em>';

        return Display::div(
            $icon,
            [
                'class' => 'btn btn-default btn-sm registered',
                'title' => get_lang("AlreadyRegisteredToSession"),
            ]
        );
    }

    /**
     * Get a icon for a session.
     *
     * @param string $sessionName The session name
     *
     * @return string The icon
     */
    public function getSessionIcon($sessionName)
    {
        return Display::return_icon(
            'window_list.png',
            $sessionName,
            null,
            ICON_SIZE_MEDIUM
        );
    }

    /**
     * Return Session Catalogue rendered view.
     *
     * @param string $action
     * @param string $nameTools
     * @param array  $limit
     */
    public function sessionsList($action, $nameTools, $limit = [])
    {
        $date = isset($_POST['date']) ? $_POST['date'] : date('Y-m-d');
        $hiddenLinks = isset($_GET['hidden_links']) ? intval($_GET['hidden_links']) == 1 : false;
        $limit = isset($limit) ? $limit : self::getLimitArray();
        $countSessions = SessionManager::countSessionsByEndDate($date);
        $sessions = CoursesAndSessionsCatalog::browseSessions($date, $limit);

        $pageTotal = intval(intval($countSessions) / $limit['length']);
        // Do NOT show pagination if only one page or less
        $cataloguePagination = $pageTotal > 1 ? CourseCategory::getCatalogPagination($limit['current'], $limit['length'], $pageTotal) : '';
        $sessionsBlocks = $this->getFormattedSessionsBlock($sessions);

        // Get session search catalogue URL
        $courseUrl = CourseCategory::getCourseCategoryUrl(
            1,
            $limit['length'],
            null,
            0,
            'subscribe'
        );

        $tpl = new Template();
        $tpl->assign('show_courses', CoursesAndSessionsCatalog::showCourses());
        $tpl->assign('show_sessions', CoursesAndSessionsCatalog::showSessions());
        $tpl->assign('show_tutor', api_get_setting('show_session_coach') === 'true' ? true : false);
        $tpl->assign('course_url', $courseUrl);
        $tpl->assign('catalog_pagination', $cataloguePagination);
        $tpl->assign('hidden_links', $hiddenLinks);
        $tpl->assign('search_token', Security::get_token());
        $tpl->assign('search_date', $date);
        $tpl->assign('web_session_courses_ajax_url', api_get_path(WEB_AJAX_PATH).'course.ajax.php');
        $tpl->assign('sessions', $sessionsBlocks);
        $tpl->assign('already_subscribed_label', $this->getAlreadyRegisteredInSessionLabel());

        $layout = $tpl->get_template('auth/session_catalog.html.twig');
        $content = $tpl->fetch($layout);
        $tpl->assign('content', $content);
        $tpl->display_one_col_template();
    }

    /**
     * Show the Session Catalogue with filtered session by course tags.
     *
     * @param array $limit Limit info
     */
    public function sessionsListByCoursesTag(array $limit)
    {
        $searchTag = isset($_POST['search_tag']) ? $_POST['search_tag'] : null;
        $searchDate = isset($_POST['date']) ? $_POST['date'] : date('Y-m-d');
        $hiddenLinks = isset($_GET['hidden_links']) ? intval($_GET['hidden_links']) == 1 : false;
        $courseUrl = CourseCategory::getCourseCategoryUrl(
            1,
            $limit['length'],
            null,
            0,
            'subscribe'
        );

        $sessions = CoursesAndSessionsCatalog::browseSessionsByTags($searchTag, $limit);
        $sessionsBlocks = $this->getFormattedSessionsBlock($sessions);

        $tpl = new Template();
        $tpl->assign('show_courses', CoursesAndSessionsCatalog::showCourses());
        $tpl->assign('show_sessions', CoursesAndSessionsCatalog::showSessions());
        $tpl->assign('show_tutor', (api_get_setting('show_session_coach') === 'true' ? true : false));
        $tpl->assign('course_url', $courseUrl);
        $tpl->assign('already_subscribed_label', $this->getAlreadyRegisteredInSessionLabel());
        $tpl->assign('hidden_links', $hiddenLinks);
        $tpl->assign('search_token', Security::get_token());
        $tpl->assign('search_date', Security::remove_XSS($searchDate));
        $tpl->assign('search_tag', Security::remove_XSS($searchTag));
        $tpl->assign('sessions', $sessionsBlocks);

        $contentTemplate = $tpl->get_template('auth/session_catalog.tpl');

        $tpl->display($contentTemplate);
    }

    /**
     * Show the Session Catalogue with filtered session by a query term.
     *
     * @param array $limit
     */
    public function sessionListBySearch(array $limit)
    {
        $q = isset($_REQUEST['q']) ? Security::remove_XSS($_REQUEST['q']) : null;
        $hiddenLinks = isset($_GET['hidden_links']) ? (int) $_GET['hidden_links'] == 1 : false;
        $courseUrl = CourseCategory::getCourseCategoryUrl(
            1,
            $limit['length'],
            null,
            0,
            'subscribe'
        );
        $searchDate = isset($_POST['date']) ? $_POST['date'] : date('Y-m-d');

        $sessions = CoursesAndSessionsCatalog::browseSessionsBySearch($q, $limit);
        $sessionsBlocks = $this->getFormattedSessionsBlock($sessions);

        $tpl = new Template();
        $tpl->assign('show_courses', CoursesAndSessionsCatalog::showCourses());
        $tpl->assign('show_sessions', CoursesAndSessionsCatalog::showSessions());
        $tpl->assign('show_tutor', api_get_setting('show_session_coach') === 'true' ? true : false);
        $tpl->assign('course_url', $courseUrl);
        $tpl->assign('already_subscribed_label', $this->getAlreadyRegisteredInSessionLabel());
        $tpl->assign('hidden_links', $hiddenLinks);
        $tpl->assign('search_token', Security::get_token());
        $tpl->assign('search_date', Security::remove_XSS($searchDate));
        $tpl->assign('search_tag', Security::remove_XSS($q));
        $tpl->assign('sessions', $sessionsBlocks);

        $contentTemplate = $tpl->get_template('auth/session_catalog.tpl');

        $tpl->display($contentTemplate);
    }

    /**
     * @return array
     */
    public static function getLimitArray()
    {
        $pageCurrent = isset($_REQUEST['pageCurrent']) ? (int) $_GET['pageCurrent'] : 1;
        $pageLength = isset($_REQUEST['pageLength']) ? (int) $_GET['pageLength'] : CoursesAndSessionsCatalog::PAGE_LENGTH;

        return [
            'start' => ($pageCurrent - 1) * $pageLength,
            'current' => $pageCurrent,
            'length' => $pageLength,
        ];
    }

    /**
     * Get the formatted data for sessions block to be displayed on Session Catalog page.
     *
     * @param array $sessions The session list
     *
     * @return array
     */
    private function getFormattedSessionsBlock(array $sessions)
    {
        $extraFieldValue = new ExtraFieldValue('session');
        $userId = api_get_user_id();
        $sessionsBlocks = [];
        $entityManager = Database::getManager();
        $sessionRelCourseRepo = $entityManager->getRepository('ChamiloCoreBundle:SessionRelCourse');
        $extraFieldRepo = $entityManager->getRepository('ChamiloCoreBundle:ExtraField');
        $extraFieldRelTagRepo = $entityManager->getRepository('ChamiloCoreBundle:ExtraFieldRelTag');

        $tagsField = $extraFieldRepo->findOneBy([
            'extraFieldType' => Chamilo\CoreBundle\Entity\ExtraField::COURSE_FIELD_TYPE,
            'variable' => 'tags',
        ]);

        /** @var \Chamilo\CoreBundle\Entity\Session $session */
        foreach ($sessions as $session) {
            $sessionDates = SessionManager::parseSessionDates([
                'display_start_date' => $session->getDisplayStartDate(),
                'display_end_date' => $session->getDisplayEndDate(),
                'access_start_date' => $session->getAccessStartDate(),
                'access_end_date' => $session->getAccessEndDate(),
                'coach_access_start_date' => $session->getCoachAccessStartDate(),
                'coach_access_end_date' => $session->getCoachAccessEndDate(),
            ]);

            $imageField = $extraFieldValue->get_values_by_handler_and_field_variable(
                $session->getId(),
                'image'
            );
            $sessionCourseTags = [];
            if (!is_null($tagsField)) {
                $sessionRelCourses = $sessionRelCourseRepo->findBy([
                    'session' => $session,
                ]);
                /** @var SessionRelCourse $sessionRelCourse */
                foreach ($sessionRelCourses as $sessionRelCourse) {
                    $courseTags = $extraFieldRelTagRepo->getTags(
                        $tagsField,
                        $sessionRelCourse->getCourse()->getId()
                    );
                    /** @var Tag $tag */
                    foreach ($courseTags as $tag) {
                        $sessionCourseTags[] = $tag->getTag();
                    }
                }
            }

            if (!empty($sessionCourseTags)) {
                $sessionCourseTags = array_unique($sessionCourseTags);
            }

            /** @var SequenceRepository $repo */
            $repo = $entityManager->getRepository('ChamiloCoreBundle:SequenceResource');
            $sequences = $repo->getRequirementsAndDependenciesWithinSequences(
                $session->getId(),
                SequenceResource::SESSION_TYPE
            );

            $hasRequirements = false;
            foreach ($sequences['sequences'] as $sequence) {
                if (count($sequence['requirements']) === 0) {
                    continue;
                }
                $hasRequirements = true;
                break;
            }
            $cat = $session->getCategory();
            if (empty($cat)) {
                $cat = null;
                $catName = '';
            } else {
                $catName = $cat->getName();
            }

            $generalCoach = $session->getGeneralCoach();
            $coachId = $generalCoach ? $generalCoach->getId() : 0;
            $coachName = $generalCoach ? UserManager::formatUserFullName($session->getGeneralCoach()) : '';

            $actions = null;
            if (api_is_platform_admin()) {
                $actions = api_get_path(WEB_CODE_PATH).'session/resume_session.php?id_session='.$session->getId();
            }

            $plugin = \BuyCoursesPlugin::create();
            $isThisSessionOnSale = $plugin->getBuyCoursePluginPrice($session);

            $sessionsBlock = [
                'id' => $session->getId(),
                'name' => $session->getName(),
                'image' => isset($imageField['value']) ? $imageField['value'] : null,
                'nbr_courses' => $session->getNbrCourses(),
                'nbr_users' => $session->getNbrUsers(),
                'coach_id' => $coachId,
                'coach_url' => $generalCoach
                    ? api_get_path(WEB_AJAX_PATH).'user_manager.ajax.php?a=get_user_popup&user_id='.$coachId
                    : '',
                'coach_name' => $coachName,
                'coach_avatar' => UserManager::getUserPicture(
                    $coachId,
                    USER_IMAGE_SIZE_SMALL
                ),
                'is_subscribed' => SessionManager::isUserSubscribedAsStudent(
                    $session->getId(),
                    $userId
                ),
                'icon' => $this->getSessionIcon($session->getName()),
                'date' => $sessionDates['display'],
                'price' => !empty($isThisSessionOnSale['html']) ? $isThisSessionOnSale['html'] : '',
                'subscribe_button' => isset($isThisSessionOnSale['buy_button']) ? $isThisSessionOnSale['buy_button'] : $this->getRegisteredInSessionButton(
                    $session->getId(),
                    $session->getName(),
                    $hasRequirements
                ),
                'show_description' => $session->getShowDescription(),
                'description' => $session->getDescription(),
                'category' => $catName,
                'tags' => $sessionCourseTags,
                'edit_actions' => $actions,
                'duration' => SessionManager::getDayLeftInSession(
                    ['id' => $session->getId(), 'duration' => $session->getDuration()],
                    $userId
                ),
            ];

            $sessionsBlock = array_merge($sessionsBlock, $sequences);
            $sessionsBlocks[] = $sessionsBlock;
        }

        return $sessionsBlocks;
    }
}
