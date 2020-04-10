<?php

class Location extends ApiModel {

    public $id_country;
    public $id_region;
    public $id_city;
    public $country;
    public $country_declension;
    public $country_latin;
    public $country_iso;
    public $region;
    public $region_declension;
    public $region_latin;
    public $region_timediff;
    public $city;
    public $city_declension;
    public $city_latin;
    public $city_lat;
    public $city_lng;
    public $city_population;

    public static $geo = null;
    public static $club = null;


    protected function getAction() {
        return 'getLocation';
    }

    public function findByIp($ip) {
        return $this->requestApi(array('ip' => $ip));
    }

    public function findById($id) {
        return $this->requestApi(array('id' => $id));
    }

    public function attributeApiNames(){
        return array(
            'id_country',
            'id_region',
            'id_city',
            'country',
            'country_declension',
            'country_latin',
            'country_iso',
            'region',
            'region_declension',
            'region_latin',
            'region_timediff',
            'city',
            'city_declension',
            'city_latin',
            'city_lat',
            'city_lng',
            'city_population',
        );
    }

    /**
     * @return self with data about location user
     */
    public static function getLocation()
    {
        $location = self::model();
        $geo = Yii::app()->request->cookies['id_city'];
        $id_club = Yii::app()->request->cookies['id_club'];

        if (!$geo) {
            self::$geo = $location->findByIp(self::getRealIPAddress());  //'213.135.96.34 - oren 87.226.188.54 - tuymen 195.208.32.22');


            if (self::$geo) {
                Yii::app()->request->cookies['id_city'] = new CHttpCookie('id_city', self::$geo->id_city);
            }
        } else {
            self::$geo = $location->findById($geo->value);
        }

        if ($id_club) {
            $club = Club::model()->findById($id_club->value);
            if($club)
                self::$club = $club;
        }

        return self::$geo;
    }

    public static function autoSelectClub() {
        $id_city = self::getIdCity();
        $id_club = self::getIdClub();
        if($id_city && !$id_club){
            $clubs = Club::model()->findByIdCity($id_city);
            if($clubs && sizeof($clubs) == 1) {
                Yii::app()->controller->redirect(array('/'. $clubs[0]['alias']));
            }
        }
    }

    public static function changeLocation($id_city = null, $id_club = null)
    {
        if($id_city)
            Yii::app()->request->cookies['id_city'] = new CHttpCookie('id_city', (int) $id_city);
        if($id_club)
            Yii::app()->request->cookies['id_club'] = new CHttpCookie('id_club', (int) $id_club);
    }

    public static function getRealIPAddress()
    {
        if (isset($_SERVER["HTTP_CLIENT_IP"])) {
            return $_SERVER["HTTP_CLIENT_IP"];
        } elseif (isset($_SERVER["HTTP_X_FORWARDED_FOR"])) {
            return $_SERVER["HTTP_X_FORWARDED_FOR"];
        } elseif (isset($_SERVER["HTTP_X_FORWARDED"])) {
            return $_SERVER["HTTP_X_FORWARDED"];
        } elseif (isset($_SERVER["HTTP_FORWARDED_FOR"])) {
            return $_SERVER["HTTP_FORWARDED_FOR"];
        } elseif (isset($_SERVER["HTTP_FORWARDED"])) {
            return $_SERVER["HTTP_FORWARDED"];
        } else {
            return $_SERVER["REMOTE_ADDR"];
        }
    }

    /**
     * @return id city if exist else null
     */
    public static function getIdCity() {
        $id_city = Yii::app()->request->cookies['id_city'];
        return ($id_city) ? $id_city->value : NULL;
    }

    /**
     * @return id club if exist else null
     */
    public static function getIdClub() {
        $id_club = Yii::app()->request->cookies['id_club'];
        return ($id_club) ? $id_club->value : NULL;
    }

    /**
     * @return boolean did show popup with select club
     */
    public static function isClubSelected() {
        $isSelected = Yii::app()->request->cookies['is_club_selected'];
        return ($isSelected) ? $isSelected->value : NULL;
    }

}
