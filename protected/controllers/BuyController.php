<?php

class BuyController extends Controller{
	
	public function actionIndex(){
		
	}	
	
	public function actionListSoldAbonements(){
		$id_club= (int) Yii::app()->request->getPost('club');
		$abonements = Price::model()->getListSold($id_club);
		if (count ($abonements) > 0)
			$this->renderPartial('_abonements', ['abonements'=>$abonements], false, true);
		else
			echo 'Абонементов для продажи нет';
	}
	
	public function actionAbonement(){
        $model = new BuyForm();

        if(Yii::app()->request->getIsAjaxRequest() && Yii::app()->request->getPost('ajax') == 'buyAbonement'){
            echo CActiveForm::validate($model);
            Yii::app()->end();
        }
		
		if(isset($_POST['BuyForm'])){
            $model->attributes=$_POST['BuyForm'];
			$model->validate();
		
 			if(!$model->validate()){
				exit(json_encode(array('result'=>'error','msg'=>CHtml::errorSummary($model))));
			}
			else{

				$client = Clients::model()->isNewClient($model);
				$cash = Cash::model()->buyAbonement($model, $client);
				$price = Price::model()->findByPk($model->price);
				
				if(!isset($model->credit) && empty($model->credit) && $model->credit == 0 && $price->is_block_discount ==0){
					//расчет скидки
					$discount_percent = empty($client->id_discount) ? 0 : $client->idDiscount->discount_percent;
					$discount_sum = empty($client->id_discount) ? 0 : $client->idDiscount->discount_value;
				
					if ($discount_percent > 0){
						$resultCost = $price->cost - ($price->cost*$discount/100); //расчет итоговой стоимости (%)
					}elseif($discount_sum > 0){
						$resultCost = $price->cost - $discount_sum; //расчет итоговой стоимости (Σ)
					}
					else{
						$resultCost = $price->cost;
					}
				}
				else{
					$resultCost = $cash->cost;
				}
				
				$alias_club = Club::model()->getAliasClubById($model->clubChange);
 
				$pay = Yii::app()->payment;
				$result = (array)$pay->registerDo($alias_club, $cash->id, (int)$resultCost * 100, null, null, null, null);
 
				if (isset($result['formUrl'])){

					$cash->merchant_order_number = $result['orderId'];
					$cash->save();

					//return $this->redirect($result['formUrl']);
					exit(json_encode(['result'=>'success','msg'=>$result['formUrl']]));

				}else{
					$this->render('resultpay', ['type'=>'fail']);
				}
				
			}
        }
	}
	
	public function actionSuccess(){
		$cash = Cash::model()->findByAttributes(['merchant_order_number'=>Yii::app()->getRequest()->getParam('orderId')]);
		if (count($cash)>0){
			if (!empty($cash->merchant_order_number)){
				
				$club_id = Price::model()->getClubId($cash->id_price);
				$alias_club = Club::model()->getAliasClubById($club_id);
				
				$pay = Yii::app()->payment;
				$result = (array)$pay->getOrderStatus($alias_club, $cash->merchant_order_number);

				if(($result['ErrorCode']== 0) && ($cash->state == 0)){

					$cash->state = 1;
					$cash->delete = 0;
					$cash->number_credit_doc = $result['Pan'];
					$cash->save();
					Cash::model()->addAbonementClient($cash);
				}
				else{
					return $this->render('resultpay', ['type' => 'fail']);
				}
			}
			
		}
		$this->render('resultpay', ['type'=>'success']);
 	}
	
	public function actionFail(){
		$this->render('resultpay', ['type'=>'fail']);
	}
	
	public function actionGetInfoCredit(){
		
		if (!Yii::app()->getRequest()->getIsPostRequest() && !Yii::app()->getRequest()->getPost('idPrice') && !Yii::app()->getRequest()->getPost('credit')) {
            throw new CHttpException(404);
        }
		
		$info = Price::model()->getInfoCredit(Yii::app()->getRequest()->getPost('idPrice'), Yii::app()->getRequest()->getPost('credit'));
		echo json_encode($info);
		Yii::app()->end();
	}
	
}