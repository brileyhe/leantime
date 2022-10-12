<?php
/**
 * Generic / Tempate of canvas controller
 */
namespace leantime\library\canvas {

    use leantime\core;
    use leantime\domain\repositories;
    use leantime\domain\services;

    class controllerCanvas
    {

        /**
         * Constant that must be redefined
         */
        protected const CANVAS_NAME = 'xx';
        protected const CANVAS_TEMPLATE = '';

        /**
         * run - display template and edit data
         *
         * @access public
         */
        public function run()
        {

            $tpl = new core\template();
            $canvasRepoName = "leantime\\domain\\repositories\\".static::CANVAS_NAME."canvas";
            $canvasRepo = new $canvasRepoName();
            $projectService = new services\projects();
            $language = new core\language();

            $allCanvas = $canvasRepo->getAllCanvas($_SESSION['currentProject']);

            if (isset($_SESSION["current".strtoupper(static::CANVAS_NAME)."Canvas"])) {
                $currentCanvasId = $_SESSION["current".strtoupper(static::CANVAS_NAME)."Canvas"];
            }else{
                $currentCanvasId = -1;
                $_SESSION["current".strtoupper(static::CANVAS_NAME)."Canvas"] = "";
            }

            if (count($allCanvas) > 0 && $_SESSION["current".strtoupper(static::CANVAS_NAME)."Canvas"] == '') {
                $currentCanvasId = $allCanvas[0]['id'];
                $_SESSION["current".strtoupper(static::CANVAS_NAME)."Canvas"] = $currentCanvasId;
            }

            if (isset($_GET["id"]) === true) {
                $currentCanvasId = (int)$_GET["id"];
                $_SESSION["current".strtoupper(static::CANVAS_NAME)."Canvas"] = $currentCanvasId;
            }

            if (isset($_POST["searchCanvas"]) === true) {
                $currentCanvasId = (int)$_POST["searchCanvas"];
                $_SESSION["current".strtoupper(static::CANVAS_NAME)."Canvas"] = $currentCanvasId;
                $tpl->redirect(BASE_URL."/".static::CANVAS_NAME."canvas/".static::CANVAS_TEMPLATE.static::CANVAS_NAME."Canvas/");
            }

            // Add Canvas
            if (isset($_POST["newCanvas"]) === true) {

                if (isset($_POST['canvastitle']) === true && !empty($_POST['canvastitle'])) {

                  if (!$canvasRepo->existCanvas($_SESSION["currentProject"], $_POST['canvastitle'])) {
                        $values = ["title" => $_POST['canvastitle'], 
                                   "author" => $_SESSION['userdata']["id"], 
                                   "projectId" => $_SESSION["currentProject"]];
                        $currentCanvasId = $canvasRepo->addCanvas($values);
                        $allCanvas = $canvasRepo->getAllCanvas($_SESSION['currentProject']);
                        
                        $mailer = new core\mailer();
                        $projectService = new services\projects();
                        $users = $projectService->getUsersToNotify($_SESSION['currentProject']);
                        
                        $mailer->setSubject($language->__("notifications.new_canvas_created"));
                        
                        $actual_link = CURRENT_URL;
                        $message = sprintf($language->__("email_notifications.canvas_created_message"),
                                           $_SESSION["userdata"]["name"], "<a href='" . $actual_link . "'>" . $values['title'] . "</a>");
                        $mailer->setHtml($message);
                        
                        // New queuing messaging system
                        $queue = new repositories\queue();
                        $queue->queueMessageToUsers($users, $message, $language->__("notifications.new_canvas_created"),
                                                    $_SESSION["currentProject"]);
                        
                        $tpl->setNotification($language->__("notifications.new_canvas_created"), 'success');
                        
                        $_SESSION["current".strtoupper(static::CANVAS_NAME)."Canvas"] = $currentCanvasId;
                        $tpl->redirect(BASE_URL."/".static::CANVAS_NAME."canvas/".static::CANVAS_TEMPLATE.static::CANVAS_NAME."Canvas/");

                    } else {
                        $tpl->setNotification($language->__("notification.boeard_exists"), 'error');
                    }

                } else {
                    $tpl->setNotification($language->__("notification.please_enter_title"), 'error');
                }

            }

            // Edit Canvas
            if (isset($_POST["editCanvas"]) === true && $currentCanvasId > 0) {

                if (isset($_POST['canvastitle']) === true && !empty($_POST['canvastitle'])) {

                    if (!$canvasRepo->existCanvas($_SESSION["currentProject"], $_POST['canvastitle'])) {
                        $values = array("title" => $_POST['canvastitle'], "id" => $currentCanvasId);
                        $currentCanvasId = $canvasRepo->updateCanvas($values);

                        $tpl->setNotification($language->__("notification.board_edited"), "success");
                        $tpl->redirect(BASE_URL."/".static::CANVAS_NAME."canvas/".static::CANVAS_TEMPLATE.static::CANVAS_NAME."Canvas/");

                    } else {
                        $tpl->setNotification($language->__("notification.boeard_exists"), 'error');
                    }

                } else {
                    $tpl->setNotification($language->__("notification.please_enter_title"), 'error');
                }

            }

            // Clone canvas
            if (isset($_POST["cloneCanvas"]) === true && $currentCanvasId > 0) {

                if (isset($_POST['canvastitle']) === true && !empty($_POST['canvastitle'])) {

                    if (!$canvasRepo->existCanvas($_SESSION["currentProject"], $_POST['canvastitle'])) {
                        
                        $currentCanvasId = $canvasRepo->copyCanvas($_SESSION["currentProject"], $currentCanvasId,
                                                                     $_SESSION['userdata']["id"], $_POST['canvastitle']);
                        $allCanvas = $canvasRepo->getAllCanvas($_SESSION['currentProject']);
                        
                        $tpl->setNotification($language->__("notification.board_copied"), "success");
                        
                        $_SESSION["current".strtoupper(static::CANVAS_NAME)."Canvas"] = $currentCanvasId;
                        $tpl->redirect(BASE_URL."/".static::CANVAS_NAME."canvas/".static::CANVAS_TEMPLATE.static::CANVAS_NAME."Canvas/");

                    } else {
                        $tpl->setNotification($language->__("notification.board_exists"), 'error');
                    }

                } else {
                    $tpl->setNotification($language->__("notification.please_enter_title"), 'error');
                }

            }

            $_SESSION[self::CANVAS_NAME]['template'] = static::CANVAS_TEMPLATE;
            $tpl->assign('currentCanvas', $currentCanvasId);
            $tpl->assign('statusLabels', $canvasRepo->statusLabels);
            $tpl->assign('canvasLabels', $canvasRepo->canvasTypes);
            $tpl->assign('allCanvas', $allCanvas);
            $tpl->assign('canvasItems', $canvasRepo->getCanvasItemsById($currentCanvasId));
            $tpl->assign('users', $projectService->getUsersAssignedToProject($_SESSION["currentProject"]));

            if (isset($_GET["raw"]) === false) {
                $tpl->display(static::CANVAS_NAME."canvas.".static::CANVAS_TEMPLATE.static::CANVAS_NAME."Canvas");
            }
        }

    }

}
