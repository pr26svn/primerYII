<?php
/* @var $this ClientsController */

$this->pageTitle = "Работа в Броско";
?>
<h2 class="title-page">Отправь нам свое резюме, мы ищем таланты!</h2>
<div class="form-training col-md-8 offset-md-2">
	<div class="col-md-8 offset-md-2 text-form">
		Мечтаешь об интересной и перспективной работе в фитнес-индустрии? Стань частью команды Броско! Мы ищем талантливых и активных сотрудников, готовых к развитию и карьерному росту. Заполни форму, привeденную ниже, и мы обязательно рассмотрим твою кандидатуру.
	</div>

	<div class="get-coupon-form">
		<?php $form=$this->beginWidget('CActiveForm', array(
            'id' => 'WorkFormController',
            'enableAjaxValidation'=>true,
            'clientOptions' => array(
                'validateOnSubmit' => false,
                'validateOnChange' => true,
                'inputContainer' => 'dd',
            ),
            'action' => Yii::app()->createUrl('//work#WorkForm'),
            'htmlOptions' => array(
                'class' => 'get-coupon-form-request',
                'enctype' => 'multipart/form-data',
            ),
            'focus'=>array($model,'name'),
        ));
        CHtml::$errorContainerTag = 'span';
        ?>
		<p class="center">Заполните, пожалуйста, все поля отмеченные *</p>
		<div class="form-group">
			<?php echo $form->labelEx($model,'name'); ?>
			<?php echo $form->textField($model,'name', array('class' => 'form-control')); ?>
			<?php echo $form->error($model,'name', array('class'=>'invalid-feedback')); ?>
		</div>

		<div class="form-group">
			<?php echo $form->labelEx($model,'phone'); ?>
			<?php echo $form->textField($model,'phone', array('class' => 'form-control')); ?>
			<?php echo $form->error($model,'phone', array('class'=>'invalid-feedback')); ?>
		</div>

		<div class="form-group">
			<?php echo $form->labelEx($model,'email'); ?>
			<?php echo $form->textField($model,'email', array('class' => 'form-control')); ?>
			<?php echo $form->error($model,'email', array('class'=>'invalid-feedback')); ?>
		</div>
		<div class="row">
			<div class="col">
				<?php echo $form->labelEx($model,'city'); ?>
				<?php echo $form->dropDownList($model, 'city', $clubs, array('class'=>'form-select js-form-city form-control')); ?>
				<?php echo $form->error($model,'city', array('class'=>'invalid-feedback')); ?>
			</div>
			<div class="col">
				<?php echo $form->labelEx($model,'club'); ?>
				<?php echo $form->dropDownList($model, 'club', $addresses, array('class'=>'form-select js-form-club form-control')); ?>
				<?php echo $form->error($model,'club', array('class'=>'invalid-feedback')); ?>
			</div>
		</div>

		<?php if(CCaptcha::checkRequirements() && Yii::app()->user->isGuest):?>
		<div class="row">
			<div class="col">
				<?php echo $form->labelEx($model,'verifyCode'); ?>
				<?php $this->widget('CCaptcha', array(
						'id' => 'captchaCallBackForm',
						'clickableImage'=> true,
						'imageOptions' => array('class' => 'captcha-img'),
						'showRefreshButton' => false,
					)) ?>
				<?php echo $form->textField($model,'verifyCode', array('class' => 'form-control')); ?>
				<?php echo $form->error($model,'verifyCode', array('class'=>'invalid-feedback')); ?>
			</div>
		</div>
		<?php endif; ?>

		<div class="form-group">
			<?php /*echo $form->labelEx($model,'resume'); ?>
			<?php echo $form->textField($model,'resume', array('class' => 'form-control')); ?>
			<?php echo $form->error($model,'resume', array('class'=>'invalid-feedback'));*/ ?>
		</div>

		<div class="form-group">
			<script>
				$( function () {
					$( "[data-input=pseudoFile]" ).click( function () {
						$target = $( $( this ).data( 'target' ) );
						$target.trigger( 'click' );
					} );
					$( '[data-input=pseudoFileHide]' ).change( function () {
						$( '[data-target="[data-id=' + $( this ).data( 'id' ) + ']"]' ).val( $( this ).val() );
					} );
				} );
			</script>
			<?php echo $form->textField($model,'resume_text', array('class' => 'input-text', 'data-input' => 'pseudoFile', 'data-target' => '[data-id=js-hidden-resume-file]', 'style' => 'cursor: pointer;', 'readonly' => 'readonly', 'value' => 'Выбрать файл...')); ?>
			<?php echo $form->fileField($model, 'resume', array('class'=>'form-select', 'data-input' => 'pseudoFileHide', 'data-id' => 'js-hidden-resume-file', 'style' => 'display: none;')); ?>
			<?php echo $form->error($model,'resume', array('class'=>'error')); ?>
		</div>

		<div class="form-group">
			<?php echo $form->labelEx($model,'exper'); ?>
			<?php echo $form->dropDownList($model,'exper',  $model->exper_arr, array('class' => 'form-control form-select')); ?>
			<?php echo $form->error($model,'exper', array('class'=>'invalid-feedback')); ?>
		</div>

        <p class="policy_new">«Нажимая кнопку «Отправить заявку», Вы даете согласие на обработку своих <a href="/policy.pdf">персональных данных</a>»</p>

        <div class="row">
			<?php echo CHtml::submitButton('Отправить', array('class' => 'btn btn-submit')); ?>
		</div>
		<?php $this->endWidget(); ?>
	</div>
</div>
