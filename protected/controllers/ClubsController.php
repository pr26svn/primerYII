<?php

class ClubsController extends MainController
{
    public function behaviors()
    {
        $parent = parent::behaviors();
        return array_merge($parent, [
            'InlineWidgetsBehavior'=>array(
                'class'=>'application.components.DInlineWidgetsBehavior',
                'location'=>'application.widgets.*',
                'startBlock'=> '{{w:',
                'endBlock'=> '}}',
               	'widgets'=>['application.widgets.GalleryWidget'],
            ),
        ]);
    }

    public $clubInfo;

    public function beforeAction($action = null) {
        parent::beforeAction($action);
        $alias = Yii::app()->request->getQuery('alias');
        $action = Yii::app()->controller->action->id;

		if ($action == 'index')
			$this->layout = "//layouts/clubs";
        if(!empty($alias)) {
            $this->layout = "//layouts/club";
            $this->clubInfo = $this->getClubInfo(Yii::app()->request->getQuery('alias'));
            if($this->clubInfo){
                if($action != 'clubSiteMap')
                    Location::changeLocation($this->clubInfo->id_city, $this->clubInfo->id);
                if($this->clubInfo['only_desc'] && $action != 'clubIndex'){
                    $this->redirect(Yii::app()->createUrl($alias), 301);
                }
            }
            else {
                throw new CHttpException(404, 'Страница данного клуба не найдена');
            }
        }

        $this->pageNumber = isset($_GET['params']['page']) ? $_GET['params']['page'] : null;

        return true;
    }

    /* Список и выбор клубов */
	public function actionIndex(){
		
        if(isset($_GET['params']))
            throw new CHttpException(404, 'Такой страницы не существует');

        $model_group = Club::model()->findAll(array('group' => '1'));
		//print_r($model_group); Yii::app()->end();
        $model = Club::model()->findAll();

        $citys_chunk = array();
        if($model_group) {
            $diff = round((sizeof($model_group) / 3));
            $diff = ($diff < 1) ? 1 : $diff;
            $citys_chunk = array_chunk($model_group, $diff);
        }

		$this->render('index', array(
            'model_group' => $citys_chunk,
            'model' => $model,
        ));

	}

    public function actionClubGoodChoice() {
        if(isset($_GET['params']) || !$this->clubInfo['comming_soon'])
            throw new CHttpException(404, 'Такой страницы не существует');

        $this->render('_getPriceFormSuccessful', null);
    }


    /* Главная страница клуба */
    public function actionClubIndex() {

        if(isset($_GET['params']))
            throw new CHttpException(404, 'Такой страницы не существует');

			$date = Yii::app()->request->getQuery('date');
			$date = Tools::getTimeFirstDayOfWeek($date);
			$date_n = strtotime('+7 days', strtotime($date));
			$date_b = strtotime('-7 days', strtotime($date));
			$date_display = $date .' &mdash; '. date('d.m.Y', strtotime('+6 days', strtotime($date)));

			$responce = Schedule::model()->findById($this->clubInfo['id'], $date);
		
			$trainers = ClubPersonal::model()->findById($this->clubInfo['id']);
		
			$limit_news = 5;
			$pagination = Pagination::paginationLimit($limit_news, $this->pageNumber);

			$news_all = News::model()->findNewsById($this->clubInfo['id'], $pagination[0], $pagination[1]);
		
			if($responce) {
				foreach($responce as $room => $schedule) {
					for($i = $this->clubInfo['weekdays_start_h']; $i <= $this->clubInfo['weekdays_end_h']; $i++) {
						if($i < 10) {
							$i = "0".(int) $i;
						}
						if(!isset($responce[$room][$i])) {
							$responce[$room][$i] = array();
						}
					}
					ksort($responce[$room]);
				}
			}
			
			$rooms = $responce;

           /* $news = News::model()->findNewsById($this->clubInfo['id'], 0, 3);
            $news_all = News::model()->findNewsById($this->clubInfo['id'], 0, 9);*/
		
            $news = TextNews::model()->newsClub($this->clubInfo['id'], 3);
            $news_all = TextNews::model()->newsClub($this->clubInfo['id'], 9);
			
			$model = new FreeTrainingForm('withCaptcha');

			if(Yii::app()->request->getIsAjaxRequest() && Yii::app()->request->getPost('ajax') == 'freeTrainingForm'){
				$model->setScenario('ajax');
				echo CActiveForm::validate($model);
				Yii::app()->end();
			}

			if(isset($_POST['FreeTrainingForm']))
			{
				$model->attributes=$_POST['FreeTrainingForm'];
				if($model->save())
				{
					$this->redirect('getFreeTraining/goodChoice');
				}
			}

			$id_club = Yii::app()->request->getQuery('id');
			$id_club = (!$id_club) ? Location::getIdClub() : $id_club;
			if($id_club) {
				$responce = Club::model()->findById($id_club);
				if($responce){
					$model->city = $responce['id_city'];
					$model->club = $responce['id'];
				} else {
					//seo 1 link
	//                $this->redirect('clubs', true, 301);
				}
			}

			$responce = Club::model()->findAll();
			$clubs = array();
			if($responce){
				$resp = array();
				foreach($responce as $val) {
					if($val->has_coupon)
						$resp[] = $val;
				}
				$clubs = CHtml::listData($resp,'id_city','title');
			}

			$tmp = $clubs;
			$tmp = array_keys($tmp);
			$id_city = array_shift($tmp);
			if($model->hasErrors() || $id_club){
				$id_city = $model->city;
			}

			$responce = Club::model()->findByIdCity($id_city);
			$addresses = array();
			if($responce){
				$addresses = CHtml::listData($responce,'id','address');
			}
		
            $this->render('_main', array(
                'news' => $news,
                'news_all' => $news_all,
				'trainers' => $trainers,
				'rooms' => $rooms,
				'date' => $date,
				'date_n' => $date_n,
				'date_b' => $date_b,
				'date_display' => $date_display,
				'model'=>$model,
				'clubs'=>$clubs,
				'addresses'=>$addresses,
            ));

       
    }

    private function increaseTime(&$time, $start) {
        if($time != $start) {
            $time++;
        }
    }

    /* Расписание */
    public function actionClubSchedule() {

        if(isset($_GET['params']))
            throw new CHttpException(404, 'Такой страницы не существует');

        $this->pageCanonical = Yii::app()->getBaseUrl(true) .'/'. $this->clubInfo['alias'] .'/schedule';

        $date = Yii::app()->request->getQuery('date');
        $date = Tools::getTimeFirstDayOfWeek($date);
        $date_n = strtotime('+7 days', strtotime($date));
        $date_b = strtotime('-7 days', strtotime($date));
        $date_display = $date .' &mdash; '. date('d.m.Y', strtotime('+6 days', strtotime($date)));

        $responce = Schedule::model()->findById($this->clubInfo['id'], $date);
 
        if($responce) {
            foreach($responce as $room => $schedule) {
                for($i = $this->clubInfo['weekdays_start_h']; $i <= $this->clubInfo['weekdays_end_h']; $i++) {
                    if($i < 10) {
                        $i = "0".(int) $i;
                    }
                    if(!isset($responce[$room][$i])) {
                        $responce[$room][$i] = array();
                    }
                }
                ksort($responce[$room]);
            }
        }

        $this->render('_schedule', array(
            'rooms' => $responce,
            'date' => $date,
            'date_n' => $date_n,
            'date_b' => $date_b,
            'date_display' => $date_display,
        ));
    }

    /* Тренерский состав */
    public function actionClubCoaches() {
        if(isset($_GET['params']))
            throw new CHttpException(404, 'Такой страницы не существует');
        $responce = ClubPersonal::model()->findById($this->clubInfo['id']);
        $this->render('_coaches', array(
            'personal' => $responce,
        ));
    }

    //супергерои клуба
    public function actionClubSuperheroes() {
        if(isset($_GET['params']))
            throw new CHttpException(404, 'Такой страницы не существует');
        $responce = Superhero::model()->findById($this->clubInfo['id']);
        $comments = array();
        foreach ($responce as $key => $val)
        {
            $comments[] = array('id'=>$val['id'],'comment'=>$val['comment']);
        }
        $this->render('_superheroes', array(
            'superheroes' => $responce,
            'comments'=> $comments,
        ));
    }

    //TODO добавить в сайт мап услуги
    /* Услуги клуба */
    public function actionClubServices() {
        $alias = Yii::app()->request->getQuery('alias_service');
        if(isset($_GET['params']) || !$alias)
            throw new CHttpException(404, 'Такой страницы не существует');

        $alias = explode('-', $alias,2);
        $type = substr($alias[0], strlen($alias[0]) - 1, 1);
        if($type != 'i' && $type != 'g')
            throw new CHttpException(404, 'Такой страницы не существует');

        $response = Service::model()->findServiceById($alias[0], $type, $this->clubInfo['id']);
        if($response) {
            if(!isset($alias[1]) || ($alias[1] != $response['alias']))
                $this->redirect($this->getServiceUrl($response), 301);
        } else {
            throw new CHttpException(404, 'Такой страницы не существует');
        }

        $response = Service::model()->findServiceByAlias($response['alias'], $type, $this->clubInfo['id']);
        if(!$response)
            throw new CHttpException(404, 'Такой страницы не существует');

        $this->render('_services', array(
            'services' => $response,
        ));
    }

    /* Новости клуба */
    public function actionClubNews() {
        $alias = Yii::app()->request->getQuery('alias_news');
        $before_n = null;
        $next_n = null;
 
        if($alias) {
			
            $alias = explode('-', $alias,2);
            //$responce = News::model()->findNewByParams(array('id' => $alias[0], 'id_club' => $this->clubInfo['id']));
            $responce = TextNews::model()->news(['id' => $alias[0]]);
 
            if($responce) {
               //if(!isset($alias[1]) || ($alias[1] != $responce['alias']))
               if(!isset($alias[1]) || ($alias[1] != $responce->slug))
                    //$this->redirect($this->getClubUrl().'/news/'.$responce['id'].'-'.$responce['alias'], 301);
                    $this->redirect($this->getClubUrl().'/news/'.$responce->id.'-'.$responce->slug, 301);
            }

            if(!$responce || isset($_GET['params']))
                throw new CHttpException(404, 'Такой страницы не существует');

            /*if($responce->before_id) {
                $before_n = News::model()->findNewByParams(array('id' => (int)$responce->before_id, 'id_club' => $this->clubInfo['id']));
            }

            if($responce->next_id) {
                $next_n = News::model()->findNewByParams(array('id' => (int)$responce->next_id, 'id_club' => $this->clubInfo['id']));
            }
            $photos = NULL;
            if($responce['files']) {
                foreach($responce['files'] as $file) {
                    if($file['type'] == 'photo') {
                        $photos[] = $file;
                    }
                }
            }*/

            $this->render('_new', array(
                'new' => $responce,
/*                'new_before' => $before_n,
                'new_next' => $next_n,
                'photos' => $photos,*/
            ));
        } else {

            if($this->pageNumber && !is_numeric($this->pageNumber))
                throw new CHttpException(404, 'Такой страницы не существует');

            $limit_news = 5;
            $pagination = Pagination::paginationLimit($limit_news, $this->pageNumber);

            $responce = News::model()->findNewsById($this->clubInfo['id'], $pagination[0], $pagination[1]);
            if(!$responce)
                throw new CHttpException(404, 'Такой страницы не существует');

            $this->render('_news', array(
                'news' => $responce,
                'limit_news' => $limit_news,
            ));
        }
    }

    public function actionClubSiteMap() {
        if((isset($_GET['params']) && !array_key_exists('xml', $_GET['params'])) || !isset($_GET['params']))
            throw new CHttpException(404, 'Такой страницы не существует');

        Sitemap::getInstance()->club($this->clubInfo);
    }

    private function getClubInfo($alias) {
//        if(is_numeric($alias)) {
//            $api_club = Club::model()->findById($alias);
//            if($api_club)
//                $this->redirect($api_club['alias'], true, 301);
//            else
//                throw new CHttpException(404, 'Страница данного клуба не найдена');
//        }
//        else
        $api_club = Club::model()->findByAlias($alias);
        return $api_club;
    }

    public function getClubPageTitleText($title) {
        $pageTitle = '';
        if($this->pageNumber && $this->pageNumber > 1)
            $pageTitle = ' cтраница '. $this->pageNumber;

        return $title.$pageTitle .' - '. $this->clubInfo['title']. ', ' .$this->clubInfo['address'];
    }

    public function getClubUrl() {
        return '/' .$this->clubInfo['alias'];
    }

    public function getServiceUrl($service) {
        return '/' .$this->clubInfo['alias'] .'/services/'. $service->id . substr($service->type, 0, 1) .'-'. $service->alias;
    }

    public static function getServiceSitemapUrl($service) {
        return '/services/'. $service->id . substr($service->type, 0, 1) .'-'. $service->alias;
    }

    public function  getServiceKeyUrl($service) {
        return $service->id . substr($service->type, 0, 1) .'-'. $service->alias;
    }

    public function actions() {
        return array(
            'captcha' => Yii::app()->params['captchaOptions'],
        );
    }

}